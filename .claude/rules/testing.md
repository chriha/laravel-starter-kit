---
paths:
  - "tests/**"
---

# Test-Driven Development

Any behavior change is driven test-first via the red → green loop. Pest mechanics
(syntax, datasets, factories, `RefreshDatabase`) live in the `pest-testing` skill; this rule owns the loop.
CI requires 100 % type coverage and exactly 100 % code coverage.

## The loop

- **Red before green.** Write the failing test first; run it and see it fail *for the
  right reason* (an assertion, not a typo/missing class). Filter:
  `php artisan test --compact --filter=<name>`.
- **One cycle at a time.** One seam, one test, minimal code to pass it, repeat. No
  speculative tables/DTOs/config — green with the least code wins.
- **Red means fix the code, not the test.** Loosening an assertion to match output is
  green theater. If the test is genuinely wrong, get user confirmation before editing it.
- **Refactor after green**, under the review stage — not inside the loop.
- **Never batch all tests up front** — that verifies imagined behavior. Grow one behavior
  at a time, each test a tracer bullet responding to the last cycle.

## Seams — where tests go

A **seam** is a public boundary where behavior is observable without reaching inside.
Test at seams, never internals. Good: an Action's `handle()`, a Query result, a Service
method, an HTTP endpoint (assert the JSON, not the rows). Not: Eloquent
internals, observers, private helpers. Name and confirm the seams with the user before
writing any test.

## A good test

Verifies behavior through a public interface, reads like a spec (WHAT not HOW), one
logical assertion, survives refactors.

```php
// GOOD: Action's seam in, Query seam out — not assertDatabaseHas
it('makes an assigned role retrievable via the user', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create();

    app(AssignRole::class)->handle($user, $role);

    expect(app(GetUserRoles::class)->handle($user)->pluck('id'))
        ->toContain($role->id);
});
```

## Anti-patterns

- **Implementation-coupled** — mocking internal collaborators, testing private methods,
  asserting call counts/order (`shouldReceive()->once()`), or verifying via a side
  channel. Tell: breaks on refactor with no behavior change. Read back through the same
  Action/Query/endpoint. `assertDatabaseHas` only when the row *is* the contract (e.g. an
  import); if a read path exists, use it.
- **Tautological** — expected value recomputed the way the code computes it
  (`toBe(array_sum($prices))`), so it can never disagree. Expected values come from an
  independent source: a known literal, a worked example, the spec.

## Mocking — boundaries only

Mock only system boundaries: external APIs/HTTP, time/randomness (`Carbon::setTestNow()`),
filesystem (`Storage::fake()`), and queue/mail/event dispatch (`Queue::fake()`). Prefer the
real SQLite test DB (`RefreshDatabase` + factories) over a mocked one. Never mock your own Actions/Services/Queries or anything
else in the app — it freezes today's call graph into the test. Design boundaries to
substitute: inject deps (don't `new` inside), prefer specific methods (`startList()`,
`results()`) over a generic `get($endpoint, $params)`.

## Planning is cycles, not layers

Plan as a sequence of per-behavior cycles (each: failing test → minimal code), not
"build every layer, then test". **Breadth in the plan, minimalism per cycle:** enumerate
*every* behavior before sequencing — under-listing is a coverage hole that ships as a bug.
Cover happy path; every failure path (timeout, empty/null, downstream down) as its own
cycle; boundaries (empty/zero/one/max, `>=` vs `>`, first/last, rollover, encoding);
every AC and NFR (retention, dedupe, uniqueness); and non-obvious seams (config defaults,
migrations/constraints, scheduler wiring, outbound events/notifications). Close with a
coverage check mapping each source-of-truth item to its cycle.
