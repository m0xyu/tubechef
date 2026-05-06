# TubeChef — Copilot Instructions

TubeChef is a Laravel 11 / PHP 8.4 JSON API backend. It accepts a YouTube cooking-video URL, fetches metadata via the YouTube Data API v3, and triggers an async Gemini AI job that extracts structured recipe data (ingredients, steps, tips). The frontend is a separate React SPA repo.

---

## Commands

```bash
# Start / stop dev environment (Docker / Laravel Sail)
make sail-up
make sail-down

# Run all tests (Pest, inside Sail container)
make sail-test

# Run a single test file or describe block
./vendor/bin/sail artisan test --filter="VideoControllerTest"
./vendor/bin/sail artisan test tests/Feature/VideoControllerTest.php

# Static analysis (PHPStan level 6)
make stan

# Test coverage
make sail-test-coverage
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
