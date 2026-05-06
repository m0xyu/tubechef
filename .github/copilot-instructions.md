# TubeChef — Copilot Instructions

TubeChef is a **monorepo** with two services:
- **Laravel 11 / PHP 8.4 JSON API** (`/`) — accepts YouTube cooking-video URLs, fetches metadata via YouTube Data API v3, and dispatches an async Gemini AI job to extract structured recipe data (ingredients, steps, tips).
- **`ai-recipe-service/`** — a Go (chi v5) microservice on port 3000, currently in early development.

The frontend is a separate React SPA repo.

---

## Commands

```bash
# Start / stop dev environment (Docker / Laravel Sail)
make sail-up
make sail-down

# Run all tests (PHP + Go)
make test-all

# Run PHP tests only (Pest, inside Sail container)
make sail-test

# Run a single test file or describe block
./vendor/bin/sail artisan test --filter="VideoControllerTest"
./vendor/bin/sail artisan test tests/Feature/VideoControllerTest.php

# Static analysis (PHPStan level 6 via phpstan.neon; make stan runs at max)
make stan

# Test coverage
make sail-test-coverage

# Go service (ai-recipe-service/)
make go-run    # go run ./cmd/api/main.go
make go-lint   # gofmt + goimports + golangci-lint
make go-build
make tidy      # go mod tidy
```

---

## Architecture

```
app/
  Actions/          # Single-responsibility workflow steps (one public execute() method)
  Dtos/             # Typed data transfer objects (final readonly class, fromArray() factory)
  Enums/
    Errors/         # Error enums annotated with #[ErrorDetails(message, statusCode)]
  Exceptions/       # BaseException subclasses keyed to error enums
  Http/
    Controllers/    # Thin — delegate immediately to Actions
    Requests/       # Form requests for validation
    Resources/      # API resources
  Infrastructure/
    Gemini/         # Low-level Gemini HTTP client
    YouTube/        # Low-level YouTube API client
  Jobs/             # GenerateRecipeJob — dispatched after DB transaction commits
  Models/           # Eloquent models
  Repositories/     # Repository pattern with Contracts/ interface
  Rules/            # Custom validation rules
  Services/
    LLM/            # LLMServiceInterface + LLMServiceFactory + GeminiService
    RecipeService/  # Persists LLM output to DB
  ValueObjects/     # YouTubeVideoId etc. — immutable, self-validating
```

### Request lifecycle

1. `POST /api/videos` → `VideoController::store` → `StoreVideoWorkflowAction`
2. `StoreVideoWorkflowAction`: acquires a `Cache::lock`, fetches YouTube metadata, stores it in DB inside a transaction, marks status `processing`, then dispatches `GenerateRecipeJob` via `DB::afterCommit()`
3. `GenerateRecipeJob` → `GenerateRecipeAction` → calls `LLMServiceInterface::generateStructured()` → parses result into `GeneratedRecipeData` DTO → stores via `RecipeService`
4. Frontend polls `GET /api/videos/{videoId}/status` until status is `completed` or `failed`

### API routes

All routes require `auth:sanctum` except recipe reads:

| Method | Path | Throttle |
|--------|------|----------|
| POST | `/api/videos/preview` | `youtube-api` (10/min per user) |
| POST | `/api/videos` | `gemini-generator` (3/min per user) |
| GET | `/api/videos/{videoId}/status` | — |
| GET/DELETE | `/api/user/library` | — |
| GET | `/api/recipes` | public |
| GET | `/api/recipes/{slug}` | public |

### LLM abstraction

`LLMServiceInterface` is the only dependency injected into Actions. `LLMServiceFactory` resolves the concrete implementation (currently only `GeminiService`). To add a new provider, implement `LLMServiceInterface` and register it in the factory.

---

## Key Conventions

### Error handling

Errors are PHP 8.1 backed enums under `App\Enums\Errors\*`, annotated with the custom `#[ErrorDetails(message: '…', statusCode: 422)]` attribute. Domain exceptions extend `BaseException` and accept the error enum in their constructor:

```php
throw new RecipeException(RecipeError::GENERATION_FAILED, previous: $e);
```

Each exception implements `render()` to return a consistent JSON shape:
```json
{ "success": false, "error_code": "generation_failed", "message": "…" }
```

Never throw generic `\Exception` from domain code — always use the typed exceptions.

### DTOs

All DTOs are `final readonly class` with a static `fromArray(array $data): self` factory. Validate the raw array inside `fromArray` with inline type-checks (no external validation library).

### Actions

Every `Action` class has exactly one public method: `execute()`. Actions are injected via constructor (Laravel DI). Controllers call `$action->execute(...)` and return the result directly as an API Resource or JSON response.

### Value Objects

Parse and validate external IDs (e.g., YouTube video ID) through Value Objects (`YouTubeVideoId::fromUrl()`, `YouTubeVideoId::fromString()`). Pass the Value Object — never a raw string — through the call chain.

### Concurrency control

Use `Cache::lock($key, $ttl)` for atomic operations that must not run twice simultaneously (e.g., `StoreVideoWorkflowAction`). Always release the lock in a `finally` block.

### Job dispatch

Always dispatch jobs with `DB::afterCommit(fn() => MyJob::dispatch(...))` so the job only runs after the surrounding transaction has committed.

### Authentication

Sanctum SPA auth. All write endpoints require `auth:sanctum` middleware. Rate limiting is applied per route group (`throttle:youtube-api`, `throttle:gemini-generator`).

### Testing

Tests use **Pest** with Laravel's feature-test helpers. External HTTP calls (YouTube API, Gemini API) are always faked with `Http::fake([...])`. Queues are faked with `Queue::fake()`. Factories live in `database/factories/`.

Run a single test:
```bash
./vendor/bin/sail artisan test --filter="description or test name"
```

### Cache invalidation

`RecipeObserver` flushes the `recipes` cache tag on every Recipe `created`/`updated` event. This requires Redis with tag support. Use `Cache::tags(['recipes'])` when caching recipe queries so they are automatically invalidated.
