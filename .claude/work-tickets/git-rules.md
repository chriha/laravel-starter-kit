# Git rules (/work-tickets)

- Never touch the main checkout's branch or working tree — all code work happens in worktrees.
- Every ticket branch is `ticket/NNN-<slug>`, cut from `origin/<base>` — `main`, or the branch it's
  stacked on when the prompt gives one. Push only that branch, only to `origin`. Never push to `main` or
  to the base branch, never force-push, never rewrite history (`rebase`, `reset --hard`,
  `commit --amend`, `stash`). Integrating a newer base into a ticket branch is a `git merge origin/<base>`.
- PRs always target `main` (or the ticket branch they're stacked on).
- PRs are always opened as **draft**; only the implementer runs `gh pr ready`, and only when the ticket is
  done with tests green.
  Nobody runs `gh pr merge`.
