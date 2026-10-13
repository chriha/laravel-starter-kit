---
allowed-tools: Read, Edit, Grep, Glob, AskUserQuestion, Bash(ls:*), Bash(cat:*), Bash(grep:*), Bash(find:*), Bash(rm:*), Bash(gh issue:*), mcp__codegraph__codegraph_explore
description: Walk through a planned ticket (GitHub issue labeled "plan") with me, answer its questions, finalize the plan and move it back to the ready queue
---

Finalize the plan of one big ticket together with the human, then release it to the ready queue for
`/work-tickets`.

Tickets are **GitHub issues** in this repo (`gh issue …`). Background: `/work-tickets` sub-agents don't
implement tickets that touch **more than 5 files**. Instead they draft a `## Plan` (Approach, Steps, Files,
Risks) plus a `## Questions` list in the issue body, mark the metadata line `**Plan:** pending`, and relabel
the issue `ready` → `plan`. See `.claude/commands/work-tickets.md` (Step 2, "Planning mode") for the exact
format.

**This command writes no application code, runs no tests, and never touches git.** It only edits the one
issue (body, labels) and may create split-off issues.

To edit an issue body: `gh issue view NNN --json body -q .body > .user/issue-NNN.md`, edit that file, then
`gh issue edit NNN --body-file .user/issue-NNN.md` and `rm` the file.

Argument (optional): an **issue number** (`/plan-tickets 115`, `#115` also works).
- **Given** → `gh issue view NNN --json number,title,state,labels,body`; it must be open and labeled `plan`.
  If not, say what it is instead (`question` → use `/answer-tickets`; `ready` / `review` / closed → nothing
  to plan) and stop.
- **Omitted** → the **lowest-numbered** open issue labeled `plan`
  (`gh issue list --label plan --state open --json number,title`). If there is none, say so and stop.

## Step 1 — Load & brief

- Read the issue in full, plus the `## Summary` of any closed issue it references (`gh issue view`).
- Verify the plan against the code before asking anything: check the listed files exist (or are sensibly
  new), and that the steps still match the current code — other tickets may have landed since it was
  planned (`git` is off-limits here; use codegraph/grep/read). Note any drift; it may become an extra
  question.
- Check the metadata line's `**Playwright:**` field against the plan (criteria in
  `.claude/work-tickets/tickets.md`). Missing → propose `yes` or `no` with a one-line reason (`yes` also
  names what to check in the browser); present but no longer fitting the plan → propose a change.
- Give the human a **short briefing** (≤ 10 lines): the problem in one line, the approach, the steps as a
  numbered one-liner list, the file estimate, how many questions are open, and the Playwright decision
  with its reason.

## Step 2 — Ask the questions

Ask **every** question whose `**Answer:**` is `_open_`, personally, via `AskUserQuestion`:

- One `AskUserQuestion` call per question (up to 4 per call only when they are small and independent).
  The question text must stand alone — include the context the human needs, not just "Q3?".
- Options = the ticket's options (2–4), recommended one first with `(Recommended)`. The human can always
  pick "Other" and type a free answer. Use `preview` for options that are code/layout shapes.
- **Add your own questions** for drift found in Step 1 or for gaps you spot in the plan (missing tests or coverage,
  migration strategy, README update, splitting the ticket). Ask them the same way and add them to
  `## Questions`.
- If an answer changes the premise of later questions, re-phrase or drop those before asking.
- If an answer is "split this ticket", note which steps move out; you'll create the new issues in Step 3.
- Ask about **Playwright** only when it's a close call — options `No — <reason>` / `Yes — <reason>;
  check: …`, recommended first.

## Step 3 — Update the plan

Edit the issue body:

- Fill in every `**Answer:** …` with the decision (and a short why, if the human gave one).
- Rewrite `## Plan` (Approach / Steps / Files / Risks) so it reflects the answers — the plan must be
  executable **without** reading the questions. Update `**Estimated files:**`.
- Rewrite `## Tasks` to match the final `### Steps` one-to-one (`- [ ]` per step, with its **Done when:**).
  Keep the original problem text as is.
- If the answers change a product rule, add a task to update the matching `README.md` section.
- Only tests and test coverage count as quality work: every step's **Done when:** includes its tests, and no
  step, task or question is about code style, Rector, linting or static analysis (Pint, Rector, PHPStan,
  ESLint, Prettier) — drop any such ones from the drafted plan.
- Metadata line: `**Plan:** pending` → `**Plan:** approved (YYYY-MM-DD)`, and set
  `· **Playwright:** no — <why>` or `· **Playwright:** yes — <why>; check: <what to verify>` (add it if
  missing).
- **Split** (if chosen): `gh issue create --label ready` a new issue for the carved-out steps, in the normal
  ticket format, with its own `**Plan:** approved` line if it still exceeds 5 files, its own
  `**Playwright:**` decision for the carved-out steps, and a `## Reference` back to `#NNN`.

Show the human the updated `## Plan` → `### Steps` and the Playwright decision, and ask once via `AskUserQuestion`:
**"Release this plan to the ready queue?"** — options: `Approve (Recommended)` / `Adjust`. On `Adjust`,
take the feedback, update the issue, and ask again.

## Step 4 — Release & report

- `gh issue edit NNN --remove-label plan --add-label ready`.
- Report: issue number + title, decisions made (one line each), final step count and file estimate, any
  issues created by a split, and how many issues are left labeled `plan`.

End with: **"Ready — `/work-tickets NNN` will implement it."**
