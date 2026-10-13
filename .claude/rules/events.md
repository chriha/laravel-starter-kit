---
paths:
  - "app/Events/**"
  - "app/Listeners/**"
---

# Events & Listeners

## Naming — behavior, not data
- Name events after a domain behavior that happened, past tense: `PaymentFailed`, `OrderShipped`, `PasswordReset`.
- Do NOT dispatch generic record-mutation events (`UserUpdated`, `OrderSaved`). They carry no meaning, every listener must re-inspect the model, and they are the main reason event flows become hard to follow.
- One event = one thing that happened. If a listener needs to ask "what changed?", the event is too generic.

## Dispatch
- Dispatch the event from the Action that performed the state change, as one atomic unit: persist the change (inside the DB transaction) **then** dispatch. If the mutation fails, the event must not fire.
- Never dispatch an event to *cause* the state change (no listener that "writes the row the event describes"). State is already persisted by the time listeners run.

## Jobs vs listeners
- **Job** = work the caller asked for. It is an async boundary, not a step. Dispatched by a Workflow (`workflows.md`), never by an Action.
- **Listener** = work the mutation *caused*, which the writer should not have to know about: cache warm-up, search-index update, notification.
- Cache warm-up after an order update is a queued listener on the domain event, **not** a `dispatch()` inside `UpdateOrder`. The listener covers every write path (web, API, import); a dispatch inside one Action covers only the path someone remembered.
- If you are about to add a dispatch to an Action, it is one of the two above: caller-requested → move the flow to a Workflow; consequence → queued listener.

## Listeners
- Domain listeners are **queued** — implement `ShouldQueue`. Do not handle domain events synchronously inside the request.
- Synchronous listeners are only for framework / 3rd-party package events you must react to in-process.
- Each listener is independent and owns its failure: a failing listener must not break the dispatcher or other listeners. Set `$tries`/`backoff` for volatile dependencies (3rd-party APIs); use a job chain when order matters.
- Do not assume listener execution order. Queued listeners may run out of order — never build a listener that depends on another having run first.

## Observers vs events
- Framework-level, every-instance concerns → observer (`database-eloquent.md`).
- Business behavior → meaningful domain event + queued listener.
