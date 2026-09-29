# OPQ001 — Opaque and ciphered value objects

## Status

Accepted

## Context

Some values are costly when they leak: cryptographic keys, credentials, access tokens (an IT
breach), personal data under the GDPR or the CCPA (a legal one), trade secrets (an industrial one).
In PHP they leak through channels nobody opened on purpose:

- `var_dump()`, `print_r()`, `var_export()`, `json_encode()`, an `(array)` cast;
- `serialize()` / `unserialize()`, and every queue or cache that uses them;
- a string cast in a log line or an exception message;
- stack traces, which print the arguments of every frame;
- reflection, and the serializers built on it (Symfony's `ObjectNormalizer` and
  `PropertyNormalizer` read private properties).

This decision generalises an earlier one, *HIVE004 — Opaque and Secret data objects*, written for
a single application (`App\Util\Vault`). It keeps its intent and its vocabulary — an **opaque**
value object hides a value, a **ciphered** value object carries an encrypted one together with
what deciphering it needs, and a `disclose()` function is the way out — and makes it a standalone
package. Where HIVE004 left a gap, or where a reusable component cannot keep its choice, this
record says so and decides.

## Decision

### Package and namespace

`gplanchat/opaque`, namespace `Gplanchat\Opaque\`, PHP ≥ 8.2, `ext-sodium`, no framework.

### The opaque value: `Opaque`

`Gplanchat\Opaque\Opaque` is a value object built with `Opaque::fromString()`. An application
**extends it to name a secret** — `final class ApiKey extends Opaque {}` — so that a signature
states which secret it takes; `fromString()` returns `static`, and everything that guards the
content is `final`.

**The content is not a property of the instance.** It lives in a private static
`WeakMap<Opaque, SensitiveParameterValue>` keyed by the instance. This is the main departure from
HIVE004, which kept a private `SensitiveParameterValue` property and listed reflection as an
uncovered channel. With no instance property at all:

| Channel | Result |
|---|---|
| `var_dump()`, `print_r()` | `__debugInfo()` shows `content => **SECRET**` |
| `(string)` | `**SECRET**` (`Opaque::MASK`) |
| `var_export()`, `(array)`, `get_object_vars()` | an object with nothing inside |
| `ReflectionObject::getProperties()` on the instance | no instance property to read |
| `ObjectNormalizer`, `PropertyNormalizer` | nothing to normalize — see OPQ002 |
| stack traces | `fromString()` marks its parameter `#[\SensitiveParameter]`; frames receive the object, which dumps masked |
| `serialize()`, `json_encode()` | **throw** `LeakRefused` |
| `unserialize()`, `__set_state()` | **throw** `LeakRefused` |
| `clone` | refused (private `__clone`) |

Two choices in that table differ from HIVE004 and are deliberate:

- **`serialize()` throws instead of writing an empty object.** HIVE004's `__serialize()` returned
  `[]`. That loses the value silently, and the loss surfaces where the payload is read back — in
  another process, later, as a `RuntimeException` from `__unserialize()`. Refusing at the write
  puts the failure next to the mistake. A value that must be stored is ciphered first.
- **`json_encode()` throws too** (`JsonSerializable::jsonSerialize()`), for the same reason. An
  object with no public property encodes as `{}`, and a store that journals its payloads with
  `json_encode()` — gplanchat/durable's do — would keep an empty object where the secret was, for
  ever. The cost is accepted and stated: a logger that JSON-encodes its context (Monolog's
  normalizer calls `jsonSerialize()`) throws on an opaque value instead of printing the mask. Log
  `(string) $secret`, which is the mask, or nothing.
- **`clone` is refused.** A clone is a new key in the map, and `__clone()` is not told which
  instance it copies, so the clone would come out empty. An immutable value gains nothing from a
  copy.

One silent-loss channel remains: Symfony's `ObjectNormalizer`, used **without** the bridge of
OPQ002, finds nothing to read and writes an empty object. It does not leak, but it loses the value,
and nothing in the class can make it refuse. The bridge is the answer (OPQ002).

What remains uncovered, stated rather than hidden: **static reflection**
(`ReflectionClass::getStaticPropertyValue()` on `Opaque`, or a closure bound to its scope) reads
the map. Nothing in PHP code can prevent a caller who deliberately targets the class; the aim is
that no *accidental* channel exposes the value.

### The way out: `disclose()`

```php
namespace Gplanchat\Opaque;

function disclose(Opaque $opaque): string;
```

The **only** public way to turn an opaque value back into a string. Every call site is a place
where a secret becomes a string again; there should be few of them, at the edge of the
application, and an audit finds them with one grep.

**HIVE004's `BlobInterface::asString()` / `asResource()` on the opaque value are dropped.** They
returned the clear value, which made `disclose()` one exit among three rather than the one exit —
or, if they returned the mask, they duplicated `__toString()`. Either way the contradiction is
resolved by removing them. `asResource()` can come back when a caller needs a stream, as a function
next to `disclose()`.

### Interfaces HIVE004 declared, and why they are not here

HIVE004 declared `BlobInterface`, `OpaqueInterface` and `CipheredInterface`, and required that
`BlobInterface` be implemented only through the two others. PHP cannot enforce that requirement,
and an interface anyone can implement is a way *around* the guards: an `OpaqueInterface`
implementation that forgets `__debugInfo()` is accepted wherever an opaque value is. So the guards
live in one class, `Opaque`, with its magic methods `final`, and an application extends the class
rather than implementing an interface. If the sealing becomes necessary later, it is a PHPStan rule,
not an interface.

### The ciphered value: `Ciphered`

`Gplanchat\Opaque\Ciphered` is what may be stored, journaled or sent. It holds four strings:
`algorithm`, `keyId`, `nonce`, `payload`. HIVE004's ciphered object carried the nonce and the
encrypted text; **the algorithm and the key id are added** because a ciphered value that crosses a
journal is read back after the key has rotated, sometimes by a newer deployment, and an
append-only journal cannot be rewritten to say which key wrote which row.

Its wire form is an array, written by `toWire()` and read by `fromWire()`:

```php
['v' => 1, 'alg' => 'sodium-secretbox', 'kid' => '…', 'nonce' => '<base64>', 'data' => '<base64>']
```

- `v` is the wire version. `fromWire()` refuses a version it does not know.
- `kid` and `nonce` leave the wire when empty (the unkeyed encodings have neither).
- `fromWire()` **validates and throws** `CipherFailure` on a missing field, a wrong type or invalid
  base64: a journal row is data from outside, and a malformed one must not become a silently
  wrong value.
- The payload of an unsecured encoding is the clear text, so `Ciphered::__debugInfo()` masks it.

### Ciphers

`Gplanchat\Opaque\Cipher\Cipher` turns an `Opaque` into a `Ciphered` and back. The clear text never
crosses the interface as a string.

| Class | Algorithm id | Key | Notes |
|---|---|---|---|
| `SodiumSecretBox` | `sodium-secretbox` | 32-byte secret key | XSalsa20-Poly1305, random nonce per value |
| `SodiumSealedBox` | `sodium-sealedbox` | X25519 public key to cipher, keypair to decipher | anonymous sender |
| `Base64Encoding` | `base64` | none | **unsecured** |
| `PlainText` | `plain` | none | **unsecured** |

HIVE004 listed "Sodium Box" (`crypto_box`), which authenticates a *sender* keypair to a
*recipient*. Storing one's own secrets has no second party, and what the asymmetric variant is
worth here is different: **a process that only writes secrets holds nothing that reads them
back**. That is the sealed box (`crypto_box_seal`), so it replaces the box.
`SodiumSealedBox::forPublicKey()` can only cipher; `fromKeypair()` can do both.

Keys are held inside the cipher as `Opaque` values, so a cipher dumps without its key. A key id is
a domain-separated, truncated SHA-256 of the key (of the public key, for the sealed box): enough to
tell keys apart on a ring, useless to recover one.

A cipher refuses a `Ciphered` whose algorithm or key id is not its own, with `CipherFailure`, before
attempting anything. A failed authentication (wrong key, tampered payload) is `CipherFailure` too.
No exception message carries clear text or key material.

The two unsecured encodings exist for development and tests, where no key exists. They are named
as what they are.

### Rotation: `Keyring`

`Gplanchat\Opaque\Keyring` holds the current cipher and the ones that wrote older rows. It
encrypts with the current one and deciphers a row with the cipher whose algorithm **and** key id
the row names; a row none of them wrote is refused. A consequence worth stating: **a keyring
without an unsecured encoding refuses a clear row**, so a secret someone wrote in clear into the
journal is not accepted as if it had been ciphered.

`Keyring::decrypt()` takes the opaque class to restore (`Password::class`), so a typed secret comes
back typed.

## Consequences

- An application declares its secrets as subclasses of `Opaque` and never handles them as strings
  between the edges.
- Anything that persists a secret goes through a `Keyring`; `serialize()` on an opaque value fails
  loudly, at the write.
- Every type that crosses the journal — `Ciphered` — has a round-trip test and a test of the shape
  version 1 writes. A new wire field bumps `v` or is optional; rows already written keep reading.
- The Symfony Serializer integration is a separate package: OPQ002.
- Follow-up, not decided here: a PHPStan rule flagging `disclose()` outside an allowed set of
  namespaces; a stream variant of `disclose()`; key derivation helpers.
