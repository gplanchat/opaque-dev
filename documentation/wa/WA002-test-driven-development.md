# WA002 — Test-driven development (TDD)

## Status

Accepted

## Context

Opaque's whole promise is negative: a value must *not* leak. A property like that is only as good
as the tests that try to break it. **Test-driven development** turns each requirement into an
executable specification, keeps refactors safe, and leaves no untested production path. This WA
defines **how** we develop; **[OPQ004](../adr/OPQ004-testing-standards-and-pyramid.md)** defines what good tests look like and how they are layered.

## Agreement

### The TDD cycle (mandatory)

For **behaviour-changing** work on production code:

1. **Red** — add or extend an automated test that fails and expresses the next desired behaviour.
2. **Green** — write the minimum production code that makes it pass.
3. **Refactor** — improve structure and names with every relevant test still passing.

Repeat. Do not skip **Red** for new behaviour.

### Nomenclature and test shape

- **Test names** state the observable behaviour or rule under test, not private implementation
  details. Follow PHPUnit and PER naming (**[OPQ003](../adr/OPQ003-per-php-fig-coding-style.md)**, **[OPQ004](../adr/OPQ004-testing-standards-and-pyramid.md)**).
- **One primary intent per test method**; if a name needs "and", split it.
- **Arrange → Act → Assert** (Given → When → Then), with names from the domain language.

### Rules

- **No new production behaviour** without a failing test written (or extended) first.
- No speculative production code; generalise in **Refactor**, on green.
- Refactoring happens only on green, and the suite stays green after each step.

### Out of scope

- Documentation-only or comment-only edits.
- Purely mechanical changes (a rename with no behaviour change) whose tests are updated in the same
  change.
- Time-boxed spikes, whose code is replaced by a TDD-led implementation before merge unless a
  recorded exception (ADR or issue) applies.

### Code review

Reviewers treat this WA as a first-class check alongside the ADRs.

| Finding | Severity |
|--------|----------|
| Production behaviour added or changed **without** tests that encode it | **Blocking** — tests first, or a documented exception |
| Tests present but unclear intent, weak names, oversized methods | Non-blocking — suggest a refactor per [OPQ004](../adr/OPQ004-testing-standards-and-pyramid.md) |
| Unrelated refactors bundled with a behaviour change | Non-blocking — suggest smaller follow-ups |

Use **Conventional Comments** (`issue (blocking):`, `suggestion:`). Self-review against the same
checks before asking for a human review.

## Consequences

- Review may reject an increment that changes production behaviour without an evident
  Red → Green sequence.
- The PHPUnit suite in CI is the safety net for **Refactor**.

## References

- [OPQ003](../adr/OPQ003-per-php-fig-coding-style.md) — PER coding style
- [OPQ004](../adr/OPQ004-testing-standards-and-pyramid.md) — Testing standards and test pyramid
- [WA001 — English language for project documentation](WA001-english-language-documentation.md)
- [documentation/INDEX.md](../INDEX.md)
