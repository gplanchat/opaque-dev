# WA008 — Publishing a version: `bin/release.sh`, from origin/main only

## Status

Accepted

## Context

Publishing a version of Opaque takes two steps:

1. a tag on a commit, pushed to origin. Splitsh propagates it to the satellite repositories
   ([OPQ005](../adr/OPQ005-monorepo-splitsh-and-satellite-repositories.md)), and Packagist follows;
2. a GitHub release for that tag, marked as a prerelease while the line is unstable, with notes
   listing what changed since the previous version.

Each step has a trap:

- **A tag off origin/main.** A working copy's local `main` often carries a commit nobody pushed — a
  prise file, for instance. A tag on it publishes, to every satellite at once, a commit that exists
  nowhere else.
- **The wrong previous tag.** Asked to generate notes, `gh release create` picks the previous tag
  itself, and sorts lexically: `v0.1.0-alpha9` comes after `v0.1.0-alpha10`, so alpha10's notes
  would list alpha9's changes.

## Agreement

**A version is published with `bin/release.sh`, and never by hand.**

```bash
bin/release.sh v0.1.0-alpha1 --dry-run              # what would happen
bin/release.sh v0.1.0-alpha1                        # tag the tip of origin/main, then release
bin/release.sh v0.1.0-alpha1 <sha> --notes-file body.md
bin/release.sh v0.1.0-alpha1 --release-only         # the tag is pushed, the release failed
```

The script:

- fetches origin and **refuses any commit not reachable from origin/main**; without a commit, it
  tags the tip of origin/main;
- refuses a malformed tag (`vMAJOR.MINOR.PATCH`, optionally `-alphaN`, `-betaN` or `-rcN`) or one
  that already exists, locally or on origin;
- finds the previous tag in **version order**, where a prerelease sorts before its release — the
  newest tag *below* the new one — and passes it as `--notes-start-tag`;
- creates an annotated tag, pushes it, then creates the release titled `Version <x>`, with
  generated notes, `--prerelease` for any `-alpha`, `-beta` or `-rc` tag;
- places an editorial body above the generated notes when given `--notes-file`;
- finishes a release whose tag is already pushed with `--release-only`, after checking the tag is
  on origin and on origin/main.

`bin/release-test.sh` checks all of this on a throwaway repository, with a stub `gh`.

## Before and after

- **Before:** tag only a commit whose CI is green — the script checks where the commit is, not
  whether it passed. If the repository keeps an `UPGRADE.md`, cut it first: rename "Unreleased" to
  the version being tagged, open an empty "Unreleased" above it, and merge that through a PR, so the
  next version's entries do not land in a section the tag already shipped.
- **After:** check that every satellite received the tag.
