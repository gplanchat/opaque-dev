# Index — Opaque documentation

This repository documents the **Opaque** component: opaque value objects that refuse to leak a
secret, ciphered value objects that may be stored, and a Symfony Serializer bridge.

**Language:** all normative documents in `documentation/adr/` and `documentation/wa/` are
**English** — see [WA001](wa/WA001-english-language-documentation.md). **Development** follows
**TDD** — see [WA002](wa/WA002-test-driven-development.md). **Licensing:** MIT — see
[WA004](wa/WA004-mit-license-distribution.md).

## Architecture Decision Records (ADR)

| ID | Title | File |
|----|--------|------|
| OPQ000 | ADR management process | [adr/OPQ000-adr-management-process.md](adr/OPQ000-adr-management-process.md) |
| OPQ001 | Opaque and ciphered value objects | [adr/OPQ001-opaque-and-ciphered-value-objects.md](adr/OPQ001-opaque-and-ciphered-value-objects.md) |
| OPQ002 | The Symfony Serializer bridge | [adr/OPQ002-symfony-serializer-bridge.md](adr/OPQ002-symfony-serializer-bridge.md) |
| OPQ003 | PER (PHP-FIG) coding style and naming | [adr/OPQ003-per-php-fig-coding-style.md](adr/OPQ003-per-php-fig-coding-style.md) |
| OPQ004 | Testing standards and the test pyramid | [adr/OPQ004-testing-standards-and-pyramid.md](adr/OPQ004-testing-standards-and-pyramid.md) |
| OPQ005 | Monorepo, splitsh, and satellite repositories | [adr/OPQ005-monorepo-splitsh-and-satellite-repositories.md](adr/OPQ005-monorepo-splitsh-and-satellite-repositories.md) |

OPQ001 generalises *HIVE004 — Opaque and Secret data objects*, written for a single application;
the record says where it departs from it and why.

## Working agreements (WA)

| ID | Title | File |
|----|--------|------|
| WA001 | English language for project documentation | [wa/WA001-english-language-documentation.md](wa/WA001-english-language-documentation.md) |
| WA002 | Test-driven development | [wa/WA002-test-driven-development.md](wa/WA002-test-driven-development.md) |
| WA003 | GitHub epics, tasks, and project tracking | [wa/WA003-github-epics-tasks-and-project-tracking.md](wa/WA003-github-epics-tasks-and-project-tracking.md) |
| WA004 | MIT license for the repository and Composer packages | [wa/WA004-mit-license-distribution.md](wa/WA004-mit-license-distribution.md) |
| WA006 | English is the working language | [wa/WA006-english-is-the-working-language.md](wa/WA006-english-is-the-working-language.md) |
| WA007 | The agentic loop and its ledgers | [wa/WA007-the-agentic-loop-and-its-ledgers.md](wa/WA007-the-agentic-loop-and-its-ledgers.md) |
| WA008 | Publishing a version | [wa/WA008-publishing-a-version.md](wa/WA008-publishing-a-version.md) |

WA005 belongs to the repository these agreements were taken from and has no counterpart here; the
number is left unused so the others keep theirs.

## Other documents

- [User guide](user/_index.md) — also rendered by `hugo-docs/`.
- [Work journal](journal/README.md).
- [`openspec/`](../openspec/) — behaviour specifications and change proposals.
