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
final readonly class ApplySomething
{
    public function __construct(
        private SomeDependency $dependency,
    ) {}

    public function handle(Something $request): Something
    {
        $this->assertPreconditions($request);

        // Business logic here...

        return $request;
    }

    private function assertPreconditions(Something $request): void
    {
        if ($request->isBlocked()) {
            throw new SomethingException('Cannot do something.');
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
