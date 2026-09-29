---
allowed-tools: Read, Write, Edit, Grep, Glob, AskUserQuestion, Bash(ls:*), Bash(cat:*), Bash(grep:*), Bash(find:*), Bash(rm:*), Bash(mkdir:*), Bash(gh issue:*), mcp__codegraph__codegraph_explore
description: Report a bug, change request or feature — I investigate it, plan it with you and file it as a ready ticket (GitHub issue) for /work-tickets
---

Turn something the human reports — a **bug**, a **change request** or a **new feature** — into a planned,
actionable ticket and put it in the ready queue for `/work-tickets`.

Tickets are **GitHub issues** in this repo (`gh issue …`). Ticket shape: `.claude/work-tickets/tickets.md`;
closed issue **#81** is the reference shape. The plan format is the one in
`.claude/work-tickets/implementer.md` ("Planning mode").

**This command writes no application code, runs no tests, and never touches git.** It only creates issues.

Argument: the report in free text (`/report-ticket Barracks queue shows negative seconds after reload`).
**Omitted** → ask for it via `AskUserQuestion` (options: `Bug` / `Change request` / `Feature`, the human
types the details via "Other" or a follow-up).

## Step 1 — Investigate

- Classify: `Bug` (behaviour differs from code intent or `README.md`), `Change` (existing behaviour should
  differ), `Feature` (new behaviour). Metadata type: `Bug` / `Feature` / `Refactor` / `Content` / `Docs`.
- **Duplicate check**: `gh issue list --state all --search "<keywords>" --json number,title,state,labels`
  with a few keyword queries; open similar ones. If one already covers it, show it and ask via
  `AskUserQuestion`: `Add to #MMM` (append a `## Update (YYYY-MM-DD)` section via body file, then stop) /
  `File separately` / `Drop`.
- Look at the code in the area (codegraph first, then grep/read) and the matching `README.md` section.
  For a bug, find the cause (file:line, what happens vs. what should). Don't run tests or the app.
- List every file the change would create or modify, **including** tests, migrations, factories, seeders,
  Vue pages/components, TS types and `README.md`.
- Brief the human (≤ 10 lines): what you understood, what the code does today (with file:line), the likely
  fix/approach, the file estimate.

## Step 2 — Ask

Ask everything an implementer would otherwise guess — game-design rules, numbers, UX, naming, edge cases,
scope, splitting — via `AskUserQuestion`, the same way `/plan-tickets` Step 2 does: standalone question
text, 2–4 options, recommended first with `(Recommended)`, `preview` for code/layout/formula shapes,
follow-ups until the decision is concrete. No questions is fine if the report is unambiguous.

If the human wants to postpone a decision, the ticket becomes an **open question** (see Step 3).

## Step 3 — Draft the ticket

Write the body to `.user/new-ticket.md` (`mkdir -p .user`):

```
**Type:** <type> · **Area:** `<paths>`[ · **Plan:** approved (YYYY-MM-DD)]

## Problem
<1–5 sentences: what happens vs. what should, with file:line evidence; for features: what's missing and why>

## Decision
<one bullet per answer from Step 2, concrete (numbers, rules, edge cases); omit if nothing was asked>

## Tasks
- [ ] <concrete task> — including tests, and "Document in README → <section>" for a game rule

## Reference
Reported by the human (YYYY-MM-DD). README → <section>. Related: #NNN
```

- **More than 5 files** → also add `**Plan:** approved (YYYY-MM-DD)` to the metadata line and a `## Plan`
  section (`**Estimated files:**`, `### Approach`, `### Steps` with **Done when:**, `### Files`,
  `### Risks`) in the implementer's planning format, and make `## Tasks` match `### Steps` one-to-one. No
  `## Questions` section — they were answered in Step 2. If the steps split into independent parts, offer
  to file them as separate tickets.
- **Decision postponed** → title `Decide: …`, `**Type:** Open question`, a `## Question` section with the
  concrete options instead of `## Decision`, tasks starting with `- [ ] Decide …`.
- Title: short, imperative, what to implement (`Clamp barracks queue timer at zero`).

Show the human the title and body and ask once via `AskUserQuestion`: **"File this ticket?"** — options:
`File as ready (Recommended)` / `Adjust`. On `Adjust`, update the draft and ask again.

## Step 4 — File & report

- `gh issue create --title "<title>" --body-file .user/new-ticket.md --label ready` (`--label question` for
  an open question), then `rm .user/new-ticket.md`.
- Report: issue number + url, title, type, task count and file estimate.

End with: **"Ready — `/work-tickets NNN` will pick it up."** (open question: **"Filed — answer it with
`/answer-tickets NNN`."**)
