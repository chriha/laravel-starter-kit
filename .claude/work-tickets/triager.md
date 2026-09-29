# Triager brief (/work-tickets)

Your prompt gives the values for `NNN`, `<slug>`, `<repo>`, `<wt>` and `<report>`.

- Load the ready queue in full:
  `gh issue list --state open --label ready --limit 200 --json number,title,body`.
- For each `REOPENED` issue, append a dated `## Progress` note ("PR closed without merge") to its body.

The argument may contain **chains** `A>B>C` (worked in that order, each stacked on the previous one's
branch). Space-separated groups are independent. A chain's first ticket may be in `review` (PR open) — it's
not worked, only the base for the next one. Any other chain ticket must be `ready`, or it's `BLOCKED`
(`not ready: <label>`), and so is everything after it in its chain.

**Classify** each ready issue (only the argument's issues, if given):

- **Open question** — `**Type:** Open question`, or a first task starting with `Decide`. An agent would
  have to invent game design. **Relabel** it `ready` → `question`.
- **Blocked** — depends on another ticket (`#NNN`, "after #NNN") whose issue is **not closed**. A dependency
  labeled `review` (PR not merged yet), `question`, `plan`, `ready`, or scheduled in this run blocks too —
  ticket branches are cut from `origin/main`, so a dependency only counts once it's merged. Leave it.
  **Exception:** a dependency **earlier in the same chain** doesn't block.
  - **Suggest a fix** — only for tickets named in the argument. Follow the blockers recursively (a blocker's
    own open dependencies too) and build one chain that ends with the ticket: every blocker labeled
    `ready` is worked in it, dependencies before dependents, lower number first among independent ones.
    A blocker in `review` with an open PR can **head** the chain (at most one — a branch can stack on only
    one base). Blockers in `question`/`plan`, open without a state label, a second `review` blocker, or a
    cycle can't be fixed by this command.
    - Everything fixable → `fix: <chain>`, e.g. `fix: 98>104>107`.
    - Otherwise → `fix: none — <reason, e.g. #MMM needs /answer-tickets MMM>`, plus
      `partial: <chain of the workable blockers only>` if there are any.
- **Already done** — every task is `- [x]`: if it has a `**PR:**` link, relabel it `ready` → `review`;
  otherwise report it for a human to check.
- **Eligible** — everything else. Give it a `<slug>`: short kebab-case of its title, ≤ 5 words. A ticket with a `**PR:**` link (partial from an earlier run)
  **continues** on its existing branch and worktree (its slug is the existing branch's).

**Base** — each eligible ticket gets a `<base>`: `main`; the branch of the previous ticket in its chain
(`ticket/MMM-<slug>`, or a `review` head's PR branch: `gh pr view <url> --json headRefName`); or, for a
continuing ticket, its PR's current base (`gh pr view <url> --json baseRefName`).

**Overlap check** — tickets run in parallel off the same `main`, so two tickets editing the same code would
conflict at merge time. Compare the `**Area:**` paths (and `### Files` of approved plans) of the eligible
tickets. If two overlap on a code file (shared docs like `README.md` don't count), keep the lower-numbered
one and mark the other **held — overlaps #NNN**. Tickets in the **same chain** never hold each other; a
held chain ticket holds the rest of its chain too.

**Result** — your final reply must be only this block:

```
BASE_SHA: <git rev-parse --short origin/main>
ELIGIBLE:
NNN | <slug> | <title> | <deps: closed issue numbers it references, or -> | <plan approved | continues <PR url> | -> | base <branch>
CHAINS: <A>B>C per line, in work order; a review head as "#A (review, <PR url>)"; or "none">
OPEN_QUESTIONS: <NNN title — the question in one line, or "none">   (relabeled question)
BLOCKED: <NNN — blocked by #MMM (<label>)[, #KKK (<label>)] · fix: <chain> | fix: none — <reason> [· partial: <chain>] (fix/partial only for argument tickets), or "none">
HELD: <NNN — overlaps #MMM, or "none">
ALREADY_DONE: <NNN — relabeled review | needs human check, or "none">
```
