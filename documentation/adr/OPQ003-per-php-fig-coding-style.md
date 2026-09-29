# OPQ003 — PER (PHP-FIG) coding style and naming

## Status

Accepted

## Context

Opaque code must stay readable and consistent with the other gplanchat packages it is used with,
gplanchat/durable first. Those follow PHP-FIG's **PER Coding Style**, the successor of PSR-12.

## Decision

All PHP code in this repository, tests included, complies with **PER Coding Style 3.0**
([php-fig.org/per/coding-style](https://www.php-fig.org/per/coding-style/)).

- Classes, interfaces, enums: `PascalCase`. Methods and properties: `camelCase`. Class constants:
  upper case with underscores.
- `declare(strict_types=1);` in every file.
- Global classes are written with their leading slash (`\WeakMap`), not imported.

### Tooling

PHP-CS-Fixer, configured in `.php-cs-fixer.dist.php` with the **pinned** `@PER-CS3.0` rule set —
not the floating `@PER-CS` alias, which would bring the next revision in unannounced as a red CI
after a `composer update`. `composer cs:check` runs in CI; `composer cs:fix` applies it.

### Evolution

When a new major PER revision is published, raising the pinned number is a deliberate change, made
in one commit of its own, reformatting included.

## Consequences

- Reviews cite PER rather than local habits.
- The configuration is the same as gplanchat/durable's, so a contributor moving between the two
  repositories meets one style.
