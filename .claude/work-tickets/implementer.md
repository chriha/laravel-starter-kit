# Implementer brief (/work-tickets)

Your prompt gives the values for `NNN`, `<slug>`, `<repo>`, `<wt>`, `<report>` and `<base>` (`main`, or
the branch this ticket is **stacked** on — then also `<base-wt>` and the base PR url).

- **Read the issue** (`gh issue view NNN`) and the `## Summary` of every closed issue it references. Don't
  edit the issue or its labels except in planning mode or when filing the ticket (below).
- **Playwright decision** — read the metadata line's `**Playwright:**` field (see `tickets.md`) before any
  browser use. **`no` or missing** → no `mcp__playwright__*` / `mcp__plugin_playwright_*` tools and no new
  or changed Pest browser tests in `tests/Browser`; the existing browser suite still runs as part of the
  CI gates. **`yes`** → both are allowed, but only to verify the items on its `check:` list. If a `no`
  ticket turns out to need a browser after all, don't use one — report it as an open question.
- **Size check first, before any setup or code change.** Explore read-only. Use CodeGraph to locate code,
  and read files in `<wt>` if it exists (continuing), otherwise in `<base-wt>` when stacked, otherwise in
  `<repo>/.user/worktrees/_main` (a detached checkout of `origin/main`). Never edit those.
  List every file you expect to create or modify, **including** tests, migrations, factories, seeders,
  Vue pages/components, TS types and `README.md`.
  - **More than 5 files and no `**Plan:** approved` → planning mode** (below). Don't create a worktree or
    branch.
  - **Plan approved** → implement it, following `## Plan` step by step, regardless of file count. If the
    plan turns out to be wrong in a way that needs a decision, stop and report it as an open question.
  - **5 files or fewer** → implement it.
- **Worktree setup** (only when implementing) — if `<wt>` doesn't exist:
  ```
  git -C <repo> fetch origin <base>
  git -C <repo> worktree add -b ticket/NNN-<slug> <wt> origin/<base>   # continuing: worktree add <wt> ticket/NNN-<slug>
  cp -cR <repo>/vendor <wt>/vendor
  ln -s <repo>/node_modules <wt>/node_modules
  cp <repo>/.env <wt>/.env
  cp -R <repo>/public/build <wt>/public/build
  cd <wt> && npm run build
  ```
  If `<wt>` exists (continuing), `cd <wt> && npm run build` anyway. `vendor/` is a copy-on-write clone, not a
  symlink, so Pest autoloads `App\`, `Database\` and `Tests\` from the worktree. Tests use SQLite
  `:memory:`, so parallel test runs don't collide. The copied `public/build/` can predate `origin/main`, and
  stale-but-present assets silently break browser tests — so always build **inside `<wt>`**, never in
  `<repo>`.
- **Work only inside `<wt>`.** `cd <wt>` before any command and use paths under `<wt>` for every read
  and edit. Never modify files under `<repo>` except `<report>` and temp issue-body files in `<repo>/.user/`.
  CodeGraph indexes the main checkout: use it to locate code, but always read and edit the file in `<wt>`.
- `vendor/` is a worktree-local clone. If it looks stale (e.g. `<wt>/composer.lock` changed after a
  `git merge origin/<base>`), run `composer install` inside `<wt>`. **Never** run
  `composer require/update/remove`: a needed dependency change is an open question.
- If a test fails with "Unable to locate file in Vite manifest", or after a `git merge origin/<base>` or any
  change under `resources/js`, run `npm run build` inside `<wt>` before trusting a browser test.
- Read `CLAUDE.md` and follow it: activate the relevant skills (`laravel-best-practices`, `pest-testing`,
  `inertia-vue-mainment`, …), use `search-docs` before code changes, follow sibling-file conventions, use
  `php artisan make:` generators. The PR must pass every CI gate — see *CI gates* below.
- **Implementing:**
  - If an unplanned ticket grows past 5 files mid-work, stop. Discard **your own** uncommitted changes
    (`git restore <files>`, delete files you created). If you haven't pushed yet, also remove the worktree
    and branch (`git -C <repo> worktree remove --force <wt>`, `git -C <repo> branch -D ticket/NNN-<slug>`).
    Then switch to planning mode.
  - Work **every task** in `## Tasks`. Stay in scope — no opportunistic refactors, no fixing unrelated bugs;
    report them under `## Findings` instead.
  - Every change must be tested: write/update Pest tests, run the affected ones with
    `php artisan test --compact <file or --filter>` until green. Every new or changed behaviour needs a test
    covering it.
  - **Conventional commits** on `ticket/NNN-<slug>`: `type: subject` — `feat` / `fix` / `refactor` / `test` /
    `docs` / `chore` / `style`, lowercase imperative, no trailing period, ≤ 72 chars; match `git log --oneline -15`.
    One commit per ticket by default; split only for clearly separate concerns or independent plan steps.
    Each commit leaves tests green. Stage by path (`git add <files>`), never `-A` / `.`. Body: one or two
    lines on what and why, then `Refs #NNN`, then the `Co-Authored-By:` trailer exactly as the session's
    attribution system reminder gives it (it names the model you run on — never copy a model name from an
    older commit or brief).
  - **CI gates** — before the first push, and again before `gh pr ready`, run inside `<wt>` what
    `.github/workflows/tests.yml` runs (`composer test` = `test:type-coverage`, `test:lint`, `test:rector`,
    `test:types`, `test:unit` = Pest with exactly 100 % coverage; plus `vendor/bin/mago lint`), plus the auto-fixer, and fix every failure:
    1. `composer lint` — auto-fixes (PHPCBF, Rector, Pint, Prettier). Review what it changed; commit fixes
       to files your ticket touches (`style: …` or folded into your commit before pushing).
    2. `composer test:lint` — Composer validate, Pint, PHPCS, Rector, Prettier (check only).
    3. `composer test:types` — PHPStan/Larastan. Fix the code; never add baseline entries or
       `@phpstan-ignore` to silence your own changes. Only exception: Larastan can't type `$model->pivot`,
       so `Access to an undefined property <Model>::$pivot` goes into `phpstan-baseline.neon` like the
       existing entries.
    4. `composer test:type-coverage` — type coverage of 100 % (`--min=100`).
    5. `php artisan test --compact --parallel` — the full Pest suite (Unit, Feature, Architecture and
       Browser). CI runs the same suite unfiltered (`php artisan test --parallel`); run it locally first so
       failures surface before the push.
    6. `vendor/bin/mago lint` — Mago lint (CI runs it as its own step).

    Re-run a gate after fixing it until all six pass. A gate that also fails on
    `<repo>/.user/worktrees/_main` (check with the same command there, read-only) is **pre-existing**:
    don't fix it here — report it under `## Findings`, note it in the PR's `## Tests`, and it doesn't
    block `done`. A failure caused by your changes always blocks `done`.
  - **Push & open a draft PR** once every commit is in, tests are green and the CI gates pass (partial
    work: push anyway, list failing gates in `## Tests`):
    `git push -u origin ticket/NNN-<slug>` (never force). No PR yet →
    `gh pr create --draft --base <base> --head ticket/NNN-<slug>`, title = main commit's subject, body:
    stacked → first line `> Stacked on <base PR url> — merge that first.`; then `## Problem` (1–3
    sentences), `## Changes`, `## Tests`, then `Closes #NNN` (status **done**) or `Refs #NNN`
    (partial/blocked), ending with `🤖 Generated with [Claude Code](https://claude.com/claude-code)`.
    PR exists → just push (and swap `Refs` → `Closes` in its body once the ticket is done).
    Status **done** → `gh pr ready <PR>`. Partial/blocked PRs stay draft.
  - A task that needs a product/product decision the issue and `README.md` don't answer: **don't
    guess** — skip it and report it as an open question.
- **Planning mode** (the only case where you edit the issue): leave the problem/tasks text as is, add
  `· **Plan:** pending` to the metadata line (plus `· **Playwright:** yes|no — <why>[; check: …]` if it's
  missing, decided by the criteria in `tickets.md`), append the sections below to the body, then relabel it
  `ready` → `plan`. No code changes, commits, push or PR.
  ```
  ## Plan
  **Estimated files:** N

  ### Approach
  <2–5 sentences: the design, and why this one over the alternatives you considered>

  ### Steps
  1. <concrete step> — files: `path`, `path` — **Done when:** <condition>
  <each step small enough for one conventional commit; mark steps that could ship as separate tickets>

  ### Files
  - `path` (new | modify) — <why>

  ### Risks
  - <migrations, behaviour changes, performance, things that could break>

  ## Questions
  1. **<question>**
     - Options: **A)** … **B)** … — _recommended: A, because …_
     - Blocks: step N
     - **Answer:** _open_
  ```
  Ask **everything** you'd otherwise guess (product rules, numbers, UX, naming, scope cuts, splitting).
  Each question must be answerable on its own. No questions is fine if the plan is unambiguous.
- **Write `<report>`** (overwrite) with these sections:
  ```
  ## Status
  done | partial | blocked | planned — files estimated: N
  ## Commits
  <short sha + subject per line, or "none">
  ## Tasks done
  <task lines fully done, verbatim>
  ## Tasks open
  <task lines not done, each with a one-line reason>
  ## Tests
  <exact test commands you ran, then one line per CI gate: `<command>: pass | fail (pre-existing) | fail`>
  ## Summary
  <bullets in the style of the ## Summary of issue #81: one bold-led bullet per decision/change, then
   **Files**: …, then **Tests**: … — for "planned": the approach in 2–3 bullets and the number of questions>
  ## Open questions
  <each with the options you see and the task it blocks; "in ticket" for planned; "none">
  ## Findings
  <problems outside this ticket's scope — bugs, README/code mismatches, dead code, missing tests, and
   pre-existing CI gate failures on `main` (one finding per gate). Never style/lint issues in your own changes —
   those you fix. One per line: `- **<short title>** — file:line — what you
   saw vs. what you expected`. "none" if nothing. Don't fix them.>
  ```
- **File the ticket** (not in planning mode) — edit the issue body (never rewrite the problem/tasks text —
  only tick boxes, extend the metadata line, and append):
  - Tick every done task `- [ ]` → `- [x]`. PR opened → add `· **PR:** <url>` to the metadata line (once).
  - **done** → append `## Summary` (the report's Summary bullets, then a `**Commits**:` line with short shas +
    subjects and a `**Branch**:` line). **partial / blocked** → append `## Progress (YYYY-MM-DD)` with what
    was done and the commits, plus an `### Open` list of remaining tasks with reasons. Existing
    `## Summary`/`## Progress` → append a new dated `## Progress` below it.
  - Every task `- [x]`, tests green, CI gates pass, PR ready → relabel `ready` → `review` (keep the worktree and
    `<report>` for follow-up fixes). Otherwise it stays `ready`.
  - **Open questions from the report** → create one issue each:
    `gh issue create --label question --title "Decide: …" --body …` with `**Type:** Open question ·
    **Area:** … · **Playwright:** no — <why>`, `## Question` (with options), `## Tasks` starting with `- [ ] Decide …`, `## Reference` →
    `Raised while working #NNN`. Mention each new issue number in the ticket's `### Open` list.
- **Result** — your final reply must be only this line:
  `RESULT NNN: <done|partial|blocked|planned> · <review|ready|plan> · PR <url|none> <ready|draft> · commits <n> · open-questions filed: <#numbers|none> · findings: <short title>; <short title>; … (or "none")`
