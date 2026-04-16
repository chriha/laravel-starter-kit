---
paths:
  - "app/**/*.php"
---

# Project Architecture

## Layer Responsibilities

| Layer | Purpose | Examples |
|-------|---------|----------|
| Input | Receive and parse requests | FormRequest, DTOs |
| Delegation | Route to appropriate handlers | Controller, Console Command |
| Orchestration | Coordinate multiple operations | Action |
| Business Logic | Core domain rules | Action, Services, Models |
| Utilities | System-level operations | Services, Support |

Input and delegation layers should be thin. Validate, authorize, and delegate.

## File Structure

```
app/
├── Actions/          # State mutation operations (create/update/delete)
├── Console/Commands/ # Thin artisan commands
├── Contracts/        # Interfaces
├── DataObjects/      # Immutable data containers
├── Enums/            # Type-safe enumerations
├── Events/           # Domain events
├── Exceptions/       # Custom exceptions
├── Features/         # Laravel Pennant feature flags
├── Http/Controllers/ # Thin controllers
├── Http/Requests/    # Form requests
├── Http/Resources/   # API resources (versioned V1/, V2/)
├── Imports/          # Data import implementations
├── Jobs/             # Queued jobs (thin, delegate to actions)
├── Listeners/        # Event listeners
├── Models/           # Eloquent models
├── Observers/        # Model observers
├── Policies/         # Authorization policies
├── Rules/            # Custom validation rules
├── Services/         # Reusable business logic (no state mutation)
└── Support/          # Utilities and helpers
├── Queries/          # Complex query builders
├── Validation/       # Reusable validation rule providers
```

Organize by domain within each directory: `Actions/Users/`, `Actions/Teams/`.

## Naming Conventions

| Element | Convention | Example                              |
|---------|-----------|--------------------------------------|
| PHP Classes | PascalCase | `UserController`, `CreateUser`       |
| PHP Methods | camelCase | `getUserName()`, `processOrder()`    |
| PHP Variables | camelCase | `$firstName`, `$orderTotal`          |
| Route URLs | kebab-case | `/user-profiles`, `/api/v1/teams`    |
| Route Names | snake_case with dots | `user_profiles.show`, `orders.index` |
| Database Tables | snake_case (plural) | `users`, `order_items`               |
| Database Columns | snake_case | `created_at`, `first_name`           |
| Config Files | kebab-case | `pdf-generator.php`                  |
| Config Keys | snake_case | `max_upload_size`, `api_key`         |
| Artisan Commands | kebab-case | `delete-old-records`                 |
| Enum Cases | screaming snake_case | `OrderStatus::PENDING`               |

## Required Suffixes

Use suffixes to make class purpose unambiguous:

`Controller`, `Command`, `Job`, `Request`, `Resource`, `Service`, `Action`, `Query`, `Policy`, `Observer`, `Listener`, `Exception`, `Rule`, `Scope`, `Factory`, `Seeder`, `Provider`, `Middleware`, `Event`, `Notification`, `Mail`, `Cast`
