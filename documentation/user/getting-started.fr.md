---
title: Premiers pas
weight: 10
---

# Premiers pas

```bash
composer require gplanchat/opaque
```

PHP 8.2 ou plus, avec `ext-sodium` (livrée avec PHP depuis la 7.2).

## Déclarer un secret

```php
use Gplanchat\Opaque\Opaque;

final class ApiKey extends Opaque {}

$key = ApiKey::fromString($_ENV['PAYMENT_API_KEY']);
```

Une sous-classe nomme le secret : une signature dit lequel elle attend, `function charge(ApiKey $key)`.

## Ce qu'il refuse de montrer

```php
echo $key;                 // **SECRET**
var_dump($key);            // content => "**SECRET**"
var_export($key);          // un objet vide
json_encode($key);         // lève LeakRefused
serialize($key);           // lève LeakRefused
```

La valeur n'est pas une propriété de l'objet : les sérialiseurs par réflexion ne trouvent rien non
plus. Pour journaliser la présence d'un secret, on journalise `(string) $key` : le masque.

## Le relire

```php
use function Gplanchat\Opaque\disclose;

$client->authenticate(disclose($key));
```

`disclose()` est la seule sortie. On l'appelle en bordure, là où le secret est remis à la
bibliothèque qui en a besoin, et nulle part ailleurs.

## Le chiffrer pour le stocker

```php
use Gplanchat\Opaque\Cipher\SodiumSecretBox;
use Gplanchat\Opaque\Ciphered;
use Gplanchat\Opaque\Keyring;

$keyring = new Keyring(new SodiumSecretBox($trenteDeuxOctets));

$row = $keyring->encrypt($key)->toWire();           // un tableau, stockable en JSON
$key = $keyring->decrypt(Ciphered::fromWire($row), ApiKey::class);
```

La clé se génère une fois avec `sodium_crypto_secretbox_keygen()` et reste hors du dépôt.

| Chiffrement | Quand |
|---|---|
| `SodiumSecretBox` | une clé chiffre et déchiffre |
| `SodiumSealedBox` | `forPublicKey()` chiffre seulement, `fromKeypair()` fait les deux : qui écrit ne détient rien pour relire |
| `Base64Encoding`, `PlainText` | **non sécurisés**, pour le développement et les tests seulement |

## Changer de clé

```php
$keyring = new Keyring(new SodiumSecretBox($nouvelleCle), new SodiumSecretBox($ancienneCle));
```

Le premier chiffrement écrit, tous ceux du trousseau relisent ce qu'ils ont écrit. Chaque ligne
note l'algorithme et l'identifiant de sa clé : une ligne ancienne retrouve sa clé, une ligne
qu'aucune clé n'a écrite est refusée. Une clé retirée reste dans le trousseau tant qu'une ligne
qu'elle a écrite peut encore être relue.
