---
allowed-tools: Read, Write, Edit, Grep, Glob, Agent, SendMessage, Bash(ls:*), Bash(cat:*), Bash(grep:*), Bash(find:*), Bash(mv:*), Bash(rm:*), Bash(mkdir:*), Bash(cp:*), Bash(ln:*), Bash(cd:*), Bash(git status:*), Bash(git diff:*), Bash(git log:*), Bash(git show:*), Bash(git fetch:*), Bash(git worktree:*), Bash(git branch:*), Bash(git -C:*), Bash(gh pr:*), Bash(gh api:*), Bash(gh issue:*), Bash(php artisan test:*), Bash(npm run:*), Bash(composer lint:*), Bash(composer test:*), Bash(composer install:*), mcp__codegraph__codegraph_explore
description: Pick up ready tickets (GitHub issues), have sub-agents fix each one in its own worktree and open a PR (or plan it, if big), turn verified side findings into new issues, and file tickets through review to closed
---

Work the ticket queue. **Tickets are GitHub issues in this repo** (`gh issue …`); their state is a label:

| State | Holds | Who changes it |
|-------|-------|----------------|
| open, `ready` | ready tickets — **the only ones this command works** | this command → `review`, `question`, `plan` |
| open, `question` | open questions — need a human decision first | `/answer-tickets NNN` → `ready` |
| open, `plan` | big tickets (> 5 files) with a drafted plan + questions | `/plan-tickets [NNN]` → `ready` |
| `review` | implemented, PR open (`**PR:**` link on the metadata line) | this command, once the PR is merged → closed |
| closed | PR merged, all tasks `- [x]` + a `## Summary` | — |

**Quality bar: the PR must pass CI.** Every change is covered by green Pest tests, and every gate of
`.github/workflows/tests.yml` (`composer test`) passes locally before the PR is opened or marked ready: type
coverage of 100 % (`--min=100`), lint (Composer validate, Pint, PHPCS, Rector, Prettier) and static
analysis (PHPStan/Larastan) — plus the full Pest suite (CI runs it via `test:unit` with exactly 100 % coverage). Implementers run them and
fix what fails (see *CI gates* in `implementer.md`).
Style/lint/static-analysis issues are fixed in the ticket that causes them — never reported as findings
or tickets.

Ticket shape and issue-editing rules live in `.claude/work-tickets/tickets.md` (for sub-agents).

Paths below: `NNN` is the issue number, `<slug>` a short kebab-case slug of its title (≤ 5 words, chosen by
the triager), `<repo>` the main checkout (absolute path), `<wt>` a ticket's worktree
`<repo>/.user/worktrees/NNN-<slug>`, `<report>` a ticket's run report `<repo>/.user/reports/NNN-<slug>.md`.
`.user/` is gitignored, so reports, temp body files and worktrees never end up in a commit.

## The orchestrator stays thin

**Context is the budget.** A run can work many tickets, and everything the orchestrator reads stays in its
context until the run ends. So the orchestrator **only routes**: it hands out issue numbers, spawns
sub-agents, and passes short status lines between them. All real work happens in sub-agents:

| Agent | Model | Does | Returns |
|-------|-------|------|---------|
| **triager** | haiku | Step 1: classify, relabel, overlap check | the plan block |
| **implementer** | default | Step 2: size check, then worktree setup, code, commits, push, PR, file the ticket — or a plan | one line |
| **verifier** | haiku | Step 3b: verify one finding, create an issue if real | one line |

Each agent's brief is a file in `<repo>/.claude/work-tickets/`: `triager.md`, `implementer.md`,
`verifier.md`, plus the shared `tickets.md` (ticket shape, issue editing) and `git-rules.md`.

Orchestrator rules:
- **Never** read issues, reports, diffs, test output, agent transcripts or agent output files yourself.
  Don't re-run checks a sub-agent already ran. If you need a fact, ask a sub-agent for it in one line.
- Full details go in **files** (`<report>`) or the issue, never in agent replies. Every agent prompt must
  say: *"Your final reply must be only the RESULT block below — no preamble, no recap."*
- Agent prompts point at issues by number and files by path instead of pasting their content. **Don't
  read the brief files and don't paste them.** A prompt holds only the concrete values the step lists
  (numbers, `<wt>`, branch, `<report>`, …) and the line *"Read and follow `<repo>/.claude/work-tickets/<role>.md`
  and `tickets.md`"* (+ `git-rules.md` for the implementer), with absolute paths.
- All sub-agents run with `run_in_background: true`; wait for notifications, don't poll.

Argument (optional):
- **Issue numbers** (e.g. `/work-tickets 104 107`) → work exactly those tickets, in parallel.
- **Chains** (e.g. `/work-tickets 104>107>110 112`) → `>` joins tickets into a chain that is worked
  **one after another**, each **stacked** on the previous one's branch (see *Chains*). Space-separated
  groups (a chain or a single number) still run in parallel. Without `>`, everything stays parallel.
- **Omitted** → work **all** eligible tickets (see Step 1), lowest number first. Blocked tickets are skipped
  and reported, without asking.
- **`--dry-run`** → do Steps 0–1 only (including syncing `review` and relabeling open questions): print the
  plan (including the triager's suggested fixes for blocked tickets) and stop. Don't ask.

### Chains

A chain `A>B>C` works `B` only after `A` is done, and cuts `B`'s branch from `A`'s branch instead of
`origin/main`, so `B` sees `A`'s code before it's merged. `B`'s PR targets `A`'s branch (`--base
ticket/A-<slug>`), so each PR shows only its own changes. A ticket in `review` (PR open, not merged) can
**head** a chain: it isn't worked again, it's only the base for the next ticket. Once a base PR is merged,
Step 0 moves the stacked PR onto `main`.

- A chain holds **one implementer slot** at a time.
- The next ticket starts only after the previous one's implementer returns `done` (or it's the `review` head).
  If a chain ticket ends `partial`, `blocked` or `planned`, **the chain stops**: the rest isn't
  started and is reported as *chain stopped at #NNN*.
- Tickets in the same chain never block or hold each other.

## Step 0 — Sync state (orchestrator, shell only)

The orchestrator runs this itself. Every command prints a line or two, so it costs little context. Don't
read issue bodies here.

- `mkdir -p .user/worktrees .user/reports` and `git fetch origin --prune`.
- **Sync `review`** — list the tickets with their PR url:
  ```
  gh issue list --state all --label review --limit 200 --json number,title,state,body \
    --jq '.[] | "\(.number) | \(.state) | \((.body | capture("\\*\\*PR:\\*\\* (?<u>\\S+)").u) // "-") | \(.title)"'
  ```
  then `gh pr view <url> --json state -q .state` for each:
  - **MERGED** → the PR's `Closes #NNN` already closed the issue on merge. If it's still open (e.g. the PR
    said `Refs`), `gh issue close NNN --reason completed`. Remove the `review` label: it marks the ticket as
    still needing cleanup. Clean up: `git worktree remove <wt>` (add `--force` only if `git -C <wt> status`
    shows nothing but ignored files), `git branch -D ticket/NNN-<slug>`, and `rm` its `<report>`. Find all
    three by their `NNN-` prefix (`git worktree list`, `ls .user/reports`).
  - **CLOSED** (not merged) → reopen the issue if it was closed and relabel `review` → `ready`. Remove its
    worktree and local branch. Note it for the triager, which adds the `## Progress` note. The ticket is
    picked up again as a fresh one.
  - **OPEN** → leave it.
- **Unstack PRs whose base was merged** — list open PRs that don't target `main`:
  ```
  gh pr list --state open --limit 100 --json url,headRefName,baseRefName \
    --jq '.[] | select(.baseRefName != "main") | "\(.url) \(.headRefName) \(.baseRefName)"'
  ```
  For each, `gh pr list --head <base> --state all --json state -q '.[0].state'`:
  - **MERGED** → in the ticket's worktree (`git worktree list`, by its `NNN-` prefix):
    `git -C <wt> merge origin/main --no-edit`. Clean → `git -C <wt> push`, then
    `gh api -X PATCH repos/{owner}/{repo}/pulls/<PR number> -f base=main` (not `gh pr edit --base` — it fails on the Projects-classic GraphQL deprecation). Conflict → `git -C <wt> merge --abort` and keep it as
    `UNSTACK_CONFLICT: NNN <url>` for the report. No worktree → keep it as `UNSTACK_CONFLICT` too.
  - **CLOSED** (base dropped) → keep it as `UNSTACK_CONFLICT: NNN <url> (base closed)`.
  - **OPEN** → leave it.
- Refresh the **read-only reference worktree** `.user/worktrees/_main` (used by verifiers). If it exists:
  `git -C .user/worktrees/_main checkout --detach origin/main`. If it's missing:
  `git worktree add --detach .user/worktrees/_main origin/main`, then set up its environment:
  `cp -cR vendor .user/worktrees/_main/vendor`, `ln -s <repo>/node_modules .user/worktrees/_main/node_modules`,
  `cp .env .user/worktrees/_main/.env`, `cp -R public/build .user/worktrees/_main/public/build`. Implementers explore it read-only, so it must exist before Step 2.
- Keep `MERGED` and `REOPENED` as `NNN title` lines for the report.

## Step 1 — Triage (one triager agent)

Spawn one **triager** (`Agent`, `subagent_type: "general-purpose"`, `model: "haiku"`) with `<repo>`, the
argument (issue numbers and chains, verbatim, if any) and the `REOPENED` numbers from Step 0. Wait for it.
Everything else depends on its plan.

### Orchestrator after triage

Keep `BASE_SHA`. Print the plan: Step 0's `MERGED`/`REOPENED` plus the triager's block, lightly formatted. With `--dry-run`, stop here.

### Blocked argument tickets — ask instead of dropping

Only when the argument named tickets (never for a no-argument run). For every `BLOCKED` line that carries
a `fix:` or `partial:` chain, ask the user with `AskUserQuestion` (one question per blocked ticket, up to 4
per call; header `#NNN`). Question: *"#NNN is blocked by <blockers with state>. Work the blockers first?"*
Options, only those that apply:
- **"Blockers, then #NNN"** (first, `(Recommended)`) — description: the `fix:` chain, e.g. `98>104>107`.
- **"Blockers only"** — the `fix:` chain without `NNN` (or the `partial:` chain when there is no `fix:`).
- **"Skip #NNN"** — leave it blocked.

`BLOCKED` lines with `fix: none` and no `partial:` aren't asked — they go to the report with their reason
(e.g. *#MMM needs `/answer-tickets MMM`*).

If any answer adds work: build the new argument — the original argument with each blocked ticket replaced
by the chosen chain (a `review` head stays at the front; merge chains that share tickets so every ticket
appears once, blockers before the tickets that need them) — and spawn a **fresh triager** with it (no
`REOPENED` this time). Its block replaces the first one; print it. Don't ask again for tickets the user
already answered — a ticket that is still `BLOCKED` goes to the report.

## Step 2 — Implement (one implementer per ticket)

Run **up to 3 implementers in parallel** (`Agent`, `subagent_type: "general-purpose"`), lowest issue number
first. When one finishes, check it (Step 3) and start the next eligible ticket, until all are done.
A chain takes one slot: only its first not-yet-worked ticket is eligible, and the next one becomes
eligible when the previous implementer returns `done` (see *Chains*).

Implementer prompt: the issue number, `<wt>`, branch `ticket/NNN-<slug>`, `<repo>`, `<report>`, the numbers
of closed issues it references (it reads their `## Summary` itself), its PR url if it continues, and
`<base>` from the triage row (`main`, or the branch it's stacked on). Stacked: also the base ticket's
worktree `<base-wt>` and its PR url.

## Step 3 — Check planned tickets (orchestrator)

Implementers file their own tickets; there is no review step. For `planned` results the orchestrator
checks the plan itself, running these for each one:
```
gh issue view NNN --json labels,body --jq '"labels: \([.labels[].name] | join(",")) | pending: \(.body | test("\\*\\*Plan:\\*\\* pending")) | sections: \(.body | test("## Plan") and test("### Steps") and test("### Files") and test("## Questions")) | files: \((.body | capture("\\*\\*Estimated files:\\*\\* (?<n>\\d+)").n) // "?")"'
gh pr list --head ticket/NNN-<slug> --state all --json url -q length
git -C <repo> branch --list 'ticket/NNN-*'
```
It passes when the labels include `plan`, `pending` and `sections` are both `true`, files is more than 5,
the PR count is `0` and no branch is listed. If it passes, `rm <report>`. If it fails, `SendMessage` the
implementer once with what failed and check again. If it still fails, report it as a process violation.

## Step 3b — Verify findings (one verifier per finding)

For each finding title in an implementer's RESULT line, skip it if its title obviously matches one already
sent to a verifier this run; otherwise spawn a **verifier** (`Agent`, `subagent_type: "general-purpose"`,
`model: "haiku"`). Verifiers are read-only on the code, so they run in parallel with everything else and
don't count against the 3-implementer limit — don't wait for them. Keep a list of finding titles in flight.

Verifier prompt: the finding's short title, the `<report>` path it comes from (full text is under
`## Findings`), the source issue number and `<wt>`, and the titles of findings already in flight this run.

## Step 5 — Report

Wait for every implementer and verifier. New actionable issues are **not** worked in this run — the next
`/work-tickets` picks them up, so a run can't chase its own findings forever.

Build the report **only from the RESULT lines, Step 0's lists and the triage block**. Don't open issues or
files. Concise, issue number + one line each:

- **Merged → closed:** Step 0's `MERGED` (and `REOPENED`).
- **Ready for review:** implementer `done` → review, with PR url.
- **Partial / blocked / held:** implementer `partial` / `blocked`, triage `BLOCKED` / `HELD` (with the user's choice, if
  asked), with draft PR url where there is one. Include any process-violation notes.
- **Stacked:** each chain as `A → B → C` with PR urls, *chain stopped at #NNN* where it stopped. Note:
  merge stacked PRs **in chain order**; the next run moves each one onto `main` once its base is merged.
  Plus Step 0's `UNSTACK_CONFLICT` lines (need a manual merge of `main`).
- **Needs planning:** implementer `planned` results. Next step: `/plan-tickets NNN`.
- **Open questions:** triage `OPEN_QUESTIONS` plus implementer-filed numbers. Next step: `/answer-tickets NNN`.
- **New findings:** verifier `confirmed` issues (number + url), plus a one-line tally of duplicate/invalid.
- Worktrees still around: one `git worktree list`.

End with: **"PRs are open against `main` (base `<base-sha>`) — review and merge on GitHub; the next
`/work-tickets` run closes merged tickets and cleans up their worktrees."**
