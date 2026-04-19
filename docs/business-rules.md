# Business Rules — Store API

## Users

- Available roles: `user` (default) and `admin`.
- The `user` role is assigned automatically at registration. The `admin` role is assigned manually in the database only.
- Email is normalised to lowercase and must be unique → HTTP 409 on duplicate.
- Password: minimum 8 characters, hashed with Argon2ID.

## Authentication

- Write endpoints (`POST/PUT/DELETE /stores`) require a valid JWT via `Authorization: Bearer <token>`.
- Expired or invalid token → HTTP 401.
- TTL configurable via `JWT_TTL` (default: 3600 seconds).

## Store Authorisation

| Action    | Unauthenticated | Authenticated user          | Admin  |
|-----------|-----------------|-----------------------------|--------|
| List      | ✅ 200          | ✅ 200                      | ✅ 200 |
| Read      | ✅ 200          | ✅ 200                      | ✅ 200 |
| Create    | ❌ 401          | ✅ 201                      | ✅ 201 |
| Update    | ❌ 401          | ✅ if owner / ❌ 403        | ✅ 200 |
| Delete    | ❌ 401          | ✅ if owner / ❌ 403        | ✅ 204 |

## Natural Key & Deduplication

- Natural key: `LOWER(TRIM(name)) | LOWER(TRIM(address)) | LOWER(TRIM(city)) | LOWER(TRIM(zip_code)) | LOWER(TRIM(country_iso))`
- Computed in PHP and stored in the database with a UNIQUE index.
- Checked on **creation** and on **update**.
- On duplicate → HTTP 409 + header `X-Existing-Store-Id: <uuid>`.
- Soft-deleted stores do not block the reuse of a natural key.

## Soft Delete

- `DELETE /stores/{id}` sets `deleted_at` (soft delete).
- Deleted stores are invisible in `GET /stores` and `GET /stores/{id}` (→ 404).
- Data is retained for audit purposes.

## Validation

- Any invalid command → HTTP 400:
  ```json
  { "success": false, "message": { "field": "error" }, "data": null }
  ```
- All errors are collected before throwing — the response contains **all** invalid fields at once, not just the first one.
- All string fields are **trimmed** before use (leading/trailing whitespace removed).

### User (register)

| Field        | Rules                                              |
|--------------|----------------------------------------------------|
| `first_name` | Required, non-empty string                         |
| `last_name`  | Required, non-empty string                         |
| `email`      | Required, valid email format, normalised lowercase |
| `password`   | Required, minimum 8 characters                     |

### Store (create / update)

| Field        | Rules                                                        |
|--------------|--------------------------------------------------------------|
| `name`       | Required, non-empty, 100 chars max                           |
| `address`    | Required, non-empty string                                   |
| `city`       | Required, non-empty string                                   |
| `zip_code`   | Required, exactly 5 digits (`/^\d{5}$/`)                    |
| `country_iso`| Required, exactly 2 letters ISO 3166-1 alpha-2, stored uppercase |
| `phone`      | Required, 7–15 chars, digits/spaces/dashes/+ allowed        |
| `id`         | Required for update, non-empty string (UUID)                 |

## Repository Error Handling

- Every PDO method is wrapped in `try/catch (\Throwable)`.
- On database communication error, a typed domain exception is thrown:
  - `CouldNotSaveStoreException`, `CouldNotDeleteStoreException`, `CouldNotFetchStoreException`
  - `CouldNotSaveUserException`, `CouldNotFetchUserException`
- These exceptions encapsulate the original error (via `$previous`) without exposing the stack trace.
- They bubble up to the controller → `\Throwable` catch → ERROR log → HTTP 500.

## Uniform Response Format

```json
{ "success": true|false, "message": "string or object", "data": null|object|array }
```

## Logging (`logs/app.log` — JSON, Monolog)

| Level   | Examples                                                          |
|---------|-------------------------------------------------------------------|
| INFO    | User registered, User logged in, Store created/updated/deleted    |
| WARNING | Login failed, duplicate detected, unauthorised attempt            |
| ERROR   | Unexpected exception (Throwable caught in a controller)           |

Filtering:
```bash
grep '"context":"store"' logs/app.log
grep '"level_name":"WARNING"' logs/app.log | grep '"context":"auth"'
```
