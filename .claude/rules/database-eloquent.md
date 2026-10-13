---
paths:
  - "app/Models/**"
  - "database/migrations/**"
  - "database/factories/**"
  - "app/Observers/**"
---

# Database & Eloquent

## Models
- Use custom EloquentBuilder classes for models with 5+ query scopes:

```php
class Transfer extends Model
{
    #[\Override]
    public function newEloquentBuilder($query): TransferEloquentBuilder
    {
        return new TransferEloquentBuilder($query);
    }
}
```

- Use invokable scope classes for reusable scopes:

```php
final readonly class AgeBetween
{
    public function __construct(public int $min, public int $max) {}

    public function __invoke(Builder $query): Builder
    {
        return $query->whereBetween('year_of_birth', [...]);
    }
}

// Usage: Transfer::query()->tap(new AgeBetween(7, 10))->get()
```

- Set casts via `casts()` method, not `$casts` property. Follow existing model conventions.

## Factories
- Use factories only for tests.
- Models that have a factory use the `HasFactory` trait.
- Call factories via the model: `Transfer::factory()->create([...])`.
- `definition()` should set a valid default state.
- Use separate state methods for different states:

## Migrations
- Don't write `down()` methods by default. Fix forward with a new `up()` migration instead.
- Exception: when `up()` moves or destroys existing data (e.g. copies a column into a new table, then drops it), write a `down()` that restores that data. Cover the rollback with a test.
- This applies to new migrations only. Leave the `down()` methods of existing migrations as they are.
- Use `shouldRun()` to conditionally control migration execution.
- When modifying a column, include ALL previously defined attributes — otherwise they are dropped.

## Query Optimization
- Eager load relationships to prevent N+1 queries.
- Select only needed columns.
- Use database indexes for frequently queried columns.
- Use chunking for large datasets.
- Prefer `Model::query()` over `DB::` facade.

## Model Observers
- Use observers only for framework-level concerns that apply to ALL instances (UUIDs, audit logs, cascade deletes).
- Do NOT use observers for business behavior. Use Actions instead.
