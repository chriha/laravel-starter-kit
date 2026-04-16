---
paths:
  - "app/Services/**"
---

# Services

- Encapsulate complex retrieval logic, 3rd party integrations, and reusable utilities.
- Services are stateless, focused on one domain/integration, and use dependency injection.
- Use interfaces for 3rd party integrations.

```php
final readonly class GoogleMaps implements DeterminesTimezone, GeocodesAddress
{
    public function __construct(
        private HttpClient $http,
        private string $apiKey,
    ) {}

    public function geocode(string $address): ?Result
    {
        // Implementation...
    }
}
```
