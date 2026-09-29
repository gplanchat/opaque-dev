# Claims in progress

Several sessions can share this checkout. A slice is claimed by **creating its file** in `prises/`,
**before** starting, and the file is deleted at merge. Without a claim, two sessions can build the
same slice twice without either noticing.

The file's path **is** the branch name: `prises/test/serializer-round-trip.md` for the branch
`test/serializer-round-trip`. Nothing to choose, nothing to write twice.

## Why a file and not a line

A single table of claims is a magnet for conflicts: two sessions never write the same claim, but git
only sees one file, and two neighbouring lines are enough to make a rebase stop. One file per claim
removes the conflict by construction: adding a claim creates a file, releasing it deletes it. Two
sessions only collide if they take **the same branch** — and then git stops on an add/add conflict,
which is exactly the service the registry is expected to give.

## What the mechanism does not do on its own

**A claim is pushed to `main` before starting, and released there when closing.** A file created in
a worktree exists only there: nobody sees it before the merge, that is, once the work is done. The
registry only prevents a collision if it is read **and written** on `main`.

Sending a claim through a PR makes it visible only after that PR merges, several minutes later —
precisely the delay the registry exists to remove. Whether the owner may push a claim directly
depends on `main`'s branch protection; a session that forbids itself the direct push should know
what it trades for what.

A claim that is not released lies for as long as it stays, and a stale registry is worse than an
empty one: it makes you give up a free slice. **Releasing your claim is part of the merge**, just
like deleting your branch and removing your worktree.

## The check, and what it does not cover

`bin/prises-check.sh` catches stale claims. It runs on every PR that touches the registry, and once
a day for the rest — a forgotten release shows up in no PR, by definition.

**The criterion is the PR, not the branch.** A claim is placed *before* the branch exists on the
remote: comparing it to live branches would turn it red on the normal case, and a check that turns
red on the normal case gets disarmed within the week. A claim is stale when its branch has at least
one closed PR, **no** open PR, and nothing left to merge.

It also checks that the file's title names the same branch as its path, because the registry is
read by eye as much as by a script.

**What it does not see:** a live claim deleted by mistake, for instance swept away by resolving a
rebase mechanically. Nothing turns red. If you delete a claim, look at `main` when closing rather
than assume it.

## The shape of a claim file

```markdown
# <branch>

- **Scope**: what is being done, in one line
- **Entries**: the files or directories touched
- **State**: in progress | in review
```

## Reading the registry

The path carries the branch's slash, so one directory per prefix (`change/`, `docs/`, `fix/`…).
`find` gives the overview:

```
find .worktrees/prises -name '*.md'
```
