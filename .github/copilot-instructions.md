# TubeChef — Copilot Instructions

TubeChef is a **monorepo** with two services:
- **Laravel 11 / PHP 8.4 JSON API** (`/`) — accepts YouTube cooking-video URLs, fetches metadata via YouTube Data API v3, and dispatches an async job that calls the Go microservice to extract structured recipe data.
- **`ai-recipe-service/`** — a Go (chi v5) microservice on port 3000 that wraps the Gemini API for recipe extraction.

The frontend is a separate React SPA repo.

---

## Commands

```bash
# Start / stop dev environment (Docker / Laravel Sail)
make sail-up
make sail-down

# Run all tests (PHP + Go)
make test-all

# Run PHP tests only
make test                  # php artisan test

# Run a single test file or describe block
php artisan test --filter="VideoControllerTest"
php artisan test tests/Feature/VideoControllerTest.php

# Static analysis (PHPStan level max)
make stan

# Test coverage
make test-coverage

# Go service (ai-recipe-service/)
make go-run    # go run ./cmd/api/main.go
make go-test   # go test ./...
make go-lint   # gofmt + goimports + golangci-lint
make go-build
make go-tidy   # go mod tidy
```

---

## Architecture

### Laravel PHP service

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
    Gemini/         # Low-level Gemini HTTP client (kept for reference; not the active LLM path)
    YouTube/        # Low-level YouTube API client
  Jobs/             # GenerateRecipeJob — dispatched after DB transaction commits
  Models/           # Eloquent models
  Repositories/     # Repository pattern with Contracts/ interface
  Rules/            # Custom validation rules
  Services/
    LLM/            # LLMServiceInterface + GoLLMService (active) + GeminiService (legacy)
    RecipeService/  # Persists LLM output to DB
  ValueObjects/     # YouTubeVideoId etc. — immutable, self-validating
```

### Go microservice (`ai-recipe-service/`)

```
domain/         # Pure types and sentinel errors — no external dependencies
  recipe.go     # GeneratedRecipe, VideoInput, Ingredient, RecipeStep, RecipeTip
  llm.go        # LLMClient interface, LLMResult, LLMMetadata, UsageMetadata
  errors.go     # Sentinel errors (ErrNotRecipeError, ErrGenerationFailed, etc.)
internal/
  gemini/       # Gemini HTTP client implementing domain.LLMClient
  recipe/       # Service wrapping LLMClient; rejects non-recipe results
  handler/      # HTTP handlers (RecipeGenerator interface for testability)
  utils/        # Shared response helpers (ErrorResponse, CreatedResponse, etc.)
  testutil/     # MockLLMClient, MockRecipeGenerator, SampleLLMResult()
  config/       # Config loading
cmd/api/        # main.go — wires everything together
```

### Request lifecycle

1. `POST /api/videos` → `VideoController::store` → `StoreVideoWorkflowAction`
2. `StoreVideoWorkflowAction`: acquires a `Cache::lock`, fetches YouTube metadata, stores it in DB inside a transaction, marks status `processing`, then dispatches `GenerateRecipeJob` via `DB::afterCommit()`
3. `GenerateRecipeJob` → `GenerateRecipeAction` → calls `LLMServiceInterface::generate()` → `GoLLMService` POSTs to `http://ai-recipe-service:3000/generate` → parses result into `GeneratedRecipeData` DTO → stores via `RecipeService`
4. Frontend polls `GET /api/videos/{videoId}/status` until status is `completed` or `failed`

### LLM service wiring

`LLMServiceInterface` is bound to `GoLLMService` in `AppServiceProvider` (not via `LLMServiceFactory`). `GoLLMService` POSTs to the Go microservice. `GeminiService` exists but is not the active binding — do not use it for new work.

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

---

## Key Conventions

### PHP: Error handling

Errors are PHP 8.1 backed enums under `App\Enums\Errors\*`, annotated with the custom `#[ErrorDetails(message: '…', statusCode: 422)]` attribute. Domain exceptions extend `BaseException` and accept the error enum in their constructor:

```php
throw new RecipeException(RecipeError::GENERATION_FAILED, previous: $e);
```

Each exception implements `render()` to return a consistent JSON shape:
```json
{ "success": false, "error_code": "generation_failed", "message": "…" }
```

Never throw generic `\Exception` from domain code — always use the typed exceptions.

### PHP: DTOs

All DTOs are `final readonly class` with a static `fromArray(array $data): self` factory. Validate the raw array inside `fromArray` with inline type-checks (no external validation library).

### PHP: Actions

Every `Action` class has exactly one public method: `execute()`. Actions are injected via constructor (Laravel DI). Controllers call `$action->execute(...)` and return the result directly as an API Resource or JSON response.

### PHP: Value Objects

Parse and validate external IDs (e.g., YouTube video ID) through Value Objects (`YouTubeVideoId::fromUrl()`, `YouTubeVideoId::fromString()`). Pass the Value Object — never a raw string — through the call chain.

### PHP: Concurrency control

Use `Cache::lock($key, $ttl)` for atomic operations that must not run twice simultaneously (e.g., `StoreVideoWorkflowAction`). Always release the lock in a `finally` block.

### PHP: Job dispatch

Always dispatch jobs with `DB::afterCommit(fn() => MyJob::dispatch(...))` so the job only runs after the surrounding transaction has committed.

### PHP: Authentication

Sanctum SPA auth. All write endpoints require `auth:sanctum` middleware. Rate limiting is applied per route group (`throttle:youtube-api`, `throttle:gemini-generator`).

### PHP: Testing

Tests use **Pest** with Laravel's feature-test helpers. External HTTP calls (YouTube API, Gemini API, Go service) are always faked with `Http::fake([...])`. Queues are faked with `Queue::fake()`. Factories live in `database/factories/`.

### PHP: Cache invalidation

`RecipeObserver` flushes the `recipes` cache tag on every Recipe `created`/`updated` event. This requires Redis with tag support. Use `Cache::tags(['recipes'])` when caching recipe queries so they are automatically invalidated.

### Go: Domain layer

Keep `domain/` free of external dependencies — only stdlib. Sentinel errors live in `domain/errors.go`; use `errors.Is()` for matching. All domain types (`GeneratedRecipe`, `VideoInput`, etc.) are plain structs defined in `domain/`.

### Go: Error-to-HTTP mapping

`utils.ErrorResponse` maps sentinel errors from `domain/errors.go` to HTTP status codes. When adding new error types, add the sentinel to `domain/errors.go` and add a corresponding `errors.Is` case in `utils/response.go`.

### Go: Handler interfaces

Handlers depend on narrow interfaces (e.g., `RecipeGenerator` in `handler/generate.go`), not on concrete types. This makes handler tests straightforward — use `testutil.MockRecipeGenerator` or `testutil.MockLLMClient`.

### Go: Testing

Use `testutil.SampleLLMResult()` as the canonical happy-path fixture. Tests for `recipe.Service` inject `testutil.MockLLMClient`; tests for handlers inject `testutil.MockRecipeGenerator`. No external calls in tests.
