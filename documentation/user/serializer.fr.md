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

Enregistrer le normaliseur n'y suffit pas encore : durable journalise les charges utiles des
workflows et des activités avec `json_encode()`, pas avec Symfony Serializer, et son garde de rejeu
compare les charges utiles, ce qu'un nonce neuf changerait à chaque passe. En attendant une
intégration dédiée, on chiffre le secret là où l'exécution démarre, on fait traverser le workflow à
la ligne sans la toucher, et on la déchiffre dans l'activité :

```php
// l'appelant
$dispatcher->dispatchNewWorkflowRun($executionId, 'connect', [
    'user' => 'alice',
    'password' => $keyring->encrypt($password)->toWire(),
]);

// le workflow transmet la ligne enregistrée telle quelle : la même charge utile à chaque rejeu
$activities->connect(user: $user, password: $password);

// l'activité
$password = $keyring->decrypt(Ciphered::fromWire($password), Password::class);
```

Passer directement un `Opaque` à un workflow ou à une activité échoue à l'écriture, avec
`LeakRefused`, au lieu de journaliser un objet vide.
