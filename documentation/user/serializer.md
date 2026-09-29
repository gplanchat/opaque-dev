---
title: Symfony Serializer
weight: 20
---

# Symfony Serializer

```bash
composer require gplanchat/opaque-bridge-serializer
```

`OpaqueNormalizer` ciphers an opaque value on the way out and restores it, typed, on the way in.
Register it **before** the object normalizer.

```php
use Gplanchat\Opaque\Bridge\Serializer\OpaqueNormalizer;
use Gplanchat\Opaque\Cipher\SodiumSecretBox;
use Gplanchat\Opaque\Keyring;

$serializer = new Serializer(
    [new OpaqueNormalizer(new Keyring(new SodiumSecretBox($key))), new ObjectNormalizer()],
    [new JsonEncoder()],
);

$json = $serializer->serialize(new Credentials('alice', Password::fromString('s3cr3t')), 'json');
// {"user":"alice","password":{"v":1,"alg":"sodium-secretbox","kid":"…","nonce":"…","data":"…"}}
```

With the Symfony framework, declare it as a service; autoconfiguration tags it as a normalizer:

```yaml
services:
    Gplanchat\Opaque\Keyring:
        arguments: ['@app.opaque_cipher']
    app.opaque_cipher:
        class: Gplanchat\Opaque\Cipher\SodiumSecretBox
        arguments: ['%env(base64:OPAQUE_KEY)%']
    Gplanchat\Opaque\Bridge\Serializer\OpaqueNormalizer:
        tags: [{ name: serializer.normalizer, priority: 100 }]
```

## What it refuses

- A row its keyring did not write: `CipherFailure`.
- A string where a ciphered row is expected — a secret written in clear: `NotNormalizableValueException`,
  whose message names the type, never the value.

Without the bridge, the stock normalizers do not leak either: they find nothing to read.

## With gplanchat/durable

Registering the normalizer is not enough there yet: durable journals workflow and activity
payloads with `json_encode()`, not with Symfony Serializer, and its replay guard compares payloads,
which a fresh nonce would change on every pass. Until a dedicated integration exists, cipher the
secret where the run starts, carry the row through the workflow unchanged, and decipher it in the
activity:

```php
// the caller
$dispatcher->dispatchNewWorkflowRun($executionId, 'connect', [
    'user' => 'alice',
    'password' => $keyring->encrypt($password)->toWire(),
]);

// the workflow passes the recorded row on as is, so every replay rebuilds the same payload
$activities->connect(user: $user, password: $password);

// the activity
$password = $keyring->decrypt(Ciphered::fromWire($password), Password::class);
```

Passing an `Opaque` to a workflow or an activity directly fails at the write, with `LeakRefused`,
instead of journaling an empty object.
