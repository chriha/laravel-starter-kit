---
paths:
  - "app/Actions/**"
---

# Action Classes

## Rules
- Actions are `final readonly` classes. Never extend a base class.
- One public method only: `handle(...)` (may also have `__invoke`).
- Use constructor property promotion for dependencies.
- Actions perform state mutation: create, update, delete operations.
- Actions may contain lightweight assertions/guards for domain invariants, but do not own full validation.

## Pattern

```php
final readonly class Create
{
    public function __construct(
        private SomeDependency $dependency,
    ) {}

    public function handle(ChangeRequest $request): ChangeRequest
    {
        $this->assertPreconditions($request);

        // Business logic here...

        return $request;
    }

    private function assertPreconditions(ChangeRequest $request): void
    {
        if ($request->isBlocked()) {
            throw new ChangeRequestException('Cannot apply blocked request.');
        }
    }
}
```

## Action Composition

Inject other actions via constructor to orchestrate complex flows:

```php
final readonly class ImportOrders
{
    public function __construct(
        private CreateOrder $createOrder,
        private UpdateOrder $updateOrder,
    ) {}

    public function handle(OrderBatch $batch): void
    {
        // Orchestrate multiple operations
        foreach ($batch->items as $item) {
            $this->createOrder->handle($item);
        }
    }
}
```

## When to Use

| Use Actions For | Don't Use Actions For |
|----------------|----------------------|
| State mutation (create/update/delete) | Simple CRUD used once |
| Complex domain operations | Pure data retrieval (use Query Classes) |
| Orchestrating multiple operations | Utilities/helpers (use Services) |
| Logic reused across controllers, commands, jobs | Simple transformations (use DTOs) |

## Action vs Service

| Aspect | Action | Service |
|--------|--------|---------|
| Purpose | State mutation & orchestration | Complex retrieval or 3rd party integration |
| State | Changes system state | Typically read-only |
| Naming | Verb-based: `CreateUser` | Noun-based: `PaymentService` |
