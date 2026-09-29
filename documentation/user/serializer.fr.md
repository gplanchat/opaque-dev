---
title: Symfony Serializer
weight: 20
---

# Symfony Serializer

```bash
composer require gplanchat/opaque-bridge-serializer
```

`OpaqueNormalizer` chiffre une valeur opaque à la sortie et la restaure, typée, à l'entrée. Il
s'enregistre **avant** le normaliseur d'objets.

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

Avec le framework Symfony, on le déclare en service ; l'autoconfiguration le marque comme
normaliseur :

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

## Ce qu'il refuse

- Une ligne que son trousseau n'a pas écrite : `CipherFailure`.
- Une chaîne là où une ligne chiffrée est attendue, c'est-à-dire un secret écrit en clair :
  `NotNormalizableValueException`, dont le message nomme le type, jamais la valeur.

Sans le bridge, les normaliseurs standards ne fuient pas non plus : ils ne trouvent rien à lire.

## Avec gplanchat/durable

La façon dont les secrets traversent le journal de durable relève de durable, qui la décide et la
documente dans son propre guide. Ce composant garantit seulement qu'une valeur opaque n'atteint
jamais un journal en clair : `json_encode()` et `serialize()` la refusent avec `LeakRefused`.
