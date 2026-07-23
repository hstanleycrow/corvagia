English | [Español](README.es.md)

# Corvagia

A small PHP MVC + REST skeleton. Use `composer create-project` to start a new
project with routing, views, an admin panel, a JSON API (JWT auth), and a thin
database layer already wired together.

## What's inside

- **Routing** on [AltoRouter](https://altorouter.com/) with a static `Route` facade, named routes, middleware, and canonical trailing slashes.
- **Views** via [League Plates](https://platesphp.com/) (`resources/views`).
- **Database** through [EasyPHPDBCore](https://packagist.org/packages/hstanleycrow/easyphpdbcore) — a PDO layer with a simple `Model` and prepared statements everywhere.
- **REST API** with a consistent JSON envelope, typed exception mapping, JWT access tokens, and rotating refresh tokens.
- **Admin panel** with a functional login (CSRF), a datatable-driven **Users CRUD** as a reference molde, and reusable button/dropdown components.
- **HTTP request/response** from [Symfony HttpFoundation](https://symfony.com/doc/current/components/http_foundation.html).
- **Basic migrations**, a data factory, and an admin seeder.
- **PHPUnit** suite (routing, middleware, bootstrap, DB integration, API).
- **Docker (optional)** — a `docker compose` dev stack (PHP 8.3 + Apache + MariaDB), no local PHP needed.

## Requirements

- PHP 8.2 or higher
- Composer
- PDO with `pdo_mysql` (MySQL/MariaDB) — SQLite is only used by the test suite
- *Or*, instead of the above: Docker + Docker Compose (see [*Running with Docker*](#running-with-docker-optional))

## Installation

```bash
composer create-project hstanleycrow/corvagia my-project
cd my-project
cp .env.example .env
```

> Contributing to the skeleton itself? Clone the repo and run `composer install` instead.

Edit `.env` (database credentials, and a `JWT_SECRET` of **at least 32 bytes**),
create the schema, and serve the `public/` directory:

```bash
php app/Database/migrate.php          # creates the users + refresh_tokens tables
php app/Database/seed.php             # creates a default admin (admin / admin1234)
php -S localhost:8000 -t public       # or point your Apache/Nginx docroot at public/
```

Open `http://localhost:8000/` — the home page reports whether the database
connection works. Log in at `http://localhost:8000/login/` with `admin` /
`admin1234` (change it after the first login) to reach the admin panel and the
Users CRUD.

## Local use without git

You don't need git to start a project from the skeleton — just copy the folder:

- Copy everything **except** `vendor/` and `.env`, then run `composer install` in the copy — or copy `vendor/` too and skip that step.
- Copy `.env.example` to `.env` (or reuse an existing `.env`) and edit it: it is the **only** per-project file — database credentials, a 32-byte `JWT_SECRET`, `BUSINESS_NAME`, and the other keys shown in *Installation* above.
- Run `php app/Database/migrate.php` against the new project's database.

Everything else (code, routes, views, config) is shared skeleton and needs no
per-project edits.

## Running with Docker (optional)

Prefer containers? With Docker and Docker Compose installed, first point `.env` at
the `db` service:

```bash
cp .env.example .env
```

In `.env` set `DATABASE_HOST=db`, `DATABASE_PORT=3306`, `DATABASE_USERNAME=root`, a
`DATABASE_NAME`, and a **non-empty** `DATABASE_PASSWORD` (MariaDB needs a root
password, and the container provisions only the `root` user). Then, from the
project root:

```bash
docker compose up -d --build                          # PHP 8.3 + Apache + MariaDB
docker compose exec web php app/Database/migrate.php  # create the tables
docker compose exec web php app/Database/seed.php     # default admin (admin / admin1234)
```

Open `http://localhost:8000/` and log in with `admin` / `admin1234`.

- **`.env` is the single source of config.** Compose creates the container's database from `DATABASE_NAME`/`DATABASE_PASSWORD`, and the app connects to those same values — nothing to keep in sync.
- MariaDB is published on host port **3307** (container `3306`) so it doesn't clash with a local MySQL/XAMPP on 3306.
- Stop with `docker compose down` (add `-v` to also drop the database volume).

### Running multiple Corvagia projects at once

Each project is its own copy of the skeleton (see [*Local use without git*](#local-use-without-git)),
so Compose already isolates networks and volumes per project folder. The only
thing that collides across projects running at the same time is **host ports**.
Set `APP_PORT`/`DB_PORT` in each project's `.env` to unique values (e.g.
`8001`/`3308` for a second project) before running `docker compose up -d --build`.

## Further docs

- [`AI_USAGE.md`](AI_USAGE.md) — technical spec for an AI assistant, including the admin datatable CRUD (definitions, buttons, AJAX handler), flash messages, and the full exception mapping.
- [`API.md`](API.md) — REST API contract for **consumers** (auth flow, envelope, endpoints, errors, pagination, CORS). Hand this to a frontend/mobile client.

## Folder structure

```
public/
  index.php              # front controller (entry point)
  datatable_handler.php  # standalone datatable AJAX endpoint
  .htaccess              # rewrites everything to index.php; passes Authorization header
app/
  Core/
    Initialize.php       # bootstrap (session, env, config, CORS, routes)
    Route.php            # routing facade over AltoRouter
    Database.php         # lazy, shared EasyPHPDBCore connection
    Template.php         # Plates renderer
    Csrf.php             # CSRF token helper (login form)
    MiddlewareRunner.php
    Auth/                # JwtService, RefreshTokenService
    Http/                # ApiResponse, ExceptionHandler, Cors
    Config/EnvValidator.php
    Exceptions/          # typed router/config/resource exceptions
    Logger/LoggerFactory.php
  Controllers/
    Controller.php       # base for view controllers
    CrudController.php    # builds the admin datatable
    HomeController.php
    Auth/LoginController.php
    Admin/               # DashboardController, Users/ (reference CRUD)
    Api/                 # ApiController, UsersController, AuthController
  Components/            # Buttons/, Dropdowns/ (reusable UI components)
  DatatablesDefinitions/ # User (datatable column + button definitions)
  Middlewares/           # Auth, Admin, ApiAuth
  Models/                # User, RefreshToken (extend EasyPHPDBCore\Model)
  Resources/             # UserResource (typed output DTO)
  Database/
    migrate.php          # migration runner CLI
    seed.php             # default admin seeder
    Migrator.php
    Migrations/schema.php
    Factories/UserFactory.php
config/                  # App.php, Debug.php
docker/                  # Dockerfile, entrypoint, .env.docker (optional Docker stack)
helpers/                 # helpers.php, debug.php (auto-loaded functions)
resources/views/         # Plates templates (admin/, Layouts/, auth/)
routes/                  # web.php, admin.php, api.php
tests/                   # PHPUnit suite
```

## Defining routes

Routes live in `routes/`. `web.php` holds public pages, `admin.php` the admin
scaffold, `api.php` the JSON API. The controller target is
`Folder/ControllerName#method`, resolved under `App\Controllers\`.

Every URL is canonicalized to end with a slash (except file-like paths such as
`.css`/`.pdf`): a `GET` without the slash is 301-redirected, other verbs are
rewritten internally so the request body is preserved.

```php
use App\Core\Route;

// View route
Route::get('/', 'HomeController#index', 'home');

// Route parameter ([i:id] = integer, [*:slug] = string). Params arrive as strings.
Route::get('/users/[i:id]/', 'UsersController#show', 'users.show');

// Protected route (runs the App\Middlewares\AuthMiddleware first)
Route::get('/admin/', 'Admin/DashboardController#index', 'admin.dashboard')
    ->middleware('auth', 'admin');

// REST routes
Route::post('/api/users/', 'Api/UsersController#store', 'api.users.store')->middleware('apiAuth');
Route::delete('/api/users/[i:id]/', 'Api/UsersController#destroy', 'api.users.destroy')->middleware('apiAuth');
```

`->middleware('auth')` maps to `App\Middlewares\AuthMiddleware`. A middleware is
any class with `handle(): bool` (return `false` to stop the chain) and
`handleFailure(): void`.

## Using EasyPHPDBCore from a controller

A model is a subclass of `hstanleycrow\EasyPHPDBCore\Model` that only sets its
table. Inside a controller get the shared connection with `$this->db()`;
elsewhere use `App\Core\Database::connection()`. Route params are cast to the
declared type, so type `int $id` directly.

```php
namespace Models;

use hstanleycrow\EasyPHPDBCore\Model;

class Product extends Model
{
    protected ?string $table = 'products';
}
```

```php
namespace App\Controllers;

use Models\Product;
use App\Core\Template;

class ProductController extends Controller
{
    public function show(int $id): void
    {
        $product = (new Product($this->db()))->getById($id);
        Template::render('product', ['product' => $product]);
    }
}
```

Writes and `getById()` use bound parameters. For custom reads, pass user input
through the bindings, never by interpolating into the query string:

```php
$rows = (new Product(Database::connection()))
    ->query('SELECT * FROM products WHERE active = ? ORDER BY id')
    ->getRecords(['S']);
```

## A view: controller + template

```php
// app/Controllers/HomeController.php
namespace App\Controllers;

use App\Core\Template;

class HomeController extends Controller
{
    public function index(): void
    {
        Template::render('home', ['title' => 'Corvagia']);
    }
}
```

```php
<?php /* resources/views/home.php */ ?>
<?php $this->layout('Layouts/app', ['title' => $title]) ?>

<h1><?= $this->e($title) ?></h1>
<p>Welcome.</p>
```

View controllers extend `App\Controllers\Controller`, return `void`, and echo
through `Template::render()`.

## A REST endpoint

API controllers extend `App\Controllers\Api\ApiController` and **return** a
`JsonResponse` (the router sends it). Wrap the body in `handle()` so thrown
exceptions become a consistent JSON error.

```php
namespace App\Controllers\Api;

use Models\Product;
use App\Core\Http\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Core\Exceptions\ResourceNotFoundException;

final class ProductsController extends ApiController
{
    public function show(int $id): JsonResponse
    {
        return $this->handle(function () use ($id): JsonResponse {
            $product = (new Product($this->db()))->getById($id);
            if ($product === null) {
                throw new ResourceNotFoundException("Product {$id} not found.");
            }
            return ApiResponse::success($product);
        });
    }

    public function store(): JsonResponse
    {
        return $this->handle(function (): JsonResponse {
            $data = $this->input();                       // JSON body as an array
            $this->validate($data, ['name' => 'required']); // 422 on failure
            $id = (new Product($this->db()))->create([
                'name' => (string) $data['name'],
            ])->lastInsertId();
            return ApiResponse::success(['id' => $id], 201);
        });
    }
}
```

### Response envelope

```json
{ "success": true,  "data": { "...": "..." }, "meta": { } }
{ "success": false, "error": { "code": "not_found", "message": "...", "details": { } } }
```

### Exception mapping

| Thrown | HTTP | `code` |
| --- | --- | --- |
| `ResourceNotFoundException`, `RouteNotFoundException` | 404 | `not_found` |
| `EasyPHPFormValidator\ValidationException` | 422 | `validation_failed` |
| `EasyPHPDBCore\QueryException` (integrity, SQLSTATE 23xxx) | 409 | `conflict` |
| `EasyPHPDBCore\QueryException` (other) / any `Throwable` | 500 | `server_error` |
| `EasyPHPDBCore\ConnectionException` | 503 | `service_unavailable` |

Outside a `testing` environment, 500 messages are masked.

### Authentication (JWT + refresh tokens)

`POST /api/auth/login/` returns a short-lived access token and a rotating
refresh token; protected routes use the `apiAuth` middleware with an
`Authorization: Bearer <access_token>` header.

```
POST /api/auth/login/     { "username": "...", "password": "..." }
  -> { "access_token", "refresh_token", "token_type": "Bearer", "expires_in" }
POST /api/auth/refresh/   { "refresh_token": "..." }   # rotates the pair
POST /api/auth/logout/    { "refresh_token": "..." }   # revokes it
GET  /api/users/?page=1&per_page=25   (Authorization: Bearer <token>)
```

### CORS

Browser clients on another origin are blocked unless you allow the origin in `.env`:

```
CORS_ALLOWED_ORIGINS = "http://localhost:3000"   # comma separated, or * for any
```

Empty (the default) means no cross-origin access. The API answers the `OPTIONS`
preflight and echoes the headers on `/api/` responses. Full contract in
[`API.md`](API.md).

## Migrations

Add a named DDL statement to `app/Database/Migrations/schema.php` and run:

```bash
php app/Database/migrate.php
```

Applied migrations are tracked in a `migrations` table, so re-running is safe.

## Tests

```bash
vendor/bin/phpunit
```

Most tests run against an in-memory SQLite database. A few integration tests
target a MySQL database named `db_corvagia_test` and are skipped when it is
unavailable.

## License

MIT.
