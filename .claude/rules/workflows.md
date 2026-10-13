---
paths:
  - "app/Workflows/**"
  - "app/Pipes/**"
---

# Workflows

A Workflow answers **"run this multi-step flow."** It is the orchestration kind:
it sequences Actions, Services and queued work, and performs no write itself.

## Rules
- Workflows are `final readonly` classes in `app/Workflows/`, organized by domain
  (`Workflows/Import/`). Never extend a base class.
- One public method only: `handle(...)` (may also have `__invoke`). Verb-phrase name,
  no `Workflow` suffix — the namespace carries the kind.
- Use constructor property promotion for dependencies.
- A Workflow **never writes directly**: no `->save()`, no `->create()`, no `DB::`.
  Every write goes through an Action.
- A Workflow **may** call Actions, call Services, dispatch queued Jobs, and dispatch
  domain events.
- A Workflow is not an entry point. A controller, command, job or listener calls it.
- Workflows depend on `App\Contracts`, never on a concrete Service (DIP).

## Pattern

```php
final readonly class IngestProviderMessages
{
    public function __construct(
        private ImporterRegistry $importers,
    ) {}

    public function handle(ProviderPayload $payload): void
    {
        foreach ($payload->messages() as $message) {
            ImportMessageJob::dispatch($message);
        }
    }
}
```

## Jobs and job count

A Job is an **async boundary, not a step**. Add a Job class only when work must
cross into the queue — a Workflow with three steps is one Workflow, not three jobs.
An effect the write *caused* (cache warm-up, index update, notification) is a queued
listener on the domain event, not a dispatch inside the flow. See `events.md`.

## Strategy, Registry, Pipeline

Strategy is a pattern, not a layer: the interface lives in `app/Contracts`, the
implementation in the tier its behavior puts it in.

- Strategy that runs a flow (per-provider importers) → Workflow.
- Strategy that answers a question (a scorer, a matcher, an import guard) → Service.
- The class that picks one (`ImporterRegistry`) → Service.
- Pipeline → Workflow. `app/Pipes/` holds the steps a pipeline runs; a pipe is never
  an entry point and follows the rules of the tier it belongs to — a pipe that
  answers a question obeys `services.md`, a pipe that writes delegates to an Action.

## When to Use

| Use a Workflow For | Not For |
|--------------------|---------|
| Fan-out: split a payload, queue one job per item | A single write (use an Action) |
| A flow spanning several Actions across writes | Two sub-Actions completing one write (Action composition) |
| Sequencing Actions, Services and queued steps | Answering a question (use a Service) |
| A per-provider / per-strategy flow behind a Contract | Reacting to a mutation someone else made (use a queued listener) |
