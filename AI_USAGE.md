English | [Español](AI_USAGE.es.md)

# AI_USAGE.md — Corvagia

Technical spec for an AI assistant to generate correct code inside a Corvagia
skeleton project. Corvagia is an application template (like `laravel/laravel`),
not a Composer library — you edit its files, you do not `require` it.

## What it is

A small MVC + REST skeleton: a static `Route` facade over AltoRouter, view
controllers that render Plates templates, API controllers that return Symfony
`JsonResponse` objects, and models built on EasyPHPDBCore. Bootstrap validates
required env vars, opens the DB lazily, and dispatches the matched route. It ships
two front ends off the same router: a public web side and an `/admin/` panel with
a datatable-driven CRUD.

## Bootstrap and request lifecycle

1. `public/index.php` requires the autoloader and calls `App\Core\Initialize::start()`. Routing-level exceptions on a `/api/...` path are converted to JSON there; other paths rethrow.
2. `Initialize` starts the session, loads `.env` (vlucas/phpdotenv), validates `REQUIRED_ENV`, requires `config/App.php` (defines constants), then requires `routes/admin.php`, `routes/api.php`, `routes/web.php` and calls `Route::dispatch()`.
3. `Route::dispatch()` canonicalizes the trailing slash, matches the request, runs the route's middleware, instantiates the controller, coerces the route params to the action's declared types, and calls the action. If the action returns a `Symfony\Component\HttpFoundation\Response`, the router sends it.

## Usage rules

1. **Routes go in `routes/{web,admin,api}.php`.** Register with `Route::get|post|put|delete|patch($uri, $target, $name)`. `$target` is `'Folder/Controller#method'` and resolves to `App\Controllers\Folder\Controller::method`. Chain `->middleware('name', ...)` to guard a route.
2. **Every URL is canonicalized to a trailing slash** except file-like paths (a final `.ext`). Define route URIs *with* the trailing slash (`/api/users/[i:id]/`). Root `/` is exempt.
3. **Route parameters are cast to the action's declared scalar type.** AltoRouter always matches params as `string` (`[i:id]` = digits, `[*:slug]` = any string), but `Route::dispatch()` reflects the target method's signature and casts each argument to the declared `int`/`float`/`bool` (via `coerceArguments()`). Under `declare(strict_types=1)` you therefore type the action parameter with the real type — `public function show(int $id)` receives `42`, not `"42"`. Extra route params beyond what the method declares are ignored (an update action can take the id from the URL pattern without declaring it, e.g. it reads the id from the request instead).
4. **Middleware is a class `App\Middlewares\{Ucfirst(name)}Middleware`** with `handle(): bool` and `handleFailure(): void`. Returning `false` from `handle()` stops the chain and calls `handleFailure()`. Web guards redirect; `ApiAuthMiddleware::handleFailure()` sends a JSON 401.
5. **View controllers extend `App\Controllers\Controller`**, take `(Request $request, string $currentRoute)`, return `void`, and echo through `App\Core\Template::render('view', $data)` (Plates, views in `resources/views` as `.php`/`.tpl`). Inside a template use `$this->layout('Layouts/app', [...])` and `$this->e()` for escaping.
6. **API controllers extend `App\Controllers\Api\ApiController` and RETURN a `JsonResponse`.** Never `echo` from an API controller. The router sends the returned response.
7. **Wrap every API action body in `$this->handle(fn (): JsonResponse => ...)`.** Any exception thrown inside is mapped to a JSON error by `ExceptionHandler`. Read the JSON request body with `$this->input()` (returns `array`, `[]` if empty/invalid). Validate with `$this->validate($data, $rules)` — a failure throws and becomes a 422.
8. **Build responses with `App\Core\Http\ApiResponse`:** `success(mixed $data = null, int $status = 200, array $meta = [])`, `error(string $code, string $message, int $status, array $details = [])`, `noContent()` (204). The envelope is `{"success":true,"data":...,"meta":...?}` / `{"success":false,"error":{"code","message","details"?}}`.
9. **Models are subclasses of `hstanleycrow\EasyPHPDBCore\Model`** in `namespace Models;` that set `protected ?string $table`. Get the shared connection and pass it to the model. Inside a controller use `$this->db()` (base-controller helper, lazy shared connection); elsewhere use `App\Core\Database::connection()`. So: `new User($this->db())`. Do not open a connection per query; do not hardcode `new MySQLPDOConnection(...)` in app code.
10. **Never interpolate user input into SQL.** Use `create/update/delete` array values (bound) and `query('... WHERE x = ?')->getRecords([$x])`. `getById(int|string $id)` returns the row or `null`. Only inline trusted, code-defined integers (e.g. validated `LIMIT`/`OFFSET`).
11. **Throw `App\Core\Exceptions\ResourceNotFoundException`** from an API action when a record is missing (EasyPHPDBCore returns `null`, it does not throw). It maps to 404.
12. **Exception → HTTP mapping** (in `ExceptionHandler`): `ResourceNotFoundException`/`RouteNotFoundException`/`ControllerNotFoundException` → 404; `EasyPHPFormValidator\ValidationException` → 422 (`details`); `EasyPHPDBCore\QueryException` with SQLSTATE `23xxx` → 409, otherwise → 500; `EasyPHPDBCore\ConnectionException` → 503; anything else → 500. 500 messages are masked unless `ENVIRONMENT` contains `testing`.
13. **Auth is JWT + rotating refresh tokens.** `JwtService` (HS256, `JWT_SECRET` ≥ 32 bytes) issues/verifies access tokens; `RefreshTokenService` issues opaque refresh tokens stored hashed, and `rotate()` revokes the old and returns a new one. Protect routes with `->middleware('apiAuth')` and an `Authorization: Bearer <access_token>` header. Auth endpoints: `POST /api/auth/{login,refresh,logout}/`.
14. **Output DTOs are typed classes in `app/Resources/`** (e.g. `UserResource`) with `fromRow(array): self` and `toArray(): array`. Use them to shape API output and never leak secrets (e.g. password hashes).
15. **Migrations are named DDL strings in `app/Database/Migrations/schema.php`**, applied by `php app/Database/migrate.php` and tracked in a `migrations` table (idempotent). Add new tables at the bottom with a unique name.
16. **Config comes from `.env`** and is exposed as constants by `config/App.php`. Required keys: `ENVIRONMENT`, `AUTH_SALT`, `BUSINESS_NAME`, `REPORT_ERROR_EMAIL`, and `DATABASE_HOST/NAME/USERNAME/PORT/CHARSET`. Auth adds `JWT_SECRET`, `JWT_TTL`, `REFRESH_TTL`. The admin datatable CRUD adds the `DT_*` keys (see that section). Missing required keys throw `ConfigurationException` at boot.
17. **All new code uses `declare(strict_types=1)`**, typed parameters/returns/properties, no unnecessary `mixed`, and typed arrays or DTOs instead of shapeless arrays.

## User feedback (flash messages)

`App\Core\FlashMessages` is a session-backed one-shot message store:

- `FlashMessages::set(string $type, string $message)` stores a message; `$type` is rendered as a Bootstrap class `alert-{type}`.
- `FlashMessages::display()` echoes and clears all pending messages.

Rules:

- **`$type` must be a valid Bootstrap alert variant** — `success`, `danger`, `warning`, `info`. Do **not** use `'error'`: `alert-error` is not a Bootstrap class and renders invisibly (especially on a dark theme). Use `'danger'`.
- **Call `display()` exactly once, in the layout** (e.g. `resources/views/Layouts/app.php`), never in an individual content view. Plates renders the child view *before* the layout, so putting it in a view either renders in the wrong place or silently shows nothing when that view's layout differs.

```php
FlashMessages::set('success', 'User created.');
$this->route('admin.usersList'); // redirect; the layout shows it on the next page
```

## Admin view CRUD (datatables)

The admin panel lists records with a server-side datatable and edits them with
plain form controllers. Three pieces cooperate: a **definition** (columns + row
buttons + SQL source), an **AJAX handler** that streams rows, and **reusable
button components**.

### 1. Definition — `app/DatatablesDefinitions/{Name}.php`

A plain class resolved by name through `DT_DEFINITIONS_NAMESPACE`. Key properties:

- `public string $dbTable` — the **SQL** table (used for the query).
- `public string $primaryKey` — usually `'id'`.
- `public string $model` — the **absolute route base** used to build the row
  buttons' hrefs, e.g. `'/admin/user'`. This is *not* the SQL table. The package
  builds each row button href as `{model}/{path}/{id}/`; because that href is
  relative, `$model` **must** start with `/` and be the full route base, or the
  browser resolves it against the list URL and doubles the segment
  (`/admin/users/user/editar/1/` instead of `/admin/user/editar/1/`).

`getColumns()` returns column defs (`view_name`, `db_name`, `field`, `format`).
`getButtons()` returns per-row action buttons; each entry sets `view_name` (the
column header, required), `path` (the URL segment, e.g. `editar`/`borrar`) and
`buttonClass` (a reusable button component).
`getJoinQuery()` returns `FROM \`{$this->dbTable}\` AS \`a\``; `getExtraCondition()`
returns an optional `WHERE`.

```php
namespace App\DatatablesDefinitions;

class User
{
    public string $dbTable    = 'users';
    public string $model      = '/admin/user'; // absolute route base for row buttons
    public string $primaryKey = 'id';

    public function getColumns(): array
    {
        return [
            ['view_name' => 'Id',   'db_name' => '`a`.`id`',   'field' => 'id',   'format' => 'text'],
            ['view_name' => 'Name', 'db_name' => '`a`.`name`', 'field' => 'name', 'format' => 'text'],
        ];
    }

    public function getButtons(): array
    {
        return [
            ['button_id' => 'edit',   'view_name' => 'Editar', 'db_name' => '`a`.`id`', 'field' => 'id', 'path' => 'editar', 'buttonClass' => \App\Components\Buttons\EditButton::class],
            ['button_id' => 'delete', 'view_name' => 'Borrar', 'db_name' => '`a`.`id`', 'field' => 'id', 'path' => 'borrar', 'buttonClass' => \App\Components\Buttons\DeleteButton::class],
        ];
    }

    public function getJoinQuery(): string { return "FROM `{$this->dbTable}` AS `a`"; }
    public function getExtraCondition(): string { return ""; }
}
```

### 2. Building the table for the view

Use `hstanleycrow\EasyPHPDatatableCRUD\DatatableUIBuilder`. On PHP 8.2 you cannot
chain a method call directly on `new` — wrap it `(new X())->...` or use separate
statements:

```php
$datatable = new DatatableUIBuilder('user', []); // ('user' resolves DT_DEFINITIONS_NAMESPACE\User)
$datatable->setAddButtonClass(\App\Components\Buttons\AddButton::class); // table-level create button
$datatable->setAjaxUrl('/datatable_handler.php');                        // where the grid fetches rows
// pass $datatable to the view; call ->autoLoadDatatableJS() in the template to emit the JS
```

### 3. AJAX handler — `public/datatable_handler.php`

A standalone endpoint (not routed) that streams rows. **Inject EasyPHPDBCore's
PDO** into `SSP::handle()` so the datatable reuses the app connection and does not
build its own — EasyPHPDBCore treats `DATABASE_CHARSET` as a full `SET NAMES ...`
init command, which the datatable package would otherwise misread as a charset
name (error 2019 "Unknown character set"):

```php
<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->load();
require __DIR__ . '/../config/App.php';

hstanleycrow\EasyPHPDatatables\SSP::handle(App\Core\Database::connection()->getConnection());
```

### 4. Reusable buttons — `app/Components/Buttons/`

Buttons are **reusable UI components** (usable in datatables, forms, modals), so
they live under `app/Components/`, not a feature folder. Each extends the package
`BaseLink` + `LinkClient` and decorates with `EasyPHPWebComponents\Icon`, rendering
a Bootstrap `<a class="btn ...">`:

- **Per-row `EditButton`/`DeleteButton`** — wired via the definition's `buttonClass`; the builder passes the row **href** (`{model}/{path}/{id}/`) to the constructor.
- **Table-level `AddButton`** — wired via `setAddButtonClass()`; the builder passes the **definition name** to the constructor, which builds the create href (adjust its `/admin/{def}/add/` default to match your create route, e.g. `agregar`).

To restyle, edit the CSS class / icon / text in the component, or subclass it.
Font Awesome must be loaded in the layout for the icons to render.

### 5. Edit/create controllers

Ordinary view controllers. `showForm(int $id)` loads the record and renders the
form; the save action reads the request, validates, writes through a model, sets a
flash message, and redirects. Row-button routes look like
`/admin/user/editar/[i:id]/` and `/admin/user/borrar/[i:id]/`, matching the
definition's `$model` + `path`.

### 6. Form dropdowns — `app/Components/Dropdowns/`

Dropdowns are **built in the controller** and echoed by the view (`<?= $dropdown ?>`),
never assembled inline in the template. Two components:

- **ENUM / fixed options → `Dropdown` + `DropdownClient`.** The array **key** is the `<option value>`, the array value is the label, and `selected` is the current value:
  ```php
  $client = new DropdownClient(['S' => 'Activo', 'N' => 'Inactivo'], $selected);
  $html = (new Dropdown($client))->setName('active')->setId('active')->addClass('form-select')->render();
  ```
- **Foreign key (from a table) → `DBDropdown`.** Give it the connection, the related model, and a method that returns `[['id' => .., 'name' => ..], ...]` via a bound query:
  ```php
  $html = (new DBDropdown($this->db(), \Models\Category::class, 'getForDropdownOptions', $selected))
      ->setName('category_id')->addClass('form-select')->render();
  ```
  The model method:
  ```php
  public function getForDropdownOptions(): array
  {
      return $this->query('SELECT id, name FROM categories ORDER BY name ASC')->getRecords();
  }
  ```

The Users molde (`UsersCreateController`/`UsersUpdateController`) shows the ENUM
pattern via a `buildEnumDropdown()` helper.

## Admin CRUD recipe (step by step)

To add a new admin CRUD, **mirror the shipped Users molde** and adapt names.

**Users molde (read before generating):** `app/Models/User.php`,
`app/DatatablesDefinitions/User.php`,
`app/Controllers/Admin/Users/{UsersIndex,UsersCreate,UsersUpdate,UsersDelete}Controller.php` +
`UsersFormValidator.php`, `resources/views/admin/sections/Users/{UserForm,userList}.tpl.php`,
the `# Users CRUD` block in `routes/admin.php`, and the "Configuración" dropdown in
`resources/views/admin/sections/Nav/mainMenuNav.php`.

**Naming:** Model + Definition **singular** (`Product`); table **plural** (`products`);
controllers folder `app/Controllers/Admin/{Plural}/`; list route plural
(`/admin/products/`), action routes singular (`/admin/product/...`).

**Steps for `{Singular}` / table `{plural}`:**

1. **Migration** — add a `CREATE TABLE IF NOT EXISTS` entry to `app/Database/Migrations/schema.php`, run `php app/Database/migrate.php`.
2. **Model** — `Models\{Singular} extends Model` with `protected ?string $table = '{plural}';` (see rule 9-10 for custom bound queries).
3. **Definition** — `App\DatatablesDefinitions\{Singular}` with `$dbTable`, `$primaryKey`, `$model = '/admin/{singular}'` (absolute), `getColumns()`, `getButtons()`, `getJoinQuery()`, `getExtraCondition()` (see §1).
4. **Controllers** — `app/Controllers/Admin/{Plural}/` with Index/Create/Update/Delete + FormValidator. Index declares `protected string $DTDefinition = '{singular}';` and builds the grid with `(new CrudController())->generateDatatable($this->DTDefinition)`. Create/Update call `$this->validate($rules, $messages)` **first** (the base controller reads POST and handles errors/redirect — no manual collection), then read fields with `$this->request->get()`, write, flash, `$this->route('admin.{plural}List')`. Build any dropdowns here (§6).
5. **Views** — `resources/views/admin/sections/{Plural}/` with `{Singular}Form.tpl.php` + `{plural}List.tpl.php`, both `$this->layout('Layouts/admin', [...])`. The list renders `$datatable->setTableId('{plural}')->render()`. Never call `FlashMessages::display()` in a view.
6. **Routes** — six lines in `routes/admin.php`, all `->middleware('auth', 'admin')` (list, create form `agregar`, create, edit form `editar/[i:id]`, edit, delete `borrar/[i:id]`).
7. **Menu** — in `mainMenuNav.php` add `${plural}URL = Route::getUrlFromName('admin.{plural}List');` and a `<li><a class="dropdown-item" href="<?= ${plural}URL; ?>">{Label}</a></li>` inside the target dropdown.

**Validator gotchas:** `min`/`max` measure string **length** but numeric **value** for real int/float. The `in` rule is not pipe-separated: `required in:a,b`. **An unknown rule name is silently ignored** (the validator only applies rules present in its map, no error is raised) — so a made-up rule like `numeric` validates nothing and never warns. The valid rules are: `required`, `string`, `integer`, `min`, `max`, `in`, `email`, `url`, `phone`, `svphone`, `confirmed`, `decimal`, `decimalNumber`, `greaterThanZero`, `nullable`, `date`, `after`, `afterOrEqual`, `before`, `beforeOrEqual`, `fileExtension`. Use `integer` for whole numbers.

## Minimal end-to-end (a REST resource)

**For a full REST CRUD, mirror the shipped molde `App\Controllers\Api\UsersController`**
and its `# Users resource` block in `routes/api.php`: a paginated `index` (returns
`meta` with `page`/`per_page`/`total`/`total_pages`, capped by `MAX_PER_PAGE`),
plus `show`/`store`/`update`/`destroy`, with the output shaped by a DTO
(`App\Resources\UserResource`, rule 14). The paginated `index` needs `paginate()`
and `countAll()` on the model — copy them from `Models\User`. The example below is
just the minimal shape:

```php
// routes/api.php
Route::get('/api/products/[i:id]/', 'Api/ProductsController#show', 'api.products.show')->middleware('apiAuth');
Route::post('/api/products/',        'Api/ProductsController#store', 'api.products.store')->middleware('apiAuth');
```

```php
// app/Models/Product.php
namespace Models;

use hstanleycrow\EasyPHPDBCore\Model;

class Product extends Model
{
    protected ?string $table = 'products';
}
```

```php
// app/Controllers/Api/ProductsController.php
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
            $row = (new Product($this->db()))->getById($id);
            if ($row === null) {
                throw new ResourceNotFoundException("Product {$id} not found.");
            }
            return ApiResponse::success($row);
        });
    }

    public function store(): JsonResponse
    {
        return $this->handle(function (): JsonResponse {
            $data = $this->input();
            $this->validate($data, ['name' => 'required']);
            $id = (new Product($this->db()))->create(['name' => (string) $data['name']])->lastInsertId();
            return ApiResponse::success(['id' => $id], 201);
        });
    }
}
```

Add the `products` table to `app/Database/Migrations/schema.php` and run
`php app/Database/migrate.php`.

## Common mistakes to avoid

- Echoing from an API controller instead of returning a `JsonResponse`.
- Typing a route action parameter as `string` and casting by hand — the router already casts to the declared type; type `int $id` directly.
- Registering an API route without the trailing slash, or forgetting `->middleware('apiAuth')` on a protected route.
- Building a `new MySQLPDOConnection(...)` in app code instead of `$this->db()` / `Database::connection()`.
- Interpolating input into `query('... WHERE id = ' . $id)` instead of `->getRecords([$id])`.
- Returning a raw DB row that contains a password hash instead of a `Resource` DTO.
- Expecting EasyPHPDBCore to throw when a record is missing — `getById()` returns `null`; throw `ResourceNotFoundException` yourself for a 404.
- Forgetting `protected ?string $table` on a `Model` subclass.
- Setting a datatable definition's `$model` to the bare table name — it must be the absolute route base (`/admin/user`) or row-button URLs double the path segment.
- Chaining a method directly on `new DatatableUIBuilder(...)` on PHP 8.2 (parse error) — wrap `(new X())->...`.
- Using flash type `'error'` (invisible) instead of `'danger'`, or calling `FlashMessages::display()` in a view instead of once in the layout.

## Tests

`vendor/bin/phpunit`. Unit/integration tests use an in-memory SQLite connection
(`Tests\Support\SqliteConnection`) injected via `App\Core\Database::swap(...)`;
API controllers are tested by calling the action and asserting the returned
`JsonResponse`. Routing tests cover param type coercion. A few MySQL integration
tests target `db_corvagia_test` and skip when it is unavailable.
