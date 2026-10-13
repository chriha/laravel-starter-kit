---
allowed-tools: Bash(git log:*), Bash(git diff:*), Bash(git branch:*), Bash(git status:*), Bash(git add:*), Bash(git commit:*), Bash(gh pr view:*), Bash(gh pr diff:*), Bash(gh pr comment:*), Bash(gh pr review:*), Bash(gh api:*), Bash(gh issue list:*), Bash(gh issue create:*), Bash(gh issue comment:*), AskUserQuestion
argument-hint: [pr#|branch|path] [--comment] [--fix]
description: Code review the locally checked out branch, a PR, a branch or a path
---

Usage: `/local-code-review [pr#|branch|path] [--comment] [--fix]`

**Target** (`$ARGUMENTS` without flags):
- none: review the current branch (`main...HEAD`).
- number: a PR (`gh`).
- branch name: review `main...<branch>`.
- path: limit the diff to that path.

**Flags:**
- `--comment`: post verified findings from agents 1, 2 and 5 as PR comments (step 6).
- `--fix`: apply verified findings to the working tree and commit them (step 6). Never pushes.

Only look up a PR when a number is given or `--comment` is set. Only post comments when `--comment` is set. Only change files when `--fix` is set.

**Agent assumptions (applies to all agents and subagents):**
- All tools are functional and will work without error. Do not test tools or make exploratory calls. Make sure this is clear to every subagent that is launched.
- Only call a tool if it is required to complete the task. Every tool call should have a clear purpose.

To do this, follow these steps precisely:

1. Resolve the target and understand the changes:
    - none/branch/path: run `git log main..<ref> --oneline` and `git diff main...<ref> [-- <path>]` (`<ref>` = `HEAD` or the branch). Use the commit messages as the author's intent.
    - number: fetch the diff (`gh pr diff <n>`) and the title/description (`gh pr view <n>`) as the author's intent.
    - `--comment` with no number: find the PR of the current branch (`gh pr view`). If none exists, stop with a clear message.
    - `--fix` with a number whose branch is not the checked out one: stop with a clear message.
    - `--fix`: run `git status --porcelain` now and remember the already dirty paths (before any agent edits files).

2. Launch a haiku agent to return a list of file paths (not their contents) for all relevant CLAUDE.md files including:
    - The root CLAUDE.md file, if it exists
    - Any CLAUDE.md files in directories containing files modified by the change

3. Launch in parallel: the built-in `/code-review` (agent 3) and agents 1, 2 and 5. Each agent should return the list of issues, where each issue includes a description and the reason it was flagged (e.g. "CLAUDE.md adherence", "test quality"). The agents should do the following:

   Agents 1 + 2: CLAUDE.md and rules (`.claude/rules/*`) compliance sonnet agents
   Audit changes for CLAUDE.md compliance in parallel. Note: When evaluating CLAUDE.md compliance for a file, you should only consider CLAUDE.md files that share a file path with the file or parents.

   Agent 3: run `/code-review <target>` with the same target, passing `--comment` and `--fix` through when given. Its findings are not validated again in step 4.

   Agent 5: Sonnet test quality agent
   Review only test files in the diff. For each test, evaluate it against these principles and flag violations:
   - **Goal-first:** tests must specify desired behavior or intent (requirements, edge cases, error handling) — not just exercise code paths.
   - **Small & focused:** each test should check one behavior; flag tests that assert multiple unrelated things or have excessive setup.
   - **Fast & deterministic:** flag tests that rely on I/O, randomness, timing, or external dependencies without mocking/stubbing.
   - **Readable & maintainable:** flag unclear names, missing arrange/act/assert structure, or duplicated setup that should use helpers/fixtures.
   - **Independent:** flag tests that depend on execution order or shared mutable state.
   - **Right level of abstraction:** flag unit tests that should be integration tests or vice versa where the mismatch is obvious.
   - **Coverage of cases:** flag obvious missing paths — especially missing error paths, boundary values, or invalid input cases — when the diff introduces logic that clearly warrants them.
   - **Assert behavior, not implementation:** flag tests that assert on internal calls or implementation details when the observable outcome could be checked instead.
   Only flag test files; skip this agent if no test files appear in the diff.

   **CRITICAL: We only want HIGH and MEDIUM SIGNAL issues.** Flag issues where:
    - The code will fail to compile or parse (syntax errors, type errors, missing imports, unresolved references)
    - The code will definitely produce wrong results regardless of inputs (clear logic errors)
    - Clear, unambiguous CLAUDE.md and rules (`.claude/rules/*`) violations where you can quote the exact rule being broken

   Do NOT flag:
    - Code style or quality concerns
    - Potential issues that depend on specific inputs or state
    - Subjective suggestions or improvements

   If you are not certain an issue is real, do not flag it. False positives erode trust and waste reviewer time.

   Each subagent should be given the author-intent summary (commit log or PR description) as context.

4. For each issue from agents 1, 2 and 5, launch parallel subagents to validate it. They get the author-intent summary and the issue description, and must confirm with high confidence that the issue is real. For CLAUDE.md issues, confirm the rule is scoped for this file and actually violated. Use sonnet subagents.

5. Filter out any issues that were not validated in step 4. Together with the `/code-review` findings this is the list of high signal issues.

6. Act on the flags:
    - `--comment`: post each verified finding from agents 1, 2 and 5 as its own PR comment (`gh pr comment`; inline via `gh pr review` / `gh api` where a line is known). Each comment is 1-3 sentences: the problem, why it matters (quote the rule if one applies), the fix. No preamble, no praise, no summary comment, no repeating the report.
    - `--fix`: apply the verified findings from agents 1, 2 and 5 to the working tree (bugs are fixed by `/code-review --fix`) and list what changed. Then commit:
        - No fix applied -> no commit.
        - Stage by path only the files the fixes changed (never `git add -A`/`.`). Files dirty before the run (step 1) are not staged; list them in the output.
        - One Conventional Commit following the repo's recent `git log` style. Body: one or two lines, then the `Co-Authored-By` trailer from the session's attribution instructions.
        - Never push.
    - `--fix` and a fix was applied: run the harness harvest (`.claude/work-tickets/tickets.md`, "Harness harvest") over the findings applied, with the finding as evidence instead of a PR comment link.

7. Output a summary of the review findings to the terminal:
    - If issues were found, list each issue with a brief description.
    - With `--fix`, show the commit SHA and subject (or why nothing was committed).
    - If no issues were found, state: "No issues found. Checked for bugs and CLAUDE.md compliance."

8. Write the detailed review to `.user/reviews/<slug>-review.md` (`<slug>` from the target or branch name).

Use this list when evaluating issues (these are false positives, do NOT flag):

- Pre-existing issues
- Something that appears to be a bug but is actually correct
- Pedantic nitpicks that a senior engineer would not flag
- Issues that a linter will catch (do not run the linter to verify)
- General code quality concerns (e.g., lack of test coverage, general security issues) unless explicitly required in CLAUDE.md
- Issues mentioned in CLAUDE.md but explicitly silenced in the code (e.g., via a lint ignore comment)


Notes:

- Create a todo list before starting.
- Without `--comment`, post nothing to GitHub.
