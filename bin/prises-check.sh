#!/usr/bin/env bash
#
# Check of the claim registry: `.worktrees/prises/<branch>.md`.
#
# A registry that lies without anything turning red is worse than none. This script is what turns
# it red.
#
# **The criterion is the PR, not the branch.** Comparing claims to live remote branches turns red
# on the normal case, since a claim is placed *before* the branch exists. A check that turns red on
# the normal case gets disarmed within the week — a dead check plus the belief that it watches
# something.
#
# **The PR is not enough either.** A branch outlives its PR: a change that advances slice by slice
# reopens the same branch for the next one, and between the two it only has closed PRs. So the
# verdict asks for both: no live PR left, **and** nothing left to merge.
#
# **Both are still not enough** for a reused change branch: between the merge of one slice and the
# first commit of the next, the PRs are all closed, the branch has nothing beyond `main` — GitHub
# may even have deleted it — and the work goes on. The window opens at **every** slice boundary.
# So the change is authoritative before the branch: for a claim `change/<name>`, an unchecked task
# in `openspec/changes/<name>/tasks.md` is enough to keep it alive.
#
#   | PRs of the branch | change / branch                 | verdict                                  |
#   |-------------------|---------------------------------|------------------------------------------|
#   | none              | —                               | normal — the claim precedes the PR        |
#   | one open          | —                               | normal — work in progress                 |
#   | closed only       | change with tasks left          | normal — between two slices               |
#   | closed only       | branch ahead of `main`          | normal — reused, next slice               |
#   | closed only       | branch missing                  | **stale** — the branch was deleted        |
#   | closed only       | nothing beyond `main`           | **stale** — the removal was forgotten     |
#
# `--release` deletes the claim files the check has just proven merged: closed PRs and a branch
# with nothing beyond `main`. A missing branch proves nothing about where its commits went, so that
# claim stays reported. It is the one unattended write CLAUDE.md allows under the registry;
# committing the deletion is the caller's job.
#
# Usage: bin/prises-check.sh [--release] [repository]      (default: gplanchat/opaque-dev)
set -uo pipefail

RELEASE=0
[ "${1:-}" = --release ] && { RELEASE=1; shift; }
REPO="${1:-gplanchat/opaque-dev}"
OWNER="${REPO%%/*}"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PRISES="$ROOT/.worktrees/prises"

if [ ! -d "$PRISES" ]; then
    echo "::error::$PRISES does not exist — the registry is gone or the script is misplaced"
    exit 1
fi

stale=0
malformed=0
live=0

while IFS= read -r file; do
    branch="${file#"$PRISES"/}"
    branch="${branch%.md}"

    # The path *is* the branch name: a title that says otherwise makes the registry unreadable
    # for whoever reads it by eye rather than with this script.
    title="$(head -n1 "$file" | sed 's/^# *//')"
    if [ "$title" != "$branch" ]; then
        echo "::error file=.worktrees/prises/$branch.md::the title says \"$title\", the path says \"$branch\""
        malformed=$((malformed + 1))
        continue
    fi

    answer="$(gh api "repos/$REPO/pulls?head=$OWNER:$branch&state=all&per_page=100" --jq '.[].state' 2>&1)"
    if [ $? -ne 0 ]; then
        # A check that passes when it could not verify checks nothing.
        echo "::error::cannot query the PRs of \"$branch\": $answer"
        exit 1
    fi

    if [ -z "$answer" ]; then
        live=$((live + 1))
        echo "  ok        $branch — no PR, the claim precedes the work"
        continue
    fi

    if grep -qx 'open' <<<"$answer"; then
        live=$((live + 1))
        echo "  ok        $branch — open PR"
        continue
    fi

    # The change is authoritative before the branch, and this is the heart of this check.
    #
    # A `change/<name>` branch lives through several slices. Between the merge of one and the
    # first commit of the next, the three conditions of the "stale" verdict are met — no open PR
    # left, nothing ahead of `main`, the branch sometimes even deleted — while the work goes on.
    # The window is not rare: it opens at **every** slice boundary.
    #
    # The registry does not have to guess: the state of the change is written in its `tasks.md`.
    # An unchecked task is a claim to keep, whatever the branch says.
    if [[ "$branch" == change/* ]]; then
        tasks="$ROOT/openspec/changes/${branch#change/}/tasks.md"
        if [ -f "$tasks" ] && grep -q '^- \[ \]' "$tasks"; then
            left="$(grep -c '^- \[ \]' "$tasks")"
            live=$((live + 1))
            echo "  ok        $branch — PR closed, but the change has $left task(s) left"
            continue
        fi
    fi

    # No live PR left. Remains the second question, the one that was missing: does the branch
    # still have something to give? `ahead_by` counts what it carries that `main` does not. A
    # missing branch gives a 404, and that is a verdict, not an outage.
    ahead="$(gh api "repos/$REPO/compare/main...$branch" --jq '.ahead_by' 2>/dev/null)"

    if [ -n "$ahead" ] && [ "$ahead" -gt 0 ] 2>/dev/null; then
        live=$((live + 1))
        echo "  ok        $branch — PR closed, but $ahead unmerged commit(s): reused branch"
        continue
    fi

    numbers="$(gh api "repos/$REPO/pulls?head=$OWNER:$branch&state=all&per_page=100" --jq '[.[] | "#\(.number)"] | join(", ")')"
    if [ -z "$ahead" ]; then
        reason="the branch no longer exists on the remote"
    elif [ "$RELEASE" -eq 1 ]; then
        rm -- "$file"
        echo "  released  $branch — $numbers closed, and the branch has nothing \`main\` does not already have"
        continue
    else
        reason="$numbers closed, and the branch has nothing \`main\` does not already have"
    fi
    echo "::error file=.worktrees/prises/$branch.md::stale claim — $reason. Removing its claim is part of the merge."
    stale=$((stale + 1))
done < <(find "$PRISES" -name '*.md' | sort)

echo
echo "registry: $live claim(s) in progress, $stale stale, $malformed malformed"

[ $((stale + malformed)) -eq 0 ]
