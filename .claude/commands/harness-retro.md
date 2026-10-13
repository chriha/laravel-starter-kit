---
allowed-tools: Read, Grep, Bash(git fetch:*), Bash(git show:*), Bash(git log:*), Bash(git grep:*), Bash(gh api:*), Bash(gh pr list:*), Bash(gh pr view:*), Bash(gh issue list:*), Bash(gh issue view:*), Bash(gh issue create:*), Bash(gh issue comment:*), Bash(gh label create:*), Bash(jq:*), Bash(date:*)
description: Measure the agent harness KPIs over a window, append a comment to the "Harness Metrics" issue and print the delta
---

Measure whether the harness works. **Read-only on code and git** — the only write is one comment appended to
the GitHub issue **"Harness Metrics"** (label `harness-metrics`; created on the first run).

Argument (optional): window in days, default `14`. `$FROM` = now − window, `$TO` = now (`date -u`).

An **agent PR** is a merged PR whose commits carry the `Co-Authored-By: Claude` trailer. Get candidates with
`gh pr list --state merged --limit 100 --json number,title,createdAt,mergedAt` (filter `mergedAt` in the
window), then confirm the trailer per PR (`gh pr view <n> --json commits -q '.commits[].messageBody'`).
A human-authored commit is one without that trailer.

## KPIs

1. **First-pass acceptance** — agent PRs merged with **no human-authored commit after the PR was opened**
   (`createdAt`), divided by all agent PRs in the window. Report `n/N` and the percentage.
2. **Review threads per PR** — human review threads per agent PR (`gh api repos/{owner}/{repo}/pulls/<n>/comments`
   plus review bodies, authored by a human, not the agent or a bot). Report the mean and the median.
3. **Lead time** — per agent PR, the linked issue's `ready` label event (`gh api repos/{owner}/{repo}/issues/<n>/timeline`,
   `labeled` event with `label.name == "ready"`) to `mergedAt`. Report the median and p90 in hours. Skip PRs without one.
4. **Outcome split** — for issues touched in the window, count `done / partial / blocked / planned /
   needs-info` from the `## Status`/`## Summary` of the body, or from the state label (`plan` = planned,
   `question` = needs-info). Open a ticket only when the list output doesn't show it.
5. **Finding precision** — `confirmed / (confirmed + duplicate + invalid)` over the verifier verdicts in the
   window. Verdicts are issue comments starting with `<!-- finding-verdict -->` on the source issue (written
   by the verifier, see `.claude/work-tickets/verifier.md`). Report the three counts and the ratio. No
   verdicts yet → `n/a`.
6. **Entropy ratchets on `origin/main`** (`git fetch origin main` first, read via `git show`/`git grep` — never
   the working tree):
   - PHPStan baseline entries: `git show origin/main:phpstan-baseline.neon | grep -c '^\s*message:'`.
   - Arch-test exclusions: `git grep -c -E '->ignoring[A-Za-z]*\(' origin/main -- tests/Architecture`, summed over files.

A KPI with no data in the window is `n/a`, never `0`.

## Record

1. Find the issue: `gh issue list --state all --label harness-metrics --json number`. Missing →
   `gh label create harness-metrics` and `gh issue create --label harness-metrics --title "Harness Metrics"`
   with a body naming the columns below.
2. Parse the **last** comment as the previous row (for the delta).
3. Append one comment with this row (header line included only on the first comment):

```
| date | window | agent PRs | first-pass | threads/PR | lead time p50/p90 (h) | done/partial/blocked/planned/needs-info | finding precision | phpstan baseline | arch exclusions |
```

4. Print the new row and a delta line per KPI against the previous row (`+n`, `-n` or `n/a`), then one line
   naming the largest regression, if any. First run: say there is no previous row.
