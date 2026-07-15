[English](API.md) | Español

# API.es.md — Contrato de la API REST de Corvagia

Lo que un cliente necesita para consumir la API JSON: flujo de auth, envelope,
endpoints, errores y paginación. Este es el contrato para **consumir** la API —
para construirla o extenderla, ver [`AI_USAGE.es.md`](AI_USAGE.es.md).

## Reglas base

- **Base URL:** donde sirvas `public/`, p. ej. `http://localhost:8000`.
- **Toda URL termina con barra final.** Usa `/api/users/`, no `/api/users`. Un
  `GET` sin la barra recibe un 301; los demás verbos se reescriben internamente.
  Manda siempre la barra para que ningún cliente pierda el cuerpo en un redirect.
- **Los cuerpos van en JSON** con `Content-Type: application/json`.
- **Auth:** manda `Authorization: Bearer <access_token>` en cada ruta protegida.

## CORS

Los clientes de navegador en otro origen quedan bloqueados salvo que permitas el
origen en el `.env` de la API:

```
CORS_ALLOWED_ORIGINS = "http://localhost:3000"     # separados por coma, o * para cualquiera
```

Vacío (el default) significa sin acceso cross-origin. La API responde el preflight
`OPTIONS` con `204` y permite los headers `Authorization`, `Content-Type` y
`Accept`. Los clientes del mismo origen (servidos desde `public/`) no necesitan nada de esto.

## Envelope de respuesta

Toda respuesta usa una de dos formas.

**Éxito**
```json
{ "success": true, "data": { "...": "..." }, "meta": { "...": "..." } }
```
`data` es un objeto o un array. `meta` solo aparece en listados paginados.
Las respuestas `204 No Content` van con cuerpo vacío.

**Error**
```json
{ "success": false, "error": { "code": "not_found", "message": "...", "details": { } } }
```
`details` solo aparece en errores de validación (422), indexado por campo.

## Errores

| HTTP | `code` | Cuándo |
| --- | --- | --- |
| 401 | `unauthorized` | Credenciales malas, o token ausente/inválido/expirado |
| 404 | `not_found` | Ruta desconocida, o el registro no existe |
| 409 | `conflict` | Violación de integridad (p. ej. username duplicado) |
| 422 | `validation_failed` | Entrada inválida — ver `error.details` |
| 500 | `server_error` | Fallo inesperado (mensaje enmascarado fuera de `testing`) |
| 503 | `service_unavailable` | Base de datos inalcanzable |

## Flujo de auth

1. `POST /api/auth/login/` → un **access token** (vida corta) y un **refresh token**.
2. Manda el access token como `Authorization: Bearer <access_token>`.
3. Ante un `401`, llama a `POST /api/auth/refresh/` con el refresh token para obtener un **par nuevo**.
4. `POST /api/auth/logout/` revoca el refresh token.

> **Los refresh tokens rotan.** `refresh` revoca el token que mandaste y retorna uno
> nuevo. Debes guardar el `refresh_token` nuevo de la respuesta — reusar el viejo
> falla con `401`.

### POST /api/auth/login/ — abierto

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
Errores: `401 unauthorized` (credenciales malas), `422 validation_failed`.

### POST /api/auth/refresh/ — abierto

Request
```json
{ "refresh_token": "9f2b1c..." }
```
`200` — misma forma que login, con un access y refresh token **nuevos**.
Errores: `401 unauthorized` (inválido, revocado o expirado), `422 validation_failed`.

### POST /api/auth/logout/ — abierto

Request
```json
{ "refresh_token": "9f2b1c..." }
```
`204` con cuerpo vacío. Errores: `422 validation_failed`.

## Paginación

Los endpoints de listado aceptan `?page=1&per_page=25` (`page` default `1`,
`per_page` default `25`, topado en `100`) y retornan un bloque `meta`:

```json
{
  "success": true,
  "data": [ { "...": "..." } ],
  "meta": { "page": 1, "per_page": 25, "total": 42, "total_pages": 2 }
}
```

## Recurso Users — requiere `Bearer`

Un objeto `user`:
```json
{ "id": 1, "name": "Harold Crow", "username": "hstanleycrow", "active": "S", "isAdmin": "S", "created_at": "2026-07-14 10:00:00" }
```
`active` e `isAdmin` son `"S"` (sí) o `"N"` (no). Los hashes de contraseña nunca se retornan.

| Método | Ruta | Cuerpo | Éxito |
| --- | --- | --- | --- |
| `GET` | `/api/users/?page=1&per_page=25` | — | `200` listado + `meta` |
| `GET` | `/api/users/{id}/` | — | `200` user |
| `POST` | `/api/users/` | `{ "name", "username", "password", "active"? }` | `201` user |
| `PUT` | `/api/users/{id}/` | `{ "name" }` | `200` user |
| `DELETE` | `/api/users/{id}/` | — | `204` vacío |

Errores: `401` (sin token o inválido), `404` (id desconocido), `422` (cuerpo
inválido), `409` (username duplicado).

## Chequeo rápido

```bash
# 1. Login
curl -X POST http://localhost:8000/api/auth/login/ \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin1234"}'

# 2. Llama una ruta protegida con el access_token del paso 1
curl http://localhost:8000/api/users/?page=1 \
  -H "Authorization: Bearer <access_token>"
```
