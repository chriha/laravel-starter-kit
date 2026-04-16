---
allowed-tools: Bash(git log:*), Bash(git diff:*)
description: Code review the locally checked out branch
---

Always review the current locally checked out branch. Do not look up any PR or MR.

**Agent assumptions (applies to all agents and subagents):**
- All tools are functional and will work without error. Do not test tools or make exploratory calls. Make sure this is clear to every subagent that is launched.
- Only call a tool if it is required to complete the task. Every tool call should have a clear purpose.

To do this, follow these steps precisely:

1. Run `git log main..HEAD --oneline` and `git diff main...HEAD` to understand the changes on the current branch. Use the commit messages as context for the author's intent.

2. Launch a haiku agent to return a list of file paths (not their contents) for all relevant CLAUDE.md files including:
    - The root CLAUDE.md file, if it exists
    - Any CLAUDE.md files in directories containing files modified by the branch

3. Launch 5 agents in parallel to independently review the changes. Each agent should return the list of issues, where each issue includes a description and the reason it was flagged (e.g. "CLAUDE.md adherence", "bug"). The agents should do the following:

   Agents 1 + 2: CLAUDE.md and rules (`.claude/rules/*`) compliance sonnet agents
   Audit changes for CLAUDE.md compliance in parallel. Note: When evaluating CLAUDE.md compliance for a file, you should only consider CLAUDE.md files that share a file path with the file or parents.

   Agent 3: Opus bug agent (parallel subagent with agents 4 and 5)
   Scan for obvious bugs. Focus only on the diff itself without reading extra context. Flag only significant bugs; ignore nitpicks and likely false positives. Do not flag issues that you cannot validate without looking at context outside of the git diff.

   Agent 4: Opus bug agent (parallel subagent with agents 3 and 5)
   Look for problems that exist in the introduced code. This could be security issues, incorrect logic, etc. Only look for issues that fall within the changed code.

   Agent 5: Sonnet test quality agent (parallel subagent with agents 3 and 4)
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

   Each subagent should be given the git log summary as context for the author's intent.

4. For each issue found in the previous step by agents 3 and 4, launch parallel subagents to validate the issue. These subagents should get the git log summary along with a description of the issue. The agent's job is to review the issue to validate that the stated issue is truly an issue with high confidence. For example, if an issue such as "variable is not defined" was flagged, the subagent's job would be to validate that is actually true in the code. Another example would be CLAUDE.md issues. The agent should validate that the CLAUDE.md rule that was violated is scoped for this file and is actually violated. Use Opus subagents for bugs and logic issues, and sonnet agents for CLAUDE.md violations.

5. Filter out any issues that were not validated in step 4. This step will give us our list of high signal issues for our review.

6. Output a summary of the review findings to the terminal:
    - If issues were found, list each issue with a brief description.
    - If no issues were found, state: "No issues found. Checked for bugs and CLAUDE.md compliance."

7. Add your detailed review as markdown file in `.user/reviews/`.

Use this list when evaluating issues (these are false positives, do NOT flag):

- Pre-existing issues
- Something that appears to be a bug but is actually correct
- Pedantic nitpicks that a senior engineer would not flag
- Issues that a linter will catch (do not run the linter to verify)
- General code quality concerns (e.g., lack of test coverage, general security issues) unless explicitly required in CLAUDE.md
- Issues mentioned in CLAUDE.md but explicitly silenced in the code (e.g., via a lint ignore comment)

Notes:

- Create a todo list before starting.
- Do not post any GitHub or GitLab comments. Output only to the terminal.
