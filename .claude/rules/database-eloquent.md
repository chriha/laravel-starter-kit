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
final class User extends Model
{
    #[\Override]
    public function newEloquentBuilder($query): UserEloquentBuilder
    {
        return new UserEloquentBuilder($query);
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
        return $query->whereBetween('date_of_birth', [...]);
    }
}

// Usage: User::query()->tap(new AgeBetween(7, 10))->get()
```

- Set casts via `casts()` method, not `$casts` property. Follow existing model conventions.

## Factories
- Use factories only for tests.
- Do NOT use the `HasFactory` trait on Models.
- Call factories directly: `TeamFactory::new()->create([...])`.
- `definition()` should set a valid default state.
- Use separate state methods for different states:

```php
final class TeamFactory extends Factory
{
    public function definition(): array
    {
        return ['order' => 0, 'phase' => 1];
    }

    public function withUser(): self
    {
        return $this->state(fn (): array => ['users' => [...]]);
    }
}
```

## Migrations
- Never write `down()` methods. Write a new `up()` migration instead.
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
