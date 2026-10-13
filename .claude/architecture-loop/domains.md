# Architecture Loop — Domains registry

Read by `/architecture-loop`. Each domain is an audit scope: the paths it owns and the invariants (rule
files / arch tests) that code there must obey. `/architecture-loop <slug>` scans one domain; no argument
scans every `active` one. The loop may append a domain here when an ad-hoc path scan turns up real drift.

Format per domain: `## <slug>` heading, then `status`, `owns_paths`, `invariants` bullets.

## actions
- status: active
- owns_paths: `app/Actions/**`
- invariants: `.claude/rules/actions.md`, `.claude/rules/php-standards.md`, `tests/Architecture/ActionsTest.php`

## controllers
- status: active
- owns_paths: `app/Http/Controllers/**`, `routes/**/*.php`
- invariants: `.claude/rules/controllers-routing.md`, `tests/Architecture/ControllersTest.php`

## validation
- status: active
- owns_paths: `app/Http/Requests/**`, `app/Rules/**`
- invariants: `.claude/rules/validation.md`, `tests/Architecture/RequestsTest.php`

## models
- status: active
- owns_paths: `app/Models/**`, `app/Observers/**`, `database/migrations/**`, `database/factories/**`
- invariants: `.claude/rules/database-eloquent.md`, `tests/Architecture/ModelsTest.php`

## services
- status: active
- owns_paths: `app/Services/**`, `app/Queries/**`, `app/DataObjects/**`
- invariants: `.claude/rules/services.md`, `.claude/rules/query-classes.md`, `.claude/rules/data-objects.md`, `tests/Architecture/ServicesTest.php`

## commands
- status: active
- owns_paths: `app/Console/Commands/**`
- invariants: `.claude/rules/artisan-commands.md`, `tests/Architecture/CommandsTest.php`

## jobs
- status: active
- owns_paths: `app/Jobs/**`
- invariants: `.claude/rules/architecture.md`, `tests/Architecture/JobsTest.php`

## tests
- status: active
- owns_paths: `tests/**`
- invariants: `.claude/rules/testing.md`

## workflows
- status: active
- owns_paths: `app/Workflows/**`, `app/Events/**`, `app/Listeners/**`
- invariants: `.claude/rules/workflows.md`, `.claude/rules/events.md`, `tests/Architecture/EventsTest.php`, `tests/Architecture/ListenersTest.php`
