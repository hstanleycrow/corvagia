English | [Español](README.es.md)

# Corvagia

A small PHP MVC + REST skeleton. Clone it to start a new project with routing,
views, a JSON API (JWT auth), and a thin database layer already wired together.

> Status: work in progress. The docs are partial — enough to spin up a test
> project. Expect changes before the first tagged release.

## What's inside

- **Routing** on [AltoRouter](https://altorouter.com/) with a static `Route` facade, named routes, middleware, and canonical trailing slashes.
- **Views** via [League Plates](https://platesphp.com/) (`resources/views`).
- **Database** through [EasyPHPDBCore](https://packagist.org/packages/hstanleycrow/easyphpdbcore) — a PDO layer with a simple `Model` and prepared statements everywhere.
- **REST API** with a consistent JSON envelope, typed exception mapping, JWT access tokens, and rotating refresh tokens.
- **Admin panel** with a functional login (CSRF), a datatable-driven **Users CRUD** as a reference molde, and reusable button/dropdown components.
- **HTTP request/response** from [Symfony HttpFoundation](https://symfony.com/doc/current/components/http_foundation.html).
- **Basic migrations**, a data factory, and an admin seeder.
- **PHPUnit** suite (routing, middleware, bootstrap, DB integration, API).

## Requirements

- PHP 8.2 or higher
- Composer
- PDO with `pdo_mysql` (MySQL/MariaDB) — SQLite is only used by the test suite

## Installation

```bash
git clone <your-fork-or-this-repo> my-project
cd my-project
composer install
cp .env.example .env
```

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

## Further docs

- [`AI_USAGE.md`](AI_USAGE.md) — technical spec for an AI assistant, including the admin datatable CRUD (definitions, buttons, AJAX handler), flash messages, and the full exception mapping.
- [`API.md`](API.md) — REST API contract for **consumers** (auth flow, envelope, endpoints, errors, pagination, CORS). Hand this to a frontend/mobile client.

## Folder structure

```
public/
  index.php              # front controller (entry point)
  .htaccess              # rewrites everything to index.php; passes Authorization header
app/
  Core/
    Initialize.php       # bootstrap (session, env, config, routes)
    Route.php            # routing facade over AltoRouter
    Database.php         # lazy, shared EasyPHPDBCore connection
    Template.php         # Plates renderer
    MiddlewareRunner.php
    Auth/                # JwtService, RefreshTokenService
    Http/                # ApiResponse, ExceptionHandler
    Config/EnvValidator.php
    Exceptions/          # typed router/config/resource exceptions
    Logger/LoggerFactory.php
  Controllers/
    Controller.php       # base for view controllers
    HomeController.php
    Auth/LoginController.php
    Admin/DashboardController.php
    Api/                 # ApiController, UsersController, AuthController
  Middlewares/           # Auth, Admin, ApiAuth
  Models/                # User, RefreshToken (extend EasyPHPDBCore\Model)
  Resources/             # UserResource (typed output DTO)
  Database/
    migrate.php          # migration runner CLI
    Migrator.php
    Migrations/schema.php
    Factories/UserFactory.php
config/                  # App.php, Debug.php
helpers/                 # helpers.php, debug.php (auto-loaded functions)
resources/views/         # Plates templates
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
table. Get the shared connection from `App\Core\Database::connection()`.

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
use App\Core\Database;
use App\Core\Template;

class ProductController extends Controller
{
    public function show(string $id): void
    {
        $product = (new Product(Database::connection()))->getById((int) $id);
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
use App\Core\Database;
use App\Core\Http\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Core\Exceptions\ResourceNotFoundException;

final class ProductsController extends ApiController
{
    public function show(string $id): JsonResponse
    {
        return $this->handle(function () use ($id): JsonResponse {
            $product = (new Product(Database::connection()))->getById((int) $id);
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
            $id = (new Product(Database::connection()))->create([
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
