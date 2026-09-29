---
allowed-tools: Read, Grep, Glob, Skill, Bash(php artisan test:*), Bash(grep:*), Bash(ls:*), Bash(cat:*), Bash(find:*), Bash(rm:*), Bash(mkdir:*), Bash(gh issue:*), Bash(gh label:*), mcp__codegraph__codegraph_explore
description: Detect architecture drift and tech debt, file it as planned tickets (GitHub issues) for triage, optionally hand them to /work-tickets
---

Run the architecture loop: **detect drift → cluster into tickets → plan tasks → hand over**. Tickets are
**GitHub issues in this repo** (`gh issue …`) in the shape of `.claude/work-tickets/tickets.md`, so
`/work-tickets` can work them like any other ticket. Every issue this command files carries the
**`arch-loop`** label so the loop can find its own work.

**Everything this command files lands in `triage` — never further.** The loop detects and plans; a **human**
reviews the `triage` issues on GitHub and accepts one by relabeling it `triage` → `ready`. `/work-tickets`
only works `ready`. (The one exception is `--handoff` — see Step 4.)

| Loop concept | GitHub | Set by |
|--------------|--------|--------|
| signal | nothing — collected in this run, clustered into tickets in Step 2 | this command |
| ticket (planned, awaiting review) | open, labels `arch-loop` + `triage`, has a `## Tasks` checklist | this command |
| ticket (dismissed) | closed as *not planned* | a human |
| ticket (accepted) | open, `ready` | **a human** (or `--handoff`) |
| ticket (PR open → merged) | `review` → closed | `/work-tickets` |

**This command only detects and plans. It never writes application code and never touches git.** It only
creates or updates issues. Implementing, PRs and closing tickets belong to `/work-tickets` — even with
`--handoff`, this command's own steps stay read-only on the code; `/work-tickets` then works under its own
rules.

Work the given scope **to completion**: keep scanning and planning until there is no new drift to file and
every filed ticket has its task checklist. Then stop and hand over.

Argument (optional): a **path** and/or the **`--handoff`** flag.
- **Path** (e.g. `/architecture-loop app/Http`) → scan that path only.
- **Omitted** → scan `app/`, `routes/`, `database/` and `tests/`.
- **`--handoff`** (e.g. `/architecture-loop app/Http --handoff`) → skip the human triage gate: after
  planning, relabel each filed ticket `triage` → `ready` and run `/work-tickets` on them (Step 4).

**Invariants** are `.claude/rules/*.md` — each rule's `paths:` frontmatter says which files it governs — plus
the arch tests in `tests/Architecture`.

**Do NOT create or enter a git worktree.** This command only reads code and writes issues.

## Step 0 — Load state

- Make sure the labels exist (no-op if they do):
  `gh label create arch-loop --color 5319e7 --description "Filed by /architecture-loop" --force`,
  `gh label create triage --color fbca04 --description "Awaiting human acceptance" --force`,
  `gh label create ready --color 0e8a16 --description "Ready for /work-tickets" --force`.
- **List existing loop issues so you dedupe rather than duplicate:**
  ```
  gh issue list --state all --label arch-loop --limit 200 --json number,title,state,stateReason,labels
  ```
  Open ones are tracked work; closed *not planned* ones were dismissed by a human — don't re-file those.
  Also check non-loop tickets touching the same code with a keyword search
  (`gh issue list --state open --search "<keywords>" --json number,title,labels`) before filing.

## Step 1 — Scan (collect signals)

Audit the scope against the invariants. Cheapest-first sources of drift:

- **Arch tests** — `php artisan test --compact tests/Architecture`. A failure is a signal, verbatim.
- **Rule drift** — for each `.claude/rules/*.md` whose `paths:` overlap the scope, check the code obeys it
  (e.g. actions not `final readonly`, controllers not single-action, `$casts` property instead of
  `casts()`, commands injecting via constructor instead of `handle()`). Legacy code predating a rule
  counts too.
- **codegraph** (`codegraph_explore`) for structure/dependency questions grep can't answer (layering,
  cross-layer `use`, dynamic dispatch).
- **phpstan-baseline burndown** — entries in `phpstan-baseline.neon` whose code is now fixable, so the
  ignore can be deleted. Not the Larastan `$model->pivot` entries (see `implementer.md`).
- **Dead markers** — `@todo` / `TODO` / `FIXME`, commented-out arch rules in `tests/Architecture`, unused
  code, dead traits.

Keep each signal in context as: one-line symptom, evidence (`file:line`, arch-test name, command output),
the invariant at risk (`.claude/rules/*.md` or arch test). Drop anything matching an existing loop issue or
an open ticket from Step 0.

## Step 2 — Triage (signals → tickets)

- **Noise / false positive / intended** → drop it; list it in the report with a one-line reason.
- **Real signals → tickets.** Cluster related signals (same rule, same area) into one ticket, each small and
  single-themed so one PR can fix it.

## Step 3 — Plan & file (tickets → tasks → issues)

For **every** ticket, break the work into a `## Tasks` checklist — each item a `- [ ]` line stating the
concrete step, the files it touches, and a **Done when:** condition. Where a rule's arch test is missing or
commented out and the fix makes it satisfiable, add a task to enable it — that's the durable win.

Then file it: write the body to `.user/issue-new.md` (`mkdir -p .user`), run
`gh issue create --label arch-loop --label triage --title "<one-line symptom>" --body-file .user/issue-new.md`,
then `rm` the file. Body, matching `.claude/work-tickets/tickets.md`:
```
**Type:** Refactor · **Area:** `path`, `path`

## Problem
<what drift was seen, and why it matters — which invariant is at risk, linking `.claude/rules/…`>

### Evidence
- `file:line` — <what's there vs. what the rule says>

## Tasks
- [ ] <step> — files: `path` — **Done when:** <condition>

## Reference
- Found by `/architecture-loop` (<scope>)
- `.claude/rules/<rule>.md`
```
`**Area:**` must list every code path the ticket touches — `/work-tickets` uses it to detect overlapping
tickets. Use `**Type:** Bug` instead if the drift is an actual behaviour bug, `Test` for a missing test.

The ticket stays in `triage`. One that needs more than 5 files is fine: `/work-tickets` switches it to
planning mode automatically.

## Step 4 — Hand over & report

Summarize concisely, issue number + one line each, not full bodies:
- scope scanned; signals found (dropped ones with reason); tickets filed (with task count).

**Without `--handoff`** (default): stop here. End with: **"Filed in `triage`. Review the `arch-loop` +
`triage` issues on GitHub and relabel accepted ones `triage` → `ready` (close the rest as not planned) —
`/work-tickets` then picks them up."**

**With `--handoff`:** relabel every ticket filed in this run `triage` → `ready`
(`gh issue edit NNN --remove-label triage --add-label ready`), then invoke `/work-tickets` via the `Skill`
tool with those issue numbers, space-separated (e.g. `104 105 107`). `/work-tickets` takes over from
there — it triages, runs the implementers, opens the PRs, and reports.
