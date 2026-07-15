English | [Español](API.es.md)

# API.md — Corvagia REST API contract

What a client needs to consume the JSON API: auth flow, envelope, endpoints,
errors and pagination. This is the **consumer** contract — to build or extend the
API itself, see [`AI_USAGE.md`](AI_USAGE.md).

## Base rules

- **Base URL:** wherever `public/` is served, e.g. `http://localhost:8000`.
- **Every URL ends with a trailing slash.** Use `/api/users/`, not `/api/users`. A
  `GET` without the slash is 301-redirected; other verbs are rewritten internally.
  Always send the slash so no client loses a request body on a redirect.
- **Request bodies are JSON** with `Content-Type: application/json`.
- **Auth:** send `Authorization: Bearer <access_token>` on every protected route.

## CORS

Browser clients on another origin are blocked unless you allow the origin in the
API's `.env`:

```
CORS_ALLOWED_ORIGINS = "http://localhost:3000"     # comma separated, or * for any
```

Empty (the default) means no cross-origin access. The API answers the preflight
`OPTIONS` with `204` and allows the `Authorization`, `Content-Type` and `Accept`
headers. Same-origin clients (served from `public/`) need none of this.

## Response envelope

Every response uses one of two shapes.

**Success**
```json
{ "success": true, "data": { "...": "..." }, "meta": { "...": "..." } }
```
`data` is an object or an array. `meta` only appears on paginated lists.
`204 No Content` responses have an empty body.

**Error**
```json
{ "success": false, "error": { "code": "not_found", "message": "...", "details": { } } }
```
`details` only appears on validation errors (422), keyed by field.

## Errors

| HTTP | `code` | When |
| --- | --- | --- |
| 401 | `unauthorized` | Bad credentials, or a missing/invalid/expired token |
| 404 | `not_found` | Unknown route, or the record does not exist |
| 409 | `conflict` | Integrity violation (e.g. duplicate username) |
| 422 | `validation_failed` | Invalid input — see `error.details` |
| 500 | `server_error` | Unexpected failure (message masked outside `testing`) |
| 503 | `service_unavailable` | Database unreachable |

## Auth flow

1. `POST /api/auth/login/` → an **access token** (short-lived) and a **refresh token**.
2. Send the access token as `Authorization: Bearer <access_token>`.
3. On a `401`, call `POST /api/auth/refresh/` with the refresh token to get a **new pair**.
4. `POST /api/auth/logout/` revokes the refresh token.

> **Refresh tokens rotate.** `refresh` revokes the token you sent and returns a new
> one. You must store the new `refresh_token` from the response — reusing the old
> one fails with `401`.

### POST /api/auth/login/ — open

Request
```json
{ "username": "admin", "password": "admin1234" }
```
`200`
```json
{
  "success": true,
  "data": {
    "access_token": "eyJhbGciOi...",
    "refresh_token": "9f2b1c...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```
Errors: `401 unauthorized` (bad credentials), `422 validation_failed`.

### POST /api/auth/refresh/ — open

Request
```json
{ "refresh_token": "9f2b1c..." }
```
`200` — same shape as login, with a **new** access and refresh token.
Errors: `401 unauthorized` (invalid, revoked or expired), `422 validation_failed`.

### POST /api/auth/logout/ — open

Request
```json
{ "refresh_token": "9f2b1c..." }
```
`204` with an empty body. Errors: `422 validation_failed`.

## Pagination

List endpoints accept `?page=1&per_page=25` (`page` defaults to `1`, `per_page` to
`25`, capped at `100`) and return a `meta` block:

```json
{
  "success": true,
  "data": [ { "...": "..." } ],
  "meta": { "page": 1, "per_page": 25, "total": 42, "total_pages": 2 }
}
```

## Users resource — requires `Bearer`

A `user` object:
```json
{ "id": 1, "name": "Harold Crow", "username": "hstanleycrow", "active": "S", "isAdmin": "S", "created_at": "2026-07-14 10:00:00" }
```
`active` and `isAdmin` are `"S"` (yes) or `"N"` (no). Password hashes are never returned.

| Method | Path | Body | Success |
| --- | --- | --- | --- |
| `GET` | `/api/users/?page=1&per_page=25` | — | `200` list + `meta` |
| `GET` | `/api/users/{id}/` | — | `200` user |
| `POST` | `/api/users/` | `{ "name", "username", "password", "active"? }` | `201` user |
| `PUT` | `/api/users/{id}/` | `{ "name" }` | `200` user |
| `DELETE` | `/api/users/{id}/` | — | `204` empty |

Errors: `401` (no/invalid token), `404` (unknown id), `422` (invalid body),
`409` (duplicate username).

## Quick check

```bash
# 1. Log in
curl -X POST http://localhost:8000/api/auth/login/ \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin1234"}'

# 2. Call a protected route with the access_token from step 1
curl http://localhost:8000/api/users/?page=1 \
  -H "Authorization: Bearer <access_token>"
```
