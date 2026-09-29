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

How secrets travel through durable's journal is durable's to decide and document: see its own
guide. This component only guarantees that an opaque value never reaches a journal in clear —
`json_encode()` and `serialize()` refuse it with `LeakRefused`.
