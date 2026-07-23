[English](README.md) | Español

# Corvagia

Un pequeño skeleton PHP de MVC + REST. Usa `composer create-project` para arrancar
un proyecto nuevo con routing, vistas, un panel admin, una API JSON (auth JWT) y
una capa de base de datos ligera ya conectados entre sí.

## Qué incluye

- **Routing** sobre [AltoRouter](https://altorouter.com/) con una fachada estática `Route`, rutas con nombre, middleware y barra final canónica.
- **Vistas** con [League Plates](https://platesphp.com/) (`resources/views`).
- **Base de datos** vía [EasyPHPDBCore](https://packagist.org/packages/hstanleycrow/easyphpdbcore) — una capa PDO con un `Model` simple y prepared statements en todo.
- **API REST** con envelope JSON consistente, mapeo de excepciones tipado, access tokens JWT y refresh tokens rotativos.
- **Panel admin** con login funcional (CSRF), un **CRUD de Usuarios** basado en datatable como molde de referencia, y componentes de botón/dropdown reutilizables.
- **Petición/respuesta HTTP** con [Symfony HttpFoundation](https://symfony.com/doc/current/components/http_foundation.html).
- **Migraciones básicas**, un factory de datos y un seeder de admin.
- Suite de **PHPUnit** (routing, middleware, bootstrap, integración con BD, API).
- **Docker (opcional)** — un stack de desarrollo con `docker compose` (PHP 8.3 + Apache + MariaDB), sin PHP local.

## Requisitos

- PHP 8.2 o superior
- Composer
- PDO con `pdo_mysql` (MySQL/MariaDB) — SQLite solo lo usa la suite de tests
- *O*, en vez de lo anterior: Docker + Docker Compose (ver [*Levantar con Docker*](#levantar-con-docker-opcional))

## Instalación

```bash
composer create-project hstanleycrow/corvagia mi-proyecto
cd mi-proyecto
cp .env.example .env
```

> ¿Vas a contribuir al skeleton en sí? Clona el repo y corre `composer install` en su lugar.

Edita `.env` (credenciales de BD y un `JWT_SECRET` de **al menos 32 bytes**),
crea el esquema y sirve el directorio `public/`:

```bash
php app/Database/migrate.php          # crea las tablas users + refresh_tokens
php app/Database/seed.php             # crea un admin por defecto (admin / admin1234)
php -S localhost:8000 -t public       # o apunta el docroot de Apache/Nginx a public/
```

Abre `http://localhost:8000/` — la página de inicio indica si la conexión a la
base de datos funciona. Ingresa en `http://localhost:8000/login/` con `admin` /
`admin1234` (cámbiala tras el primer ingreso) para llegar al panel admin y al
CRUD de Usuarios.

## Uso local sin git

No necesitas git para arrancar un proyecto desde el skeleton — basta con copiar la carpeta:

- Copia todo **excepto** `vendor/` y `.env`, luego corre `composer install` en la copia — o copia también `vendor/` y sáltate ese paso.
- Copia `.env.example` a `.env` (o reutiliza un `.env` existente) y edítalo: es el **único** archivo por proyecto — credenciales de BD, un `JWT_SECRET` de 32 bytes, `BUSINESS_NAME`, y las demás claves mostradas en *Instalación* arriba.
- Corre `php app/Database/migrate.php` contra la base de datos del nuevo proyecto.

Todo lo demás (código, rutas, vistas, config) es skeleton compartido y no
necesita ediciones por proyecto.

## Levantar con Docker (opcional)

¿Prefieres contenedores? Con Docker y Docker Compose instalados, primero apunta el
`.env` al servicio `db`:

```bash
cp .env.example .env
```

En `.env` poné `DATABASE_HOST=db`, `DATABASE_PORT=3306`, `DATABASE_USERNAME=root`, un
`DATABASE_NAME`, y un `DATABASE_PASSWORD` **no vacío** (MariaDB necesita password de
root, y el contenedor solo provisiona el usuario `root`). Luego, desde la raíz del
proyecto:

```bash
docker compose up -d --build                          # PHP 8.3 + Apache + MariaDB
docker compose exec web php app/Database/migrate.php  # crea las tablas
docker compose exec web php app/Database/seed.php     # admin por defecto (admin / admin1234)
```

Abre `http://localhost:8000/` e ingresa con `admin` / `admin1234`.

- **El `.env` es la única fuente de config.** Compose crea la base del contenedor desde `DATABASE_NAME`/`DATABASE_PASSWORD`, y la app se conecta a esos mismos valores — nada que mantener sincronizado.
- MariaDB se publica en el puerto **3307** del host (contenedor `3306`) para no chocar con un MySQL/XAMPP local en 3306.
- Detén con `docker compose down` (agrega `-v` para borrar también el volumen de la base de datos).

### Levantar varios proyectos Corvagia a la vez

Cada proyecto es su propia copia del skeleton (ver [*Uso local sin git*](#uso-local-sin-git)),
así que Compose ya aísla redes y volúmenes por carpeta de proyecto. Lo único que
choca entre proyectos corriendo al mismo tiempo son los **puertos de host**.
Poné `APP_PORT`/`DB_PORT` en el `.env` de cada proyecto con valores únicos (por
ejemplo `8001`/`3308` para un segundo proyecto) antes de correr
`docker compose up -d --build`.

## Más documentación

- [`AI_USAGE.es.md`](AI_USAGE.es.md) — especificación técnica para una IA, incluyendo el CRUD admin con datatables (definiciones, botones, handler AJAX), flash messages y el mapeo completo de excepciones.
- [`API.es.md`](API.es.md) — contrato de la API REST para **consumidores** (flujo de auth, envelope, endpoints, errores, paginación, CORS). Esto es lo que le pasas a un cliente frontend/móvil.

## Estructura de carpetas

```
public/
  index.php              # front controller (punto de entrada)
  datatable_handler.php  # endpoint AJAX del datatable (standalone)
  .htaccess              # reescribe todo a index.php; pasa el header Authorization
app/
  Core/
    Initialize.php       # bootstrap (sesión, env, config, CORS, rutas)
    Route.php            # fachada de routing sobre AltoRouter
    Database.php         # conexión EasyPHPDBCore compartida y lazy
    Template.php         # renderizador Plates
    Csrf.php             # helper de token CSRF (formulario de login)
    MiddlewareRunner.php
    Auth/                # JwtService, RefreshTokenService
    Http/                # ApiResponse, ExceptionHandler, Cors
    Config/EnvValidator.php
    Exceptions/          # excepciones tipadas de router/config/recurso
    Logger/LoggerFactory.php
  Controllers/
    Controller.php       # base para controladores de vista
    CrudController.php    # arma el datatable del admin
    HomeController.php
    Auth/LoginController.php
    Admin/               # DashboardController, Users/ (CRUD de referencia)
    Api/                 # ApiController, UsersController, AuthController
  Components/            # Buttons/, Dropdowns/ (componentes UI reutilizables)
  DatatablesDefinitions/ # User (definición de columnas + botones del datatable)
  Middlewares/           # Auth, Admin, ApiAuth
  Models/                # User, RefreshToken (extienden EasyPHPDBCore\Model)
  Resources/             # UserResource (DTO de salida tipado)
  Database/
    migrate.php          # runner de migraciones (CLI)
    seed.php             # seeder del admin por defecto
    Migrator.php
    Migrations/schema.php
    Factories/UserFactory.php
config/                  # App.php, Debug.php
docker/                  # Dockerfile, entrypoint, .env.docker (stack Docker opcional)
helpers/                 # helpers.php, debug.php (funciones auto-cargadas)
resources/views/         # plantillas Plates (admin/, Layouts/, auth/)
routes/                  # web.php, admin.php, api.php
tests/                   # suite PHPUnit
```

## Definir rutas

Las rutas viven en `routes/`. `web.php` tiene las páginas públicas, `admin.php`
el scaffold de administración, `api.php` la API JSON. El target del controlador
es `Carpeta/NombreControlador#metodo`, resuelto bajo `App\Controllers\`.

Toda URL se canonicaliza para terminar con barra (salvo rutas tipo archivo como
`.css`/`.pdf`): un `GET` sin barra recibe un 301, los demás verbos se reescriben
internamente para no perder el cuerpo de la petición.

```php
use App\Core\Route;

// Ruta de vista
Route::get('/', 'HomeController#index', 'home');

// Parámetro de ruta ([i:id] = entero, [*:slug] = string). Llegan como string.
Route::get('/users/[i:id]/', 'UsersController#show', 'users.show');

// Ruta protegida (ejecuta App\Middlewares\AuthMiddleware primero)
Route::get('/admin/', 'Admin/DashboardController#index', 'admin.dashboard')
    ->middleware('auth', 'admin');

// Rutas REST
Route::post('/api/users/', 'Api/UsersController#store', 'api.users.store')->middleware('apiAuth');
Route::delete('/api/users/[i:id]/', 'Api/UsersController#destroy', 'api.users.destroy')->middleware('apiAuth');
```

`->middleware('auth')` mapea a `App\Middlewares\AuthMiddleware`. Un middleware es
cualquier clase con `handle(): bool` (devuelve `false` para cortar la cadena) y
`handleFailure(): void`.

## Usar EasyPHPDBCore desde un controlador

Un modelo es una subclase de `hstanleycrow\EasyPHPDBCore\Model` que solo define
su tabla. Dentro de un controlador la conexión compartida se obtiene con
`$this->db()`; en otro lugar usa `App\Core\Database::connection()`. Los parámetros
de ruta se castean al tipo declarado, así que tipa `int $id` directamente.

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

Las escrituras y `getById()` usan parámetros vinculados. Para lecturas
personalizadas, pasa la entrada del usuario por los bindings, nunca
interpolándola en el string del query:

```php
$rows = (new Product(Database::connection()))
    ->query('SELECT * FROM products WHERE active = ? ORDER BY id')
    ->getRecords(['S']);
```

## Una vista: controlador + plantilla

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
<p>Bienvenido.</p>
```

Los controladores de vista extienden `App\Controllers\Controller`, devuelven
`void` y hacen echo a través de `Template::render()`.

## Un endpoint REST

Los controladores de API extienden `App\Controllers\Api\ApiController` y
**retornan** un `JsonResponse` (el router lo envía). Envuelve el cuerpo en
`handle()` para que las excepciones lanzadas se conviertan en un error JSON
consistente.

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
            $data = $this->input();                       // cuerpo JSON como array
            $this->validate($data, ['name' => 'required']); // 422 si falla
            $id = (new Product($this->db()))->create([
                'name' => (string) $data['name'],
            ])->lastInsertId();
            return ApiResponse::success(['id' => $id], 201);
        });
    }
}
```

### Envelope de respuesta

```json
{ "success": true,  "data": { "...": "..." }, "meta": { } }
{ "success": false, "error": { "code": "not_found", "message": "...", "details": { } } }
```

### Mapeo de excepciones

| Lanzada | HTTP | `code` |
| --- | --- | --- |
| `ResourceNotFoundException`, `RouteNotFoundException` | 404 | `not_found` |
| `EasyPHPFormValidator\ValidationException` | 422 | `validation_failed` |
| `EasyPHPDBCore\QueryException` (integridad, SQLSTATE 23xxx) | 409 | `conflict` |
| `EasyPHPDBCore\QueryException` (otra) / cualquier `Throwable` | 500 | `server_error` |
| `EasyPHPDBCore\ConnectionException` | 503 | `service_unavailable` |

Fuera de un entorno `testing`, los mensajes 500 se enmascaran.

### Autenticación (JWT + refresh tokens)

`POST /api/auth/login/` devuelve un access token de vida corta y un refresh
token rotativo; las rutas protegidas usan el middleware `apiAuth` con un header
`Authorization: Bearer <access_token>`.

```
POST /api/auth/login/     { "username": "...", "password": "..." }
  -> { "access_token", "refresh_token", "token_type": "Bearer", "expires_in" }
POST /api/auth/refresh/   { "refresh_token": "..." }   # rota el par
POST /api/auth/logout/    { "refresh_token": "..." }   # lo revoca
GET  /api/users/?page=1&per_page=25   (Authorization: Bearer <token>)
```

### CORS

Los clientes de navegador en otro origen quedan bloqueados salvo que permitas el
origen en el `.env`:

```
CORS_ALLOWED_ORIGINS = "http://localhost:3000"   # separados por coma, o * para cualquiera
```

Vacío (el default) significa sin acceso cross-origin. La API responde el preflight
`OPTIONS` y echa los headers en las respuestas de `/api/`. Contrato completo en
[`API.es.md`](API.es.md).

## Migraciones

Agrega un DDL con nombre a `app/Database/Migrations/schema.php` y ejecuta:

```bash
php app/Database/migrate.php
```

Las migraciones aplicadas se registran en una tabla `migrations`, así que
re-ejecutar es seguro.

## Tests

```bash
vendor/bin/phpunit
```

La mayoría de los tests corren contra una base SQLite en memoria. Unos pocos
tests de integración apuntan a una base MySQL llamada `db_corvagia_test` y se
saltan cuando no está disponible.

## Licencia

MIT.
