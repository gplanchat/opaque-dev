---
title: Getting started
weight: 10
---

# Getting started

```bash
composer require gplanchat/opaque
```

PHP 8.2 or later, with `ext-sodium` (bundled with PHP since 7.2).

## Declare a secret

```php
use Gplanchat\Opaque\Opaque;

final class ApiKey extends Opaque {}

$key = ApiKey::fromString($_ENV['PAYMENT_API_KEY']);
```

A subclass names the secret, so a signature says which one it takes: `function charge(ApiKey $key)`.

## What it refuses to show

```php
echo $key;                 // **SECRET**
var_dump($key);            // content => "**SECRET**"
var_export($key);          // an object with nothing inside
json_encode($key);         // throws LeakRefused
serialize($key);           // throws LeakRefused
```

The value is not a property of the object, so reflection-based serializers find nothing either.
To log that a secret is there, log `(string) $key`: the mask.

## Read it back

```php
use function Gplanchat\Opaque\disclose;

$client->authenticate(disclose($key));
```

`disclose()` is the only way out. Call it at the edge — where the secret is handed to the library
that needs it — and nowhere else.

## Cipher it for storage

```php
use Gplanchat\Opaque\Cipher\SodiumSecretBox;
use Gplanchat\Opaque\Ciphered;
use Gplanchat\Opaque\Keyring;

$keyring = new Keyring(new SodiumSecretBox($thirtyTwoBytes));

$row = $keyring->encrypt($key)->toWire();           // an array, safe to store as JSON
$key = $keyring->decrypt(Ciphered::fromWire($row), ApiKey::class);
```

Generate a key once with `sodium_crypto_secretbox_keygen()` and keep it out of the repository.

| Cipher | When |
|---|---|
| `SodiumSecretBox` | one key ciphers and deciphers |
| `SodiumSealedBox` | `forPublicKey()` only ciphers; `fromKeypair()` does both — a writer holds nothing that reads |
| `Base64Encoding`, `PlainText` | **unsecured**, for development and tests only |

## Rotate keys

```php
$keyring = new Keyring(new SodiumSecretBox($newKey), new SodiumSecretBox($oldKey));
```

The first cipher writes; every cipher on the ring reads the rows it wrote. Each row records the
algorithm and the id of its key, so an old row finds its key and a row no key wrote is refused.
Keep a retired key on the ring for as long as any stored row it wrote may still be read.
