# OPQ002 — The Symfony Serializer bridge

## Status

Accepted

## Context

An opaque value (OPQ001) cannot be serialized as it is: `serialize()` and `json_encode()` refuse
it, and the stock normalizers find nothing inside it. An application that serializes objects with
Symfony Serializer — for a message bus, an HTTP API, a cache, a journal — needs a normalizer that
**ciphers on the way out and restores an opaque value on the way in**.

The motivating host is gplanchat/durable, whose journal is append-only and replayed weeks later.
What its code does was read before this record was written (durable-dev at its `main` of
2026-09-29), and it constrains the integration more than its own DUR007 suggests:

- **Payloads are not serialized with Symfony Serializer.** An activity stub builds a named array
  of the raw arguments, and the stores write it with `json_encode()`. Before OPQ001's refusal, an
  opaque argument was journaled as `{}` — the secret lost, silently, for ever. It now fails at the
  write.
- **Replay compares payloads.** Besides the identity of each step (its DUR042), the execution
  context compares the canonical JSON of the payload a replay rebuilds with the one the journal
  holds, and fails the task on a difference. A value ciphered **inside workflow code** with a fresh
  random nonce differs on every pass, so it would trip that guard.

## Decision

A separate package, `gplanchat/opaque-bridge-serializer` (namespace
`Gplanchat\Opaque\Bridge\Serializer\`), so that `gplanchat/opaque` stays framework-free. It holds one
class, `OpaqueNormalizer`, both normalizer and denormalizer.

- **Normalize**: `Opaque` → `Keyring::encrypt()` → `Ciphered::toWire()`, the array of OPQ001.
- **Denormalize**: array → `Ciphered::fromWire()` → `Keyring::decrypt($row, $type)`, so the
  declared subclass (`Password`) is what comes back.
- **Supported types**: `['object' => true]` — the classes an application declares are subclasses of
  `Opaque`, which a list of exact class names cannot cover; the per-class answer is cacheable.
- **Order**: it is registered before `ObjectNormalizer` and `PropertyNormalizer`.

### It fails closed

- The constructor takes a `Keyring`, not an optional cipher. There is no configuration of this
  normalizer that writes an opaque value some other way.
- A row that is not an array — a secret written in clear, say — is refused with
  `NotNormalizableValueException`, and the message names the type, never the value.
- A row the keyring did not write is refused with `CipherFailure` (OPQ001).
- A serializer assembled **without** this bridge does not leak either: `ObjectNormalizer` finds no
  getter and no property, `PropertyNormalizer` refuses an object with nothing to read. That is
  tested, because it is the failure mode of a misconfigured host.

### gplanchat/durable: not decided here

Given the context above, registering this normalizer in a durable host is **not** enough to
journal secrets ciphered. Two paths exist, and choosing between them is a later record:

- **Without changing durable**: the caller ciphers the secret before starting the run and passes
  the wire array as workflow input; the workflow carries that array unchanged to the activity,
  which deciphers it. The input is recorded once, so every replay pass rebuilds the same payload.
- **With a payload codec in durable**: its stores and its replay guard run payloads through a
  serializer holding this bridge, and the guard compares something stable across passes — which a
  random nonce is not, so either the guard compares disclosed content by a keyed hash, or the
  cipher is deterministic (SIV-like), which reveals that two rows hold the same secret.

The second path changes durable, whose dependency and ADR changes are supervised there.

No Symfony bundle is shipped here yet: registering one normalizer service is one line of
configuration, and a bundle would be a package to maintain for it.

## Consequences

- The bridge supports the Symfony lines durable supports: `symfony/serializer` `^6.4 || ^7.0 ||
  ^8.0`, exercised by the `symfony-matrix` CI job.
- The ciphered wire format of OPQ001 becomes a storage format wherever the bridge writes: its
  compatibility rules (versioned, append-only, round-trip tested) apply to every row written with
  it.
- **Retiring a key is bounded by what still reads it.** A key stays on the keyring as long as any
  stored row it wrote can still be read — for a journal, as long as any run holding such a row can
  still replay. Removing it earlier turns those rows into `CipherFailure`.
- Follow-up: the durable integration record above; a bundle, if registering the normalizer and its
  keyring from configuration turns out to be repeated work across hosts.
