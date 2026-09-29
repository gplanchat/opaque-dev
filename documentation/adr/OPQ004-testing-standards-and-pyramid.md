# OPQ004 — Testing standards and the test pyramid

## Status

Accepted

## Context

This component exists to stop leaks. A leak is a behaviour — a string that appears where it must
not — so the only credible evidence that a channel is closed is a test that opens it and looks.

## Decision

### Framework

PHPUnit 11, strict: risky tests, warnings, notices and deprecations fail the run; coverage
metadata (`#[CoversClass]`, `#[CoversFunction]`) is mandatory; CI passes `--fail-on-skipped`.

### Every closed channel has its test

For each channel OPQ001 claims to close, one test produces the channel's output and asserts the
secret is absent from it. A new channel discovered later gets its failing test first (WA002).

### Every wire format has two tests

A type that crosses a journal (`Ciphered`) gets a **round-trip** test and a test of the **exact
shape** the current wire version writes. The second one is what fails when a change would make
rows already written unreadable.

### Tests are deterministic

No real time, no network. Randomness is only asserted on as a property (two nonces differ), never
on as a value.

### Static analysis

PHPStan at **level 8** and Psalm at **error level 2**, on `src/` and `tests/`, **without a
baseline**: new code meets the level. An `ignoreErrors` entry carries a comment saying why the
finding is wrong, not why it is inconvenient.

### The pyramid

- **Unit** (`tests/unit`) — the base, and today all of it: value objects, ciphers, the keyring, the
  normalizer inside a real `Serializer`. Fast, no I/O.
- **Integration** — added when a test needs a host: the bridge inside a Symfony kernel, or inside
  gplanchat/durable's journal.
- **End-to-end** — not planned; the component has no process of its own.

A layer gets its PHPUnit suite the day its first test lands, not before.

## Consequences

- `composer test` is the whole suite; `loop/guardrails/verify.sh` runs it with the static analysis
  and the style check, as CI does.
- A test is never weakened, skipped or deleted to get to green (`CLAUDE.md`).
