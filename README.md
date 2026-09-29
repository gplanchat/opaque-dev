# Opaque

Secrets as PHP value objects. An opaque value does not leak through `var_dump`, `print_r`,
`var_export`, `json_encode`, `serialize`, a string cast or a reflection-based serializer;
`disclose()` is the one way out. A ciphered value is what may be stored, and a Symfony Serializer
bridge ciphers opaque values on their way into a payload — such as
[gplanchat/durable](https://github.com/gplanchat/durable-dev)'s journal.

```php
use Gplanchat\Opaque\Opaque;
use function Gplanchat\Opaque\disclose;

final class ApiKey extends Opaque {}

$key = ApiKey::fromString($_ENV['PAYMENT_API_KEY']);
echo $key;                          // **SECRET**
$client->authenticate(disclose($key));
```

Monorepo:

| Package | Role | PHP |
|---|---|---|
| `src/Opaque` — `gplanchat/opaque` | `Opaque`, `disclose()`, `Ciphered`, the ciphers (`SodiumSecretBox`, `SodiumSealedBox`, and two unsecured encodings) and the `Keyring` that rotates keys. Framework-free, `ext-sodium`. | ≥ 8.2 |
| `src/Bridge/Serializer` — `gplanchat/opaque-bridge-serializer` | `OpaqueNormalizer`: Symfony Serializer 6.4 / 7 / 8. | ≥ 8.2 |
| root | Dev tooling only; installs both by `path`. | ≥ 8.2 |

The user guide is in [`documentation/user/`](documentation/user/_index.md). The decisions — why,
and what was turned down — are in [`documentation/adr/`](documentation/INDEX.md): start with
[OPQ001](documentation/adr/OPQ001-opaque-and-ciphered-value-objects.md) and
[OPQ002](documentation/adr/OPQ002-symfony-serializer-bridge.md).

## Development

```bash
composer install
composer test        # PHPUnit
composer phpstan     # level 8, no baseline
composer psalm
composer cs:check    # PER-CS 3.0
loop/guardrails/verify.sh   # all of the above, as CI runs them
```

Contributions follow [`CLAUDE.md`](CLAUDE.md) and the working agreements in
[`documentation/wa/`](documentation/wa/): English, TDD, one prise per branch
(`.worktrees/PRISES.md`).

## License

MIT — see [`LICENSE`](LICENSE).
