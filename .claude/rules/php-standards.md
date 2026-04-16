---
paths:
  - "app/**/*.php"
  - "tests/**/*.php"
---

# PHP Standards

## Type System
- Always declare explicit return types on all methods and functions.
- Declare `void` for methods that return nothing.
- Use nullable types (`?Type`) when a method can return null.

## Constructor Property Promotion
- Use PHP 8+ constructor property promotion when all properties qualify.

```php
final class OrderProcessor
{
    public function __construct(
        private PaymentGateway $gateway,
        private int $maxRetries = 3,
    ) {}
}
```

## Docblocks
- Only add docblocks that provide value beyond type hints.
- Use one-line docblocks when possible: `/** @var string */`
- Specify both key and value types: `/** @var array<string, int> */`
- Use array shape notation for fixed keys:

```php
/**
 * @param array{name: string, email: string, age: int} $data
 */
```

- Import class names in docblocks; never use fully qualified names.
- Use `/** @return Collection<int, User> */` for generic collections.

## Control Flow
- Use early returns: handle error conditions first, happy path last.
- Avoid `else` statements — use early returns instead.
- Short ternaries on one line; multi-line ternaries get each part on its own line:

```php
$result = $object instanceof Model
    ? $object->name
    : 'A default value';
```

## String Handling
- Prefer interpolation over concatenation: `"Welcome, {$user->name}!"` not `'Welcome, ' . $user->name`.

## Code Readability
- Extract complex conditions into expressive boolean methods: `isEligibleForDiscount()`.
- Reference: [Clean Code PHP](https://github.com/piotrplenik/clean-code-php/tree/master)
