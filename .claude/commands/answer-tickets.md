---
allowed-tools: Read, Edit, Grep, Glob, AskUserQuestion, Bash(ls:*), Bash(cat:*), Bash(grep:*), Bash(find:*), Bash(rm:*), Bash(gh issue:*), mcp__codegraph__codegraph_explore
description: Guide me through an open-question ticket (GitHub issue labeled "question"), record my answers, turn it into an actionable ticket and move it to the ready queue
---

Resolve an **open-question ticket** together with the human, then turn it into a normal, actionable ticket
for `/work-tickets`.

Tickets are **GitHub issues** in this repo (`gh issue …`). Open-question tickets are open issues labeled
`question`, with `**Type:** Open question` on the metadata line, a `## Question` section and a `## Tasks`
list starting with `- [ ] Decide …`. `/work-tickets` labels them so because an agent would otherwise have to
invent game design.

**This command writes no application code, doesn't edit `README.md`, runs no tests, and never touches
git.** It only edits the one issue (title, body, labels).

To edit an issue body: `gh issue view NNN --json body -q .body > .user/issue-NNN.md`, edit that file, then
`gh issue edit NNN --body-file .user/issue-NNN.md` and `rm` the file.

Argument (required): an **issue number** (`/answer-tickets 109`, `#109` also works).
- `gh issue view NNN --json number,title,state,labels,body`. It must be open and labeled `question`. If it's
  labeled `plan`, say "that's a planned ticket — use `/plan-tickets NNN`" and stop. Otherwise say what state
  it's in (closed, `ready`, `review`, missing) and stop.
- **No argument** → `gh issue list --label question --state open --json number,title,body`, list them
  (number + title + the question in one line) and ask which one via `AskUserQuestion` (up to 4 options,
  lowest numbers first; "Other" for the rest).

## Step 1 — Brief

- Read the issue in full, plus any issue it references (`## Reference`, `#NNN` → `gh issue view`), and the
  `README.md` section it points to.
- Look at the code in the ticket's `Area` (codegraph/grep/read) so you can explain the **current behaviour**
  and what each option would mean in practice.
- Give the human a **short briefing** (≤ 10 lines): what the question is, why it matters (what is blocked),
  how the code behaves today, and what the README currently says.

## Step 2 — Answer the questions

Break the ticket's `## Question` into individual decisions (one main question often implies follow-ups —
e.g. "what does Command Limit limit?" → "…and what's the base value without an Academy?"). Ask them
**one at a time** via `AskUserQuestion`:

- The question text must stand alone, with the context the human needs.
- Options = the ones in the ticket plus sensible ones derived from the README and code (2–4),
  recommended one first with `(Recommended)` and a one-line reason. "Other" is always available for a free
  answer. Use `preview` for options that are formulas, tables or code shapes.
- After each answer, decide whether a follow-up is needed (numbers, edge cases, interaction with other
  rules) and ask it. Stop when the decision is concrete enough that an agent could implement it without
  guessing.
- If the human says "not now" / wants to postpone, record what was decided so far under `## Answers` in the
  body, leave the `question` label, and stop.

## Step 3 — Rewrite the ticket

Edit the issue into a normal ticket:

- Title `Decide: …` → `<what to implement>` (`gh issue edit NNN --title …`).
- Metadata line: `**Type:** Open question` → the real type (`Feature` / `Bug` / `Refactor`); keep `Area`.
- Keep the `## Question` section as is, and add a `## Decision` section below it: each decision as a
  bullet, concrete (numbers, rules, edge cases), plus the human's reasoning where given.
- `## Tasks`: tick the `Decide …` task(s) (`- [x]`), then replace vague tasks like "Implement accordingly"
  with concrete `- [ ]` tasks derived from the decision — including **"Document in README → <section>"**
  when the decision is a game rule, and **tests** for the new rule.
- Only tests and test coverage count as quality work: never add tasks for code style, Rector, linting or
  static analysis (Pint, Rector, PHPStan, ESLint, Prettier).

Show the human the new `## Decision` + `## Tasks` and ask once via `AskUserQuestion`:
**"Move this ticket to the ready queue?"** — options: `Yes (Recommended)` / `Adjust`. On `Adjust`, take the
feedback, update the issue, and ask again.

## Step 4 — Release & report

- `gh issue edit NNN --remove-label question --add-label ready`.
- Report: issue number + new title, the decision in 1–3 lines, the task list, and which other issues
  reference this one and may now be unblocked (`gh issue list --state open --search "#NNN in:body"`).
- If the tasks will clearly touch more than 5 files, say so: `/work-tickets` will route it through
  planning first.

End with: **"Ready — `/work-tickets NNN` will pick it up."**
