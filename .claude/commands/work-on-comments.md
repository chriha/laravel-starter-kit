---
allowed-tools: Bash(gh pr list:*), Bash(gh pr view:*), Bash(gh api:*), Bash(git branch:*), Bash(git log:*), Bash(git diff:*), Bash(git add:*), Bash(git commit:*), Bash(gh issue list:*), Bash(gh issue create:*), Bash(gh issue comment:*), Bash(gh label create:*), AskUserQuestion
description: Fetch open PR review comments and work through them systematically
---

Look up the current branch's open PR in GitHub and fetch all review comments.

**Step 1 — Fetch & categorize**

Run in parallel:
- `git branch --show-current`
- `git diff main...HEAD`

Then:
- `gh pr view --json number,url -q '.number'` for the PR of the current branch. None → say so and stop.
- Unresolved review threads (GraphQL, `isResolved` is not in the REST API):
  ```
  gh api graphql -f query='query($o:String!,$r:String!,$n:Int!){repository(owner:$o,name:$r){pullRequest(number:$n){reviewThreads(first:100){nodes{isResolved isOutdated path line comments(first:20){nodes{author{login __typename} body url}}}}}}}' \
    -F o='{owner}' -F r='{repo}' -F n=<PR number>
  ```
  Keep threads with `isResolved == false` whose first comment author `__typename` is `User`. Ignore bots and resolved threads.

If there are no open comments, say so and stop here.

Group every comment into one of three categories:
- 🟢 **Quick fix** — trivial change, low risk (naming, formatting, simple logic)
- 🔴 **Complex** — requires design thinking or significant refactoring
- 💬 **Clarify** — ambiguous intent, or best resolved with a reply

Then scan the branch diff for the *same class of issues* the reviewers flagged but missed elsewhere. Add those as additional items, noting they were self-identified.

**Step 2 — Overview + quick-fix plan**

Present:
1. Short summary of the PR's overall review status
2. Categorized comment list (file + line reference for each)
3. For each quick fix: one sentence on the approach

Then ask: "Shall I work on all the quick fixes?"

**Step 3 — Quick fixes (after approval)**

Work through each quick fix. One focused commit per fix with a clear message. Report when done.

**Step 4 — Complex tasks, one at a time**

For each complex task:
- Read all relevant code before forming an opinion
- Present 1–3 concrete approaches with trade-offs
- Give a clear recommendation and reasoning

Wait for a choice. Implement it, summarize, then wait for confirmation before moving to the next. Repeat until all complex tasks are done.

**Step 5 — Clarifications, one at a time**

For each remaining comment:
- Quote the reviewer's comment
- Provide context: what the code does, relevant classes/methods with line numbers
- Ask how to respond or resolve

Continue one by one until all are addressed.

**Step 6 — Harvest into the harness**

Run the harness harvest (`.claude/work-tickets/tickets.md`, "Harness harvest") over every comment resolved in this session, including the self-identified ones. Skip if none were resolved.
