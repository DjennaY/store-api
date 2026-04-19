# Store API

REST API for store management — DDD Hexagonal + CQRS, PHP 8.2, MySQL 8, Docker.

## Stack

| Component       | Technology                            |
|-----------------|---------------------------------------|
| Runtime         | PHP 8.2-fpm                           |
| Web server      | Nginx Alpine                          |
| Database        | MySQL 8.0                             |
| Auth            | JWT — firebase/php-jwt                |
| Assertions      | webmozart/assert                      |
| Logging         | Monolog 3 (JSON)                      |
| Quality         | PHPStan level 8 · PHP-CS-Fixer PSR-12 |
| Unit tests      | PHPUnit 11                            |
| HTTP tests      | PHPUnit 11 + Guzzle (live server)     |
| Infra           | Docker Compose · Makefile             |

## Requirements

- Docker
- Make

## Getting Started

```bash
cp .env.example .env
make setup           # build + up + install + migrate + git hooks
make migrate-test    # create and migrate the isolated test database
make test            # unit tests
make test-integration  # HTTP integration tests (isolated test database)
```

The API is available at **http://localhost:8080**.  
Swagger UI is available at **http://localhost:8080/docs/**.

## Make Commands

| Command                | Description                                  |
|------------------------|----------------------------------------------|
| `make setup`           | Full installation (first time)               |
| `make migrate-test`    | Create and migrate the test database         |
| `make create-admin`    | Seed the first admin user (idempotent)       |
| `make up / down`       | Start / stop containers                      |
| `make migrate`         | Run SQL migrations                           |
| `make test`            | Unit tests                                   |
| `make test-integration`| HTTP integration tests (server required)     |
| `make phpstan`         | Static analysis PHPStan level 8              |
| `make cs-fix`          | Auto-fix code style (PSR-12)                 |
| `make cs-check`        | Check code style without modifying           |
| `make ci`              | cs-check + phpstan + test (full pipeline)    |
| `make logs-app`        | Live application logs (JSON)                 |
| `make shell`           | Shell into the PHP container                 |
| `make hooks`           | Install git pre-push hook                    |

## API Endpoints

### Auth

| Method | Endpoint       | Auth | Description       |
|--------|----------------|------|-------------------|
| POST   | /auth/register | ❌   | Create an account |
| POST   | /auth/login    | ❌   | Obtain a JWT      |

### Stores

| Method | Endpoint      | Auth | Role                   | Description        |
|--------|---------------|------|------------------------|--------------------|
| GET    | /stores       | ❌   | —                      | List               |
| GET    | /stores/{id}  | ❌   | —                      | Detail             |
| POST   | /stores       | ✅   | user / admin           | Create             |
| PUT    | /stores/{id}  | ✅   | owner / admin          | Update             |
| DELETE | /stores/{id}  | ✅   | owner / admin          | Soft-delete        |

### Filters — GET /stores

| Parameter   | Example           |
|-------------|-------------------|
| name        | ?name=apple       |
| city        | ?city=paris       |
| country_iso | ?country_iso=FR   |
| sort_by     | ?sort_by=name     |
| sort_order  | ?sort_order=ASC   |
| limit       | ?limit=10         |
| offset      | ?offset=20        |

## cURL Examples

```bash
# Register
curl -X POST http://localhost:8080/auth/register \
  -H "Content-Type: application/json" \
  -d '{"first_name":"Jean","last_name":"Dupont","email":"jean@example.com","password":"securepass123"}'

# Login
curl -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"jean@example.com","password":"securepass123"}'

# Create a store
curl -X POST http://localhost:8080/stores \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{"name":"Mon Magasin","address":"1 rue de la Paix","city":"Paris","zip_code":"75001","country_iso":"FR","phone":"+33123456789"}'

# List with filters
curl "http://localhost:8080/stores?city=Paris&sort_by=name&sort_order=ASC&limit=10"

# Update
curl -X PUT http://localhost:8080/stores/<ID> \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{"name":"New Name","address":"2 rue Test","city":"Lyon","zip_code":"69001","country_iso":"FR","phone":"+33456789012"}'

# Delete
curl -X DELETE http://localhost:8080/stores/<ID> \
  -H "Authorization: Bearer <TOKEN>"
```

## HTTP Status Codes

| Code | Meaning                                          |
|------|--------------------------------------------------|
| 200  | Success                                          |
| 201  | Resource created                                 |
| 204  | Deletion successful (no body)                    |
| 400  | Invalid JSON or validation error                 |
| 401  | Token missing, invalid, or expired               |
| 403  | Forbidden (not the owner)                        |
| 404  | Resource not found or soft-deleted               |
| 409  | Duplicate — X-Existing-Store-Id header provided  |
| 500  | Internal server error                            |

## Admin Seeding

The first admin user is created via an interactive CLI command — no manual SQL required.

```bash
make create-admin EMAIL=admin@example.com FIRST_NAME=Admin LAST_NAME=User
# Password: ········
# Confirm password: ········
# [OK]   Admin user ready — email: admin@example.com | id: <uuid>
```

The password is never visible — `stty -echo` hides input, and a confirmation prompt catches typos.  
The command is idempotent: running it again with the same email and an existing admin exits `0` (no-op).  
If the email belongs to a regular user, it exits `1` with an explicit conflict error.

**Why not pass the password as an argument?** Arguments appear in plaintext in `ps aux` and shell history. Interactive hidden input (`stty -echo`) is the standard safe approach for sensitive values in CLI tools.

## Best Practices

### Git Hooks (pre-push)
The `pre-push` hook is installed automatically via `make setup` (or `make hooks`).  
It runs **PHP-CS-Fixer** and **PHPStan** before every push and blocks if either fails.

```bash
# Install manually if needed
make hooks
```

### Continuous Quality
- Run `make ci` before any push to run the full pipeline locally.
- PHPStan level 8: zero errors tolerated.
- PHP-CS-Fixer PSR-12: zero diff tolerated.
- Any regression must be fixed before merging.

### PHPStan (level 8)

Configuration: `phpstan.neon` — analyses `src/` only.

| Parameter | Value | Effect |
|---|---|---|
| `level` | `8` | Strictest standard level — covers dead code, missing types, impossible conditions |
| `checkMissingIterableValueType` | `true` | `array` without a value type (e.g. `array<string, mixed>`) is an error — full generics required |
| `treatPhpDocTypesAsCertain` | `true` | `@var`, `@param`, `@return` PHPDoc types are trusted as certain by flow analysis |
| `checkGenericClassInNonGenericObjectType` | `true` | Generic classes (e.g. `Collection<T>`) must always be used with their type parameter |

### PHP-CS-Fixer

Configuration: `.php-cs-fixer.php` — applies to `src/` and `tests/`.

| Rule | Value | Effect |
|---|---|---|
| `@PSR12` | `true` | Full PSR-12 ruleset (indentation, braces, blank lines, etc.) |
| `declare_strict_types` | `true` | Enforces `declare(strict_types=1)` at the top of every file |
| `strict_param` | `true` | Native functions are called with strict type parameters |
| `array_syntax` | `short` | Short array syntax `[]` instead of `array()` |
| `no_unused_imports` | `true` | Removes unused `use` statements |
| `ordered_imports` | `alpha`, `class › function › const` | Imports sorted alphabetically, grouped by kind |
| `visibility_required` | `true` | Class members must declare explicit visibility |
| `no_useless_else` | `true` | Removes `else` branches after a `return`/`throw` |
| `binary_operator_spaces` | `single_space` | One space on each side of binary operators |
| `return_type_declaration` | `space_before: none` | No space before `:` in return type — `): void` not `) : void` |

### Architecture
- **Domain**: zero external dependencies — entities, value objects, criteria, domain exceptions.
- **Application**: CQRS use cases — Commands (`validateAndCreate` + `private` constructor), Queries, DTOs, repository interfaces (output ports).
- **Infrastructure**: adapters — PDO, JWT, Logging, Controllers, Middleware.
- Every repository method is wrapped in `try/catch (\Throwable)` throwing a typed domain exception.

```
┌─────────────────────────────────────────────────────────────────────────┐
│  INFRASTRUCTURE                                                         │
│                                                                         │
│  HTTP      index.php · AuthController · StoreController                 │
│            AuthMiddleware (JWT Bearer) · ApiResponse                    │
│                                                                         │
│  Adapters  PdoStoreRepository · PdoUserRepository                       │
│            JwtService · AuthContext · AppLogger (Monolog JSON)          │
│                                                                         │
└──────────────────────┬──────────────────────────────┬───────────────────┘
                       │ calls                        │ implements
┌──────────────────────▼──────────────────────────────▼────────────────────┐
│  APPLICATION                                                             │
│                                                                          │
│  Commands (write)                    Queries (read)                      │
│  ──────────────────────────────────  ────────────────────────────────────│
│  RegisterUserCommand                 GetStoreQuery                       │
│  LoginUserCommand                    GetStoreQueryHandler                │
│  CreateStoreCommand                  ListStoresQuery                     │
│  UpdateStoreCommand                  ListStoresQueryHandler              │
│  DeleteStoreCommand + Handlers       StoreDTO                            │
│                                                                          │
│  ┌───────────────────────────────────────────────────────────────────┐   │
│  │  Repository Interfaces (output ports)                             │   │
│  │  StoreRepositoryInterface  ·  UserRepositoryInterface             │   │
│  └───────────────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────┬───────────────────────────────────┘
                                       │ uses
┌──────────────────────────────────────▼────────────────────────────────────┐
│  DOMAIN                                                                   │
│                                                                           │
│  Entities      Value Objects                      Criteria · Exceptions   │
│  ──────────    ───────────────────────────────    ─────────────────────── │
│  User          UserId · UserEmail                 StoreCriteria           │
│  Store         HashedPassword · UserRole          StoreNotFoundException  │
│                StoreId · StoreName                StoreDuplicateException │
│                StoreAddress · NaturalKey          UserAlreadyExistsEx.    │
│                                                                           │
└──────────────────────────────────────┬────────────────────────────────────┘
                                       │
                             ┌─────────▼──────────┐
                             │     MySQL 8.0      │
                             │   users · stores   │
                             └────────────────────┘

Dependency rule: Infrastructure → Application → Domain. Domain knows nothing of Application or Infrastructure.
```

#### Request flow — example `POST /stores`

```
HTTP Request
  └─► index.php (match router)
        └─► StoreController::create()
              ├─► AuthMiddleware::requireAuth()  →  decode JWT  →  AuthContext{userId, role}
              ├─► CreateStoreCommand::validateAndCreate()  →  collect all field errors or return Command
              └─► CreateStoreCommandHandler::handle()
                    ├─► StoreRepository::findByNaturalKey()  →  409 if duplicate
                    ├─► Store::create()  →  new aggregate with NaturalKey computed
                    ├─► StoreRepository::save()  →  PDO INSERT … ON DUPLICATE KEY UPDATE
                    └─► ApiResponse::created(['id' => $uuid])
```

### Tests
- One test class per Command (tests `validateAndCreate`).
- One test class per CommandHandler.
- HTTP integration tests with Guzzle covering every endpoint and every error case.

See `docs/business-rules.md` for the full set of business rules.
