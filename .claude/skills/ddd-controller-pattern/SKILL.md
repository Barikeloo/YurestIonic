---
name: ddd-controller-pattern
description: Apply the project's DDD/Hexagonal pattern to HTTP endpoints in the Laravel backend. Use when creating a new controller, refactoring an existing one, reviewing HTTP code, creating a use case, or fixing inconsistencies between modules. Triggers when working under `backend/app/<Module>/Infrastructure/Entrypoint/Http/` or `backend/app/<Module>/Application/`.
---

# DDD / Hexagonal HTTP pattern

This project follows DDD with hexagonal layering. Every HTTP endpoint must be wired the same way. If you see a controller, use case, or response that does not match this pattern, it is a candidate for refactor.

## Layer responsibilities

| Layer | Path | Owns | Forbidden |
|---|---|---|---|
| **Domain** | `app/<Module>/Domain/` | Entities, Value Objects, Repository Interfaces, Domain Exceptions | Eloquent, HTTP, Laravel facades |
| **Application** | `app/<Module>/Application/<UseCase>/` | Use cases, Command DTOs, Response DTOs | Eloquent, HTTP, `request()`, sessions |
| **Infrastructure / Persistence** | `app/<Module>/Infrastructure/Persistence/` | Eloquent models, Repository implementations | Anything HTTP |
| **Infrastructure / Entrypoint / Http** | `app/<Module>/Infrastructure/Entrypoint/Http/` | Controllers, FormRequests, route-facing concerns | Domain logic, business rules |

**Rule of thumb:** the Application layer must compile and run if you swap Laravel for any other framework. If a use case imports `Illuminate\*` (other than via injected interfaces it owns), it is wrong.

## The 8 building blocks of an endpoint

For every HTTP endpoint, these files must exist:

```
app/<Module>/
├── Domain/
│   ├── Exception/<ErrorName>Exception.php          # one per business error
│   └── Interfaces/<Repo>RepositoryInterface.php    # if persistence is needed
├── Application/<UseCase>/
│   ├── <UseCase>.php                               # the use case
│   ├── <UseCase>Command.php                        # input DTO
│   └── <UseCase>Response.php                       # output DTO (success only)
└── Infrastructure/
    ├── Persistence/Repositories/Eloquent<Repo>Repository.php
    └── Entrypoint/Http/
        ├── <Action>Controller.php
        └── Requests/<Action>Request.php            # FormRequest with toCommand()
```

## Concrete templates

### 1. Domain Exception (one file per business error)

```php
<?php

namespace App\<Module>\Domain\Exception;

final class UserNotFoundException extends \DomainException
{
    public static function withEmail(string $email): self
    {
        return new self("User with email {$email} not found.");
    }
}
```

**Rules:**
- Always extend `\DomainException` (project convention — never `\Exception` or `\RuntimeException`).
- One file per error concept. Do not group multiple errors in one file.
- Use named constructors (`::withEmail(...)`, `::withId(...)`) when context helps; default constructor with hardcoded message is fine when there is no context.
- Place under `app/<Module>/Domain/Exception/`.

### 2. Command DTO (input to use case)

```php
<?php

namespace App\<Module>\Application\<UseCase>;

final readonly class <UseCase>Command
{
    public function __construct(
        public string $field1,
        public ?string $field2,
    ) {}
}
```

**Rules:**
- `final readonly`. No setters. No mutation.
- Public properties (no getters needed — it is a transport object).
- Primitives only. No `Request`, no Eloquent, no framework types.

### 3. Response DTO (output of use case — success only)

```php
<?php

namespace App\<Module>\Application\<UseCase>;

final readonly class <UseCase>Response
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $optional,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'optional' => $this->optional,
        ];
    }
}
```

**Rules:**
- **Never** include `$success`, `$statusCode`, or `$message`. If you got a Response, it is a success. Errors are exceptions.
- **Never** add factory methods like `::notFound()`, `::invalidCredentials()`, `::forbidden()`. Those are anti-pattern: they push HTTP semantics into Application.
- `toArray()` is the boundary translator to JSON.

### 4. Use Case

```php
<?php

namespace App\<Module>\Application\<UseCase>;

use App\<Module>\Domain\Exception\<ErrorName>Exception;
use App\<Module>\Domain\Interfaces\<Repo>RepositoryInterface;

class <UseCase>
{
    public function __construct(
        private <Repo>RepositoryInterface $repository,
        // ...other domain interfaces only
    ) {}

    public function __invoke(<UseCase>Command $command): <UseCase>Response
    {
        $entity = $this->repository->find($command->id)
            ?? throw <ErrorName>Exception::withId($command->id);

        // ...domain logic, may throw more domain exceptions...

        return new <UseCase>Response(/* ... */);
    }
}
```

**Rules:**
- Receives a `Command`, returns a `Response`. Single signature.
- **Throws** domain exceptions for business errors. Never returns a "failure response".
- Never imports `Illuminate\*`. Only domain interfaces.
- Orchestrates everything the operation needs (post-success side effects too — recording audit trails, dispatching events). The controller must not have business orchestration.
- Use `?? throw` for "find or fail" patterns.

### 5. FormRequest

```php
<?php

namespace App\<Module>\Infrastructure\Entrypoint\Http\Requests;

use App\<Module>\Application\<UseCase>\<UseCase>Command;
use Illuminate\Foundation\Http\FormRequest;

final class <Action>Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'field1' => ['required', 'string', 'max:255'],
            'field2' => ['nullable', 'string'],
        ];
    }

    public function toCommand(): <UseCase>Command
    {
        return new <UseCase>Command(
            field1: (string) $this->input('field1'),
            field2: $this->input('field2'),
        );
    }
}
```

**Rules:**
- `authorize()` returns `true` unless there is a real authorization rule (then implement it here, not in middleware mixed with logic).
- `rules()` lists all validation. **Never** put `$request->validate(...)` in the controller.
- `toCommand()` is mandatory: it is how HTTP becomes domain. Header/body/query merging logic lives here, not in the controller.

### 6. Controller

```php
<?php

namespace App\<Module>\Infrastructure\Entrypoint\Http;

use App\<Module>\Application\<UseCase>\<UseCase>;
use App\<Module>\Domain\Exception\<ErrorA>Exception;
use App\<Module>\Domain\Exception\<ErrorB>Exception;
use App\<Module>\Infrastructure\Entrypoint\Http\Requests\<Action>Request;
use Illuminate\Http\JsonResponse;

final class <Action>Controller
{
    public function __construct(
        private <UseCase> $useCase,
    ) {}

    public function __invoke(<Action>Request $request): JsonResponse
    {
        try {
            $response = ($this->useCase)($request->toCommand());
        } catch (<ErrorA>Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (<ErrorB>Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);
            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        // HTTP-only side effects allowed here (session, cookies, headers).
        return new JsonResponse($response->toArray(), 200);
    }
}
```

**Rules:**
- Inject the FormRequest as the typed parameter. Laravel resolves it.
- Inject only the use case (and HTTP-specific services like `TenantContext` if needed). Never inject repositories, hashers, or domain interfaces.
- `try/catch` always covers two levels:
  - Specific domain exceptions → mapped to semantic HTTP status (401/403/404/409/422).
  - Final `\Throwable` catch → `report($e)` + 500 with a generic message. Never leak internals to the client.
- HTTP-only side effects (session regenerate, cookies, headers) are allowed **after** the use case succeeds. Anything that is not HTTP belongs in the use case.

### 7. Repository Interface (Domain)

```php
<?php

namespace App\<Module>\Domain\Interfaces;

use App\<Module>\Domain\Entity\<Entity>;

interface <Entity>RepositoryInterface
{
    public function findById(string $id): ?<Entity>;
    public function save(<Entity> $entity): void;
}
```

### 8. Repository Implementation + Binding

Implementation:

```php
<?php

namespace App\<Module>\Infrastructure\Persistence\Repositories;

use App\<Module>\Domain\Interfaces\<Entity>RepositoryInterface;

final class Eloquent<Entity>Repository implements <Entity>RepositoryInterface
{
    // Eloquent calls here. Domain logic stays out.
}
```

Binding (always required):

`app/Providers/AppServiceProvider.php` → in `register()`:
```php
$this->app->bind(<Entity>RepositoryInterface::class, Eloquent<Entity>Repository::class);
```

**Rule:** if you create a new interface, you must also add the binding in the same change. Never assume Laravel auto-binds — it does not.

## Encapsulation rule: Tell, Don't Ask

If a use case needs to perform a check on an entity that involves a private field, the method belongs **on the entity**, not in the use case.

**Wrong:**
```php
$this->hasher->verify($plain, $user->passwordHash()->value());
```

**Right:**
```php
// in the entity
public function verifyPassword(string $plain, PasswordHasherInterface $hasher): bool
{
    return $hasher->verify($plain, $this->passwordHash->value());
}

// in the use case
$user->verifyPassword($plain, $this->hasher);
```

The entity protects its invariants. Getters that exist only to feed external logic are a smell.

## Where things must NOT live

| Mistake | Where it goes instead |
|---|---|
| `$request->validate(...)` in controller | FormRequest `rules()` |
| Reading headers / merging body+header in controller | FormRequest `toCommand()` |
| `if ($response->success)` branching | Throw domain exception, catch in controller |
| Eloquent query in use case | Repository method |
| Eloquent query in domain entity | Repository method |
| `now()`, `Auth::user()`, `request()` in use case | Pass via Command, or inject a domain interface |
| Side effects (event log, audit) orchestrated in controller | Inside the use case |
| `try/catch` with no specific catch + only `\Exception` | Catch each domain exception explicitly, then `\Throwable` last |
| Multiple errors in one exception class | One file per business error |
| `Response::notFound()` / `Response::failed()` factories | Domain exceptions instead |

## Refactor procedure (existing controller → pattern)

When fixing an inconsistent endpoint, follow this exact order to keep the codebase compilable at every step:

1. **Read the current controller, use case, response.** List every error path that returns a non-2xx response.
2. **Create one Domain Exception per error path** (extending `\DomainException`). Do not delete the old code yet.
3. **Add/extend the Repository Interface** if the use case references a service that touches persistence. Implement on the Eloquent repo. Add binding in `AppServiceProvider`.
4. **Create the Command DTO.** Move the use case's `__invoke` parameters into it.
5. **Refactor the Response DTO:** remove `success`/`statusCode`/`message` and any failure factory methods. Keep only the success constructor and `toArray()`.
6. **Refactor the use case:** accept Command, throw exceptions instead of returning failure responses, move post-success side effects from controller into here.
7. **Create the FormRequest** with `rules()` and `toCommand()`.
8. **Refactor the controller:** inject FormRequest, wrap use case call in `try/catch` with one block per domain exception + final `\Throwable`. Remove validation, business orchestration, and any direct Eloquent.
9. **Grep for callers** of the removed factory methods (`Response::notFound`, etc.) and the old use case signature. Fix them.
10. **Verify route registration in `routes/api.php` (or `web.php`)** — controller class name may have changed.

## When NOT to apply this pattern

- One-off CLI commands or queue listeners that have no HTTP boundary do not need a FormRequest. They still need Command + Use Case + Domain Exceptions.
- Internal cron/scheduler tasks: same as above.
- Read-only diagnostic endpoints (health checks): a thin controller without a use case is acceptable.

## Reference: this project's conventions

- All Repository bindings live in `backend/app/Providers/AppServiceProvider.php` `register()`.
- Existing examples that follow the pattern correctly:
  - `backend/app/User/Application/AuthenticateForDeviceLink/`
  - `backend/app/User/Infrastructure/Entrypoint/Http/LoginForDeviceLinkController.php`
  - `backend/app/User/Infrastructure/Entrypoint/Http/Requests/LoginForDeviceLinkRequest.php`
- Existing examples that **do NOT yet follow** the pattern (refactor candidates):
  - `backend/app/User/Application/AuthenticateUser/` — Response still has success/statusCode/message
  - `backend/app/User/Infrastructure/Entrypoint/Http/LoginController.php` — validates inline, uses `QuickAccessRecorder` service directly
  - `backend/app/User/Infrastructure/Entrypoint/Http/LoginByPinController.php` — same as above
  - Many controllers under `backend/app/Sale/Infrastructure/Entrypoint/Http/` — validation inline, but try/catch already in place
- The legacy `backend/app/User/Infrastructure/Services/QuickAccessRecorder.php` exists only to keep `LoginController` and `LoginByPinController` working until they are refactored. When both are migrated to the pattern, delete `QuickAccessRecorder` — its logic now lives in `EloquentUserQuickAccessRepository::recordAccess`.
