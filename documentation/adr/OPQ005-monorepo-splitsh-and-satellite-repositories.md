# OPQ005 — Monorepo, splitsh, and satellite repositories

## Status

Accepted

## Context

The component ships as more than one Composer package: the framework-free core, and bridges that
depend on a framework. They change together, and are tested together, but are installed apart.

## Decision

### One development repository, read-only satellites

Development happens in `gplanchat/opaque-dev`. Each package lives under a path prefix and is
published to its own read-only repository by **splitsh-lite**:

| Prefix | Package | Satellite |
|---|---|---|
| `src/Opaque/` | `gplanchat/opaque` | `gplanchat/opaque` |
| `src/Bridge/Serializer/` | `gplanchat/opaque-bridge-serializer` | `gplanchat/opaque-bridge-serializer` |

The list lives in `bin/splitsh-publish.sh`. The root `composer.json` installs the packages through
path repositories, so the monorepo always tests them together, at the same commit.

### Behaviour

- A push to `main` splits each prefix and pushes to the satellite's `main`; a `v*` tag splits the
  tagged commit and pushes the same tag to each satellite.
- **The push is conditional** on the `SPLITSH_PUSH_TOKEN` secret (a PAT with `contents: write` on
  the satellites). Without it the workflow skips: the satellites are created when publication
  starts, not before.
- Checkout keeps `persist-credentials: false`, so the default `GITHUB_TOKEN` helper does not
  override the pushes to other repositories.
- Force-pushing a satellite is an explicit, off-by-default option.

### Each package installs alone

- Each prefix ships its own `LICENSE` (MIT, WA004) and `composer.json`.
- A package requires what it imports: `bin/check-manifests.sh` runs `composer validate` and
  composer-require-checker on every prefix, and `bin/check-root-lock.sh` checks the root lock
  against the path manifests. Both run in the `manifests` CI job.
- Sibling packages require each other at `self.version`.

## Consequences

- Adding a package means a prefix in `bin/splitsh-publish.sh`, a path repository in the root
  `composer.json`, its paths in `phpunit.xml`, `phpstan.neon` and `psalm.xml`, and a satellite
  repository — a supervised change (`CLAUDE.md`).
- Secrets are configured at the repository level by a maintainer; they never appear in logs.
