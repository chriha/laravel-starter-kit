---
allowed-tools: Read, Write, Edit, Grep, Glob, Skill, Bash(php artisan test:*), Bash(grep:*), Bash(ls:*), Bash(cat:*), Bash(find:*), Bash(mkdir:*), Bash(rm:*), Bash(gh issue:*), Bash(gh label:*), mcp__codegraph__codegraph_explore
description: Detect architecture drift — file signals, triage into tickets, write tasks, then hand tickets to /work-tickets
---

Run the architecture loop. **Signals and tickets are GitHub issues in this repo** (`gh issue …`), every one
carrying the **`arch-loop`** label so the loop can find its own work. Their shape and the issue-editing rules
follow `.claude/work-tickets/tickets.md`.

The **Domains registry** (audit scope + invariants per area) is `.claude/architecture-loop/domains.md`.
The pipeline is `signal → ticket → PR → closed`.

**Everything this command creates is labeled `triage` — never `ready`.** The loop detects and plans; a
**human** curates the triage inbox on GitHub and swaps `triage` → `ready` on accepted work. `/work-tickets`
is the only command that implements tickets, and it only ever works `ready` issues. (The one exception is
`--handoff` — see Step 4.) The mapping:

| Loop concept | GitHub issue | Set by | Notes |
|--------------|--------------|--------|-------|
| signal (open) | open, labels `arch-loop` + `triage`, `**Type:** Signal` | this command | one per observed drift |
| signal (dismissed) | closed as *not planned* | this command | + one-line reason comment (`Duplicate of #NNN` for duplicates) |
| ticket (planned, awaiting review) | open, labels `arch-loop` + `triage`, `**Type:** Refactor` | this command | has `## Problem` + `## Tasks` |
| ticket (accepted / ready) | open, labels `arch-loop` + `ready` | **a human** (or `--handoff`) | this is what `/work-tickets` picks up |
| ticket (PR open → merged) | `review` → closed | `/work-tickets` | no loop agent sets these |
| outcome | the closed issue's `## Summary` | the `/work-tickets` implementer | the closed ticket is the decision record |

**Severity is capped at medium.** Arch-loop tickets are tech debt / refactoring, never bugs: put
`**Severity:** low` or `**Severity:** medium` on the metadata line (signal severity `high` → `medium`). Never
add the `bug` label to an `arch-loop` issue.

**This command only detects and plans. It never writes application code, never closes tickets other than
dismissed signals, never adds `ready` (except `--handoff`), and never touches git or PRs.** It creates/edits
issues and, when it discovers a new area worth tracking, may add a domain to
`.claude/architecture-loop/domains.md`.

**Hard prohibition — no git, no PR, ever.** Never run `git add`/`commit`/`push`/`switch -c`, never call
`gh pr`. Issue writes are allowed; git and PRs are not. If a ticket seems to need git or a PR, that's a sign
you've drifted past this command's job — stop and leave it to `/work-tickets`. (`--handoff` invokes
`/work-tickets`, which follows its own rules.) Temp body files go to `.user/` (gitignored) and are removed
after use.

Work the given scope **to completion**: keep scanning, triaging, and planning until there is no new drift to
file and every planned ticket has its tasks. Then stop and hand over.

First argument (optional): a **domain slug** OR a **bare path**. A trailing **`--handoff`** flag may follow
either.
- **Slug** (e.g. `/architecture-loop actions`) → a domain in the registry; use its `owns_paths` +
  `invariants`.
- **Path** (e.g. `/architecture-loop app/Services`) → an ad-hoc scan of that path, no domain entry needed.
- **Omitted** → scan **all** `active` domains in the registry.
- **`--handoff`** (e.g. `/architecture-loop actions --handoff`) → the explicit "skip the human triage gate"
  path: after planning, relabel each planned ticket `triage → ready` and run `/work-tickets` on them (see
  Step 4). Omitted by default — the loop leaves everything in `triage` for a human unless this is passed.

**Agent assumptions:** all tools work; only call a tool with a clear purpose; no exploratory calls.

**Do NOT create or enter a git worktree.** This command only reads code and writes issues — there is
nothing to isolate and nothing to commit.

## Step 0 — Load state

- **Read the registry** `.claude/architecture-loop/domains.md`.
- **Make sure the labels exist** (idempotent):
  `gh label create arch-loop --color 5319e7 --description "Architecture loop signal/ticket" --force`,
  `gh label create triage --color fbca04 --description "Awaiting human review" --force`,
  `gh label create ready --color 0e8a16 --description "Ready for /work-tickets" --force`.
- **Resolve the argument:**
  - Matches a domain slug → use that domain's `owns_paths` + `invariants`.
  - Is a path (contains `/` or exists on disk) → ad-hoc scan: `owns_paths` = that path, `invariants` = every
    `.claude/rules/*.md` whose `paths:` frontmatter matches it. At the end, if this path produced real
    signals, offer to add a domain to the registry so future runs are scoped and dedup properly.
- **List existing loop issues so you dedupe rather than duplicate:**
  `gh issue list --state open --label arch-loop --limit 200 --json number,title,labels,body` — note open
  signals/tickets (`triage`), accepted ones (`ready` / `question` / `plan` / `review`) and which domain each
  names (in its `## Reference`). Also check recently closed ones
  (`gh issue list --state closed --label arch-loop --limit 50 --json number,title,stateReason`) so a
  dismissed signal isn't re-filed.

## Step 1 — Scan (produce signals)

Audit each in-scope domain's `owns_paths` against its `invariants`. Cheapest-first sources of drift:

- **Arch tests** — `php artisan test --compact tests/Architecture` (run once per run, not per domain). A
  failure is a signal, verbatim.
- **`.claude/rules/*`** listed in the domain's `invariants` — check the code obeys them. Highest-signal
  examples:
  - Actions in `app/Actions` not `final readonly` (rule: actions.md)
  - DTOs / data objects not `final readonly` or containing logic (rule: data-objects.md)
  - Controllers not single-action `__invoke()` (rule: controllers-routing.md)
  - Models using `$casts` property instead of `casts()` (rule: database-eloquent.md)
  - Commands injecting via constructor instead of `handle()` (rule: artisan-commands.md)
- **phpstan-baseline burndown** — entries in `phpstan-baseline.neon` for in-scope paths that are now
  trivially fixable; fixing the code lets the ignore go. File a cluster in one file/namespace as one signal.
- **Dead markers** — `@todo`, commented-out arch rules in `tests/Architecture/*`, unused code a change would
  let you re-enable.
- **codegraph** (`codegraph_explore`, only if `.codegraph/` exists) for structure/dependency questions grep
  can't answer (layering, cross-namespace `use`, dynamic dispatch).

For each **new, non-duplicate** drift, write the body to `.user/arch-signal.md` (`mkdir -p .user`):

```
**Type:** Signal · **Area:** `<paths>` · **Severity:** <low|medium>

## Problem
<observation: what drift was seen; evidence: file:line refs / arch-test names / commands; why it matters:
which invariant is at risk, linking `.claude/rules/*`>

## Reference
arch-loop signal · domain: `<slug>` (or the ad-hoc path). Related: #NNN
```

then `gh issue create --title "<one-line symptom>" --body-file .user/arch-signal.md --label arch-loop --label triage`
and `rm` the file. Do **not** re-file something matching an existing open or dismissed `arch-loop` issue.

## Step 2 — Triage (signals → tickets)

Review **all** open signals (`arch-loop` + `triage`, `**Type:** Signal`). **Everything stays `triage`** —
you turn signals into planned tickets, but you never add `ready` (a human does that).

- **Real, related signals → a ticket.** Either promote a single signal in place (metadata line
  `**Type:** Signal` → `**Type:** Refactor`; tasks are added in Step 3), or cluster several: pick one signal
  (or create a new `triage` issue) as the ticket, fold each other signal's evidence into its `## Problem`,
  and close the others: `gh issue close MMM --reason "not planned" --comment "Duplicate of #NNN"`. The
  ticket's `## Reference` keeps the domain line and lists the folded signals.
- **Every ticket's metadata line** also gets `· **Playwright:** no — <why>` — refactors are covered by
  Feature/Unit/arch tests. Use `yes — <why>; check: <what to verify in the browser>` only for a finding that
  can only be seen in a real browser (criteria in `.claude/work-tickets/tickets.md`).
- **Noise / false positive / accepted-legacy** →
  `gh issue close NNN --reason "not planned" --comment "<one-line reason>"`.

Edit bodies the `tickets.md` way: `gh issue view NNN --json body -q .body > .user/issue-NNN.md`, edit,
`gh issue edit NNN --body-file .user/issue-NNN.md`, `rm` the file. Retitle with `gh issue edit NNN --title`
when the ticket now covers more than the original symptom (short, imperative).

## Step 3 — Plan (tasks)

For **every** planned ticket, add a `## Tasks` checklist between `## Problem` and `## Reference` — each item
a `- [ ]` line with one concrete, testable task (what must hold, the files it concerns), including the tests
that prove behaviour is unchanged. Where bringing code into compliance makes an arch-test exclusion or a
`phpstan-baseline.neon` entry removable, add a task to remove it — that's the durable win. **Leave the
ticket `triage`.**

Plan large tickets the same way (more than 5 files is fine) — `/work-tickets` sends those to planning mode
(`plan` label) on its own.

## Step 4 — Hand over & report

Summarize concisely with issue numbers (`#NNN` + one line), not full bodies:
- domain(s) scanned; new signals (number + one line + severity); signals dismissed; tickets planned.
- **Tickets now `triage` awaiting human acceptance** (number + severity + one line + url).

**Without `--handoff`** (default): stop here. End with: **"Filed as `triage`. Review the `arch-loop` +
`triage` issues on GitHub and swap `triage` → `ready` on accepted tickets — `/work-tickets` works `ready`
(it runs independently)."** Do not run `/work-tickets`, add `ready`, or close tickets yourself.

**With `--handoff`:** this is the explicit path that skips the human triage gate. For each planned ticket,
`gh issue edit NNN --remove-label triage --add-label ready`. Then invoke `/work-tickets NNN MMM …` with
those numbers in this same session (`Skill`, `skill: "work-tickets"`). `/work-tickets` triages, plans or
implements each ticket and opens PRs — handing off does not skip *its* checkpoints. Relay its report.
