# Ticket shape (/work-tickets)

Tickets are GitHub issues in this repo (`gh issue …`). Their state is a label: `ready`, `question`, `plan`,
`review`, or closed. The shape below is the reference for a finished ticket.

The issue title is the ticket title. The body has a `**Type:** … · **Area:** …` metadata line, a
problem/question section, a `## Tasks` checklist (`- [ ]` lines) and an optional `## Reference` section. A
ticket that went through `/plan-tickets` also has `**Plan:** approved (YYYY-MM-DD)` on the metadata line and
a `## Plan` section. Tickets reference each other as `#NNN`.

**Playwright field.** Every ticket's metadata line carries a browser decision with its reason:
`· **Playwright:** no — <why>` or `· **Playwright:** yes — <why>; check: <what to verify in the browser>`.
- **Scope:** it covers both the `mcp__playwright__*` / `mcp__plugin_playwright_*` MCP tools **and** new or
  changed Pest browser tests in `tests/Browser`. `no` forbids both; `yes` allows both, limited to the
  `check:` list. The existing browser suite still runs as part of the CI gates either way.
- **`yes` only when** the behaviour can only be seen or reproduced in a real browser (focus, dialogs,
  client-side state, JS-only rendering, layout regressions) and Feature/Unit tests can't cover it.
  Otherwise `no` (backend logic, validation, Inertia props, docs, refactors).
- **Who sets it:** `/report-ticket`, `/plan-tickets` and `/answer-tickets` propose it (asking the human
  only for close calls); the verifier, `/architecture-loop` and the implementer's open-question issues set
  `no — <why>` (or `yes` with a reason and `check:` when the finding is browser-only) when they file a ticket.
- **Missing field = `no`.** Older tickets without it are treated as `no` until someone edits them.

**Editing an issue body** (tick boxes, append sections): `gh issue view NNN --json body -q .body >
<repo>/.user/issue-NNN.md`, edit that file, `gh issue edit NNN --body-file <repo>/.user/issue-NNN.md`, then
`rm` the file. **Changing state**: `gh issue edit NNN --remove-label <old> --add-label <new>`.
