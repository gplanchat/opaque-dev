# WA004 — MIT license for the repository and Composer packages

## Status

Accepted

## Context

The Opaque monorepo and its **satellite repositories** ([OPQ005](../adr/OPQ005-monorepo-splitsh-and-satellite-repositories.md)) are meant for open distribution
(Packagist, GitHub, forks). A single permissive license keeps legal expectations aligned across the
root project and the split packages.

## Agreement

### License choice

All first-party source code, documentation and assets in this repository are distributed under the
**MIT License** (SPDX `MIT`), unless a file explicitly states otherwise (for instance a vendored
third-party snippet with its header retained).

### `LICENSE` and `composer.json` at each package root

Every first-party subtree that maps to a Composer package or a satellite repository carries its own
plain-text `LICENSE` (the same MIT text as the root) and declares `"license": "MIT"` in its
`composer.json`, so a split publishes a repository with `LICENSE` at its root.

| Package | `composer.json` | `LICENSE` |
|---------|-----------------|-----------|
| Monorepo root (dev tooling) | `composer.json` | `LICENSE` |
| `gplanchat/opaque` | `src/Opaque/composer.json` | `src/Opaque/LICENSE` |
| `gplanchat/opaque-bridge-serializer` | `src/Bridge/Serializer/composer.json` | `src/Bridge/Serializer/LICENSE` |

The root `LICENSE` is the canonical copy.

### Third-party dependencies

Dependencies keep their own licenses. Adding one that is incompatible with distributing our code
under MIT requires an explicit review (an ADR or an update of this WA).

### User-visible documentation

The root README and each package README state that the project is under the MIT License and point
to the relevant `LICENSE` file.

## Consequences

- A new package directory with a `composer.json` uses `"license": "MIT"` and adds a root `LICENSE`.
- A pull request that changes the license or constrains our code with copyleft or proprietary
  terms needs explicit maintainer agreement before merge.

## References

- [MIT License (OSI)](https://opensource.org/licenses/MIT)
- [OPQ005](../adr/OPQ005-monorepo-splitsh-and-satellite-repositories.md) — Monorepo, splitsh, and satellite repositories
- [documentation/INDEX.md](../INDEX.md)
