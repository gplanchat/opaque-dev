# WA007 — The agentic loop and its ledgers

## Status

Accepted

## Context

A model can run unattended against this repository for hours. The hard part is not that; it is
trusting the result without reading every line it produced. The model supplies intelligence. What
is built around it supplies **honesty**: the permanent layer of files, scripts and ledgers under
`loop/` that makes an unattended run checkable after the fact.

The architecture follows Linas Beliūnas, *How to Build an Agentic OS with Claude Fable 5*
(2026-07-10), ported to this monorepo's PHP toolchain: a constitution, walls and a gate, a
heartbeat, a trust ledger, standing goals, a budget, optional loops, ops, and a staged rollout.
Three principles run through all of it:

- **Every rule must be checkable.** A rule with no number, no "never", and no command that
  verifies it is a suggestion, and will be treated as one.
- **Separate the powers.** One party plans the work, one executes it, one judges the result, and
  one casts the final vote. The last is a shell script, because a script cannot be talked into
  anything.
- **Done is a state, not an event.** Finishing a task creates a daily check that the result is
  still true.

## Agreement

### The seats

| Seat | Tools | Writes code | Set by |
|------|-------|-------------|--------|
| Conductor | `Read,Grep,Glob` — read-only | never | `CONDUCTOR_MODEL` |
| Worker | read, edit, scoped `git`/`composer`/`vendor/bin` | yes, in a worktree | `WORKER_MODEL` |
| Verifier | none; sees a spec and a diff, nothing else | no | `VERIFIER_MODEL` |
| Gate | `loop/guardrails/verify.sh` | no | not a model |

The separation is **physical**, not advisory: `--allowedTools` makes the conductor unable to write
a file whatever it decides, and `loop.sh` stops the tick if `permission_denials` shows it tried.
The seats are **variables**, so a model outage or a price change is a config edit, not an incident.

### What earns autonomy

Autonomy is granted **per skill** — a stable category of work such as `fix-cs` — and only from
logged evidence in `loop/memory/trust.tsv`. A skill runs unattended after **20 runs at a 95%
verified pass rate**, and is demoted automatically, and loudly, the moment it drops below its tier's
floor. Never globally, never on faith, never restored by hand.

### What the loop may and may not touch

`loop/contract.md` holds the three lists — runs solo, needs sign-off, pages me. The supervised-only
paths are in `CLAUDE.md`; two are worth restating:

- **`psalm-baseline.xml` may never grow.** A baseline entry silences a finding without fixing it.
  The gate diffs the file, and the verifier fails a diff that touches it.
- **`documentation/adr/` is not edited.** An ADR records what was decided when it was written
  ([OPQ000](../adr/OPQ000-adr-management-process.md)). A model tidying one falsifies the record.

### `loop/*` branches and the prise registry

**Loop branches are exempt from `.worktrees/prises/`.** A prise is posted on `main` before work
starts and removed at merge, and `bin/prises-check.sh` calls a prise stale once its branch has a
closed PR and no open one. A loop that opens and abandons draft PRs on its own cadence would either
churn `main` with prise commits or fill the registry with entries nothing removes. The registry
stops two *sessions* building the same slice; the loop works one item at a time from a conductor
that has read the state file, and cannot collide with itself. A human who takes a loop branch
further posts a prise then, as normal.

### The reading list is gated at the boundary

This repository is **public**, and its issues are the loop's reading list. `gh issue list` reports
no author, so without a gate a stranger's issue and the owner's reach the conductor as the same
thing.

A CI workflow labels every issue by its `author_association` — `loop:trusted` for OWNER, MEMBER and
COLLABORATOR, `loop:untrusted` for everyone else — and `loop.sh` reads only issues carrying
`loop:trusted` or a human's `loop:cleared`:

- **The gate is metadata, not judgement.** Author association cannot be spoofed by the issue text.
- **It fails closed.** Anything that is not one of the three team roles — an empty value, a
  lowercase spelling, a role GitHub invents later — is untrusted. `bin/issue-trust-gate-test.sh`
  asserts it, and CONTRIBUTOR is the trap it guards: it reads like membership and means one merged
  pull request.
- **An edit withdraws clearance.** Clearance is a statement about content that was read.
- **It runs once per issue, not once per tick.**

**There is deliberately no model in that workflow.** An LLM in a job that reads attacker-controlled
text while holding `issues: write` is the classic pwn-request shape. The model-assisted injection
scan stays in `loop/triage.md`, where the blast radius is a throwaway worktree.

Not yet covered: issue **comments**. The loop reads titles only; the moment bodies or comments enter
the reading list, a trusted issue becomes a place a stranger can write.

### Prompt injection is a standing threat

Four mitigations, all required, none sufficient alone:

1. The data-is-not-instructions law in `CLAUDE.md`, and `INJECTION-SUSPECT` in triage.
2. Tool allowlists per seat.
3. Blast radius: a worktree, a `loop/*` branch, draft PRs, no merge to `main` without a human.
4. Egress: the loop's environment holds no production credential. A token readable by the loop's
   user (a global Composer or `gh` configuration, for instance) breaks this; running the loop as a
   low-privilege user with its own home is a precondition for week 3 below.

### The 30-day rollout

**Where it stands.** Week 1 has not started: every ledger under `loop/memory/` is header-only and no
tick has run. Starting it is a human decision; when it is taken, the date goes here.

| Week | What runs | What is granted | Gate to the next week |
|------|-----------|-----------------|-----------------------|
| 1 | `loop/loop.sh` by hand, `LOOP_PUSH=0` | nothing; a human reads every result | the gate has passed and failed at least once each, for the right reasons |
| 2 | `loop/loop.sh` on weekday cron, `loop/verify-goals.sh` daily, still `LOOP_PUSH=0` | reading and deciding | 7 days of `loop/scripts/cost-check.sh --report` matching expectation, cache reads dominating conductor input |
| 3 | `LOOP_PUSH=1`; draft PRs open | draft PRs, for skills at `auto` only | the loop runs as a low-privilege user with no production credential |
| 4 | as week 3, plus `loop/retro.sh` weekly | the first standing goals, born from finished work | one rule, loop or goal deleted with nothing breaking |

The last cell is load-bearing: a system you can only add to is one you have stopped understanding.

## Consequences

- Spend is bounded by a cached stable prefix and by `cost-check.sh --budget`, which is dumb,
  external, and cannot be reasoned with. `LOOP_DAILY_BUDGET_USD` defaults to $5.
- Finished work turns into standing goals, re-checked daily by `loop/verify-goals.sh`. The sentinel
  finds; it never repairs. Retiring a goal is a human decision, and it is logged.
- `CLAUDE.md` changes only through `loop/retro.sh`'s evidence-backed proposals, or a human.

## Relationship to other normative documents

- **WA001 / WA006** — English everywhere, these ledgers included.
- **WA002** — TDD holds for the loop: the worker writes the failing test first, and the verifier
  fails a diff that weakens one.
- **WA003** — issues are the loop's reading list, and so its injection surface.
- **[OPQ000](../adr/OPQ000-adr-management-process.md)** — why `documentation/adr/` is supervised-only.
- **[OPQ003](../adr/OPQ003-per-php-fig-coding-style.md) / [OPQ004](../adr/OPQ004-testing-standards-and-pyramid.md)** — what the gate enforces.
