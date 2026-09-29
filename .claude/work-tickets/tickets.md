# Ticket shape (/work-tickets)

Tickets are GitHub issues in this repo (`gh issue …`). Their state is a label: `triage`, `ready`, `question`,
`plan`, `review`, or closed. `triage` tickets (filed by `/architecture-loop`, which also adds `arch-loop`)
aren't worked until a human relabels them `ready`. Closed issue **#81** (village placement rules) is the reference shape of a finished
ticket.

The issue title is the ticket title. The body has a `**Type:** … · **Area:** …` metadata line, a
problem/question section, a `## Tasks` checklist (`- [ ]` lines) and an optional `## Reference` section. A
ticket that went through `/plan-tickets` also has `**Plan:** approved (YYYY-MM-DD)` on the metadata line and
a `## Plan` section. Tickets reference each other as `#NNN`.

**Editing an issue body** (tick boxes, append sections): `gh issue view NNN --json body -q .body >
<repo>/.user/issue-NNN.md`, edit that file, `gh issue edit NNN --body-file <repo>/.user/issue-NNN.md`, then
`rm` the file. **Changing state**: `gh issue edit NNN --remove-label <old> --add-label <new>`.
