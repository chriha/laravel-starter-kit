---
allowed-tools: Agent, Bash(gh pr list:*), Bash(gh pr view:*), Bash(gh pr diff:*), Bash(gh pr edit:*), Bash(gh pr comment:*), Bash(gh label list:*), Bash(gh label create:*), Bash(gh api:*), Bash(gh issue view:*), Bash(jq:*)
description: Triage open PRs into AI-reviewable vs human-review-required and label them
---

Sort open PRs into two review lanes with the labels **`review-ai`** (small, isolated — AI may review and
approve) and **`review-human`** (a human must review). One sub-agent per PR.
Argument (optional): PR numbers to (re-)triage, e.g. `/triage-prs 41 40`, bypassing the skip rules.

## Step 1 — Labels

`gh label list` — create any missing:
- `gh label create review-ai --color 2da160 --description "Small, isolated change. AI review + approval allowed."`
- `gh label create review-human --color dc143c --description "Touches core/shared behaviour. Human review required."`

## Step 2 — Pick PRs

`gh pr list --json number,title,isDraft,headRefOid,labels --limit 100` and keep PRs that are **not draft** and:
- have **no** `review-*` label → triage, or
- have `review-ai` but the head SHA moved since the last triage → re-triage. Find the last triage SHA in the
  newest comment containing `<!-- pr-triage sha=` (`gh pr view <n> --json comments`). None → re-triage.

`review-human` is sticky — never re-triaged automatically (only via explicit argument).

**CI gate:** for each kept PR read `gh pr checks <n> --json state`. Any pending check, or a check for an older
SHA → skip it this run ("waiting on CI"). Pass the overall result (`pass`/`fail`) to the agent.

Nothing to do → say so and stop.

## Step 3 — Fan out

Launch one **sonnet** sub-agent per PR, all in a single message (batches of 10 max), each with the prompt
below (fill in `<n>` and `<ci>`). Tell each agent: tools work, no exploratory calls, do not check out
branches, do not approve, comment inline, or push.

> Triage GitHub PR #<n> of this repo (Laravel + Inertia + Vue starter kit). Read `CLAUDE.md` first.
> Fetch `gh pr view <n> --json title,body,headRefOid,author,headRefName,closingIssuesReferences` and
> `gh pr diff <n>`. CI: `<ci>`.
>
> **Ticket:** the linked issue (`closingIssuesReferences`, or `#NNN` in the body). Fetch it with
> `gh issue view`. Never guess a number.
>
> Decide the lane. **`review-human` if ANY hard trigger matches** — no judgement, no exceptions:
> - CI failed.
> - No linked issue, the issue has no `## Tasks`, or the diff goes beyond the issue's scope ("while I was
>   here" cleanups, changed defaults, loosened validation).
> - Mixes a refactor with a behaviour change — say in the note that it should be split.
> - Paths: `.github/`, `.github/CODEOWNERS` entries (if the file exists), `composer.*`, `package*.json`,
>   `config/`, `database/migrations/`, `routes/`, `app/Providers/`, auth/Fortify/permissions code.
> - Size: > 400 changed lines (excluding tests and lockfiles) or > 15 files.
> - Tests deleted, skipped, or assertions weakened; PHPStan baseline entries or arch-test exemptions added.
> - Tests that can't fail: no assertions, asserting on a mock's own return value, or mocking the class
>   under test.
> - Touches auth, permissions, tokens/secrets, PII, raw SQL, `DB::statement`, queue/job retry/timeout config,
>   scheduling, cache keys/TTL.
> - Backward-incompatible deploy: changed job constructor/serialized payload, event payload, or code that
>   stops using a column in the same PR that drops it.
> - Security smells: `$guarded = []` or widened `$fillable`, FormRequest `authorize()` returning `true`
>   on a non-public endpoint, unscoped queries, raw SQL with interpolation.
> - Public route/response-shape change consumed elsewhere.
> - Data-mutating commands/backfills or anything irreversible.
> - Description is empty or the diff does something the title/description doesn't say.
>
> No hard trigger → judge **blast radius**: `review-ai` only if the change is local, has tests covering the
> changed behaviour, and a wrong change would be caught by tests or be trivially reversible. Unsure →
> `review-human`.
>
> Also scan for **AI failure patterns** and list any hit under risk hotspots (they don't force the lane
> on their own): new broad `catch`/swallowed exceptions or `?->` added to silence an error, a helper
> duplicating an existing one (grep for it), calls to Facade/`__call` methods that may not exist.
>
> Apply it: `gh pr edit <n> --add-label review-<lane> --remove-label review-<other>` (ignore the error if the
> other label isn't set), then `gh pr comment <n> --body "<note>"` with:
> ```
> **PR triage: `review-<lane>`** — <one-sentence reason>
> - <each hard trigger hit, or the blast-radius reasoning, max 5 bullets with file paths>
> - Review the tests first: <test files, and whether they cover the issue's tasks>
> - Risk hotspots for the reviewer: <files/lines worth the closest look, incl. AI failure patterns>
> <!-- pr-triage sha=<head sha> -->
> ```
> Return: `#<n> <lane> — <reason>`.

## Step 4 — Report

Table: PR, title, lane, reason. Then list PRs skipped (draft / already triaged / waiting on CI).
