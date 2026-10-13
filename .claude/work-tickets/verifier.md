# Verifier brief (/work-tickets)

Your prompt gives the values for `NNN`, `<slug>`, `<repo>`, `<wt>` and `<report>`.

- **Read-only**: verify against `<repo>/.user/worktrees/_main` (a detached checkout of `origin/main`) — `cd`
  there for every command. If the finding only exists in the ticket branch's changes, look in `<wt>` too.
  Don't modify code, run migrations, commit or touch git state. Running a focused test to confirm a bug is
  fine.
- **Duplicate check**: search existing issues (open and closed) with a few keyword queries —
  `gh issue list --state all --search "<keywords>" --json number,title,state` — and open any that look
  similar; compare with the in-flight findings too.
- **Verify** against the code and `README.md`: real, reproducible, not already handled or intended?
- **Verdict:**
  - **invalid** — not a real problem, or a code-style / Rector / lint / static-analysis issue that the
    CI gates on `main` don't flag (run the gate's command in `_main` to check). Create nothing. A gate that
    fails on `main` is real — ticket it as `Type: Refactor` (or `Bug`) naming the command and its output.
  - **duplicate** — already covered by issue `#MMM`. Create nothing.
  - **confirmed, actionable** — fix is clear from code/README → `gh issue create --label ready`.
  - **confirmed, needs a decision** — needs a product/product call → `gh issue create --label question`
    with a `Decide: …` title.
- Ticket shape — match issue #81: a short title, body with
  `**Type:** Bug | Content | Refactor | Test | Docs | Open question · **Area:** \`paths\` ·
  **Playwright:** no — <why>` (or `yes — <why>; check: <what to verify in the browser>` only when the
  finding can only be seen in a real browser — criteria in `tickets.md`), a `## Problem`
  (or `## Question` with concrete options) with the evidence (file:line, what happens vs. what should), a
  `## Tasks` checklist (open questions start with `- [ ] Decide …`), and `## Reference` →
  `Found while working #NNN` plus README sections. Keep it small.
- **Result** — your final reply must be only this line:
  `RESULT F-<source NNN>: confirmed|duplicate|invalid · <created issue url | duplicate of #MMM | none> · <reason, ≤ 15 words>`
