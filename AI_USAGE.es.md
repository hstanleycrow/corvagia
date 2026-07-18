[English](AI_USAGE.md) | Español

# AI_USAGE.es.md — Corvagia

Especificación técnica para que una IA genere código correcto dentro de un
proyecto skeleton Corvagia. Corvagia es una plantilla de aplicación (como
`laravel/laravel`), no una librería de Composer: editas sus archivos, no la
haces `require`.

## Qué es

Un pequeño skeleton MVC + REST: una fachada estática `Route` sobre AltoRouter,
controladores de vista que renderizan plantillas Plates, controladores de API
que retornan objetos `JsonResponse` de Symfony, y modelos construidos sobre
EasyPHPDBCore. El bootstrap valida las variables de entorno requeridas, abre la
BD de forma lazy y despacha la ruta encontrada. Trae dos front ends sobre el
mismo router: una parte web pública y un panel `/admin/` con un CRUD basado en
datatables.

## Bootstrap y ciclo de la petición

1. `public/index.php` hace `require` del autoloader y llama a `App\Core\Initialize::start()`. Las excepciones de nivel routing en una ruta `/api/...` se convierten ahí en JSON; las demás rutas re-lanzan.
2. `Initialize` arranca la sesión, carga `.env` (vlucas/phpdotenv), valida `REQUIRED_ENV`, hace `require` de `config/App.php` (define constantes), luego de `routes/admin.php`, `routes/api.php`, `routes/web.php` y llama a `Route::dispatch()`.
3. `Route::dispatch()` canonicaliza la barra final, encuentra la petición, ejecuta el middleware de la ruta, instancia el controlador, castea los parámetros de ruta a los tipos declarados en la acción y llama a la acción. Si la acción retorna un `Symfony\Component\HttpFoundation\Response`, el router lo envía.

## Reglas de uso

1. **Las rutas van en `routes/{web,admin,api}.php`.** Se registran con `Route::get|post|put|delete|patch($uri, $target, $name)`. `$target` es `'Carpeta/Controlador#metodo'` y resuelve a `App\Controllers\Carpeta\Controlador::metodo`. Encadena `->middleware('nombre', ...)` para proteger una ruta.
2. **Toda URL se canonicaliza a barra final** salvo rutas tipo archivo (un `.ext` final). Define las URIs de ruta *con* la barra final (`/api/users/[i:id]/`). La raíz `/` está exenta.
3. **Los parámetros de ruta se castean al tipo escalar declarado en la acción.** AltoRouter siempre encuentra los parámetros como `string` (`[i:id]` = dígitos, `[*:slug]` = cualquier string), pero `Route::dispatch()` refleja la firma del método destino y castea cada argumento al `int`/`float`/`bool` declarado (vía `coerceArguments()`). Bajo `declare(strict_types=1)` tipas el parámetro de la acción con su tipo real — `public function show(int $id)` recibe `42`, no `"42"`. Los parámetros de ruta extra, más allá de los que declara el método, se ignoran (una acción de edición puede recibir el id por el patrón de la URL sin declararlo, p. ej. lee el id desde la request).
4. **Un middleware es una clase `App\Middlewares\{Ucfirst(nombre)}Middleware`** con `handle(): bool` y `handleFailure(): void`. Retornar `false` en `handle()` corta la cadena y llama a `handleFailure()`. Los guards web redirigen; `ApiAuthMiddleware::handleFailure()` envía un JSON 401.
5. **Los controladores de vista extienden `App\Controllers\Controller`**, reciben `(Request $request, string $currentRoute)`, retornan `void` y hacen echo con `App\Core\Template::render('vista', $data)` (Plates, vistas en `resources/views` como `.php`/`.tpl`). Dentro de una plantilla usa `$this->layout('Layouts/app', [...])` y `$this->e()` para escapar.
6. **Los controladores de API extienden `App\Controllers\Api\ApiController` y RETORNAN un `JsonResponse`.** Nunca hagas `echo` desde un controlador de API. El router envía la respuesta retornada.
7. **Envuelve el cuerpo de cada acción de API en `$this->handle(fn (): JsonResponse => ...)`.** Cualquier excepción lanzada dentro la mapea `ExceptionHandler` a un error JSON. Lee el cuerpo JSON con `$this->input()` (retorna `array`, `[]` si está vacío/inválido). Valida con `$this->validate($data, $rules)` — un fallo lanza y se convierte en 422.
8. **Construye respuestas con `App\Core\Http\ApiResponse`:** `success(mixed $data = null, int $status = 200, array $meta = [])`, `error(string $code, string $message, int $status, array $details = [])`, `noContent()` (204). El envelope es `{"success":true,"data":...,"meta":...?}` / `{"success":false,"error":{"code","message","details"?}}`.
9. **Los modelos son subclases de `hstanleycrow\EasyPHPDBCore\Model`** en `namespace Models;` que definen `protected ?string $table`. Obtén la conexión compartida y pásala al modelo. Dentro de un controlador usa `$this->db()` (helper del controlador base, conexión compartida lazy); en otro lugar usa `App\Core\Database::connection()`. Así: `new User($this->db())`. No abras una conexión por query; no hardcodees `new MySQLPDOConnection(...)` en el código de la app.
10. **Nunca interpoles entrada del usuario en SQL.** Usa los valores de array de `create/update/delete` (vinculados) y `query('... WHERE x = ?')->getRecords([$x])`. `getById(int|string $id)` retorna la fila o `null`. Solo inserta directo enteros de confianza definidos en código (p. ej. `LIMIT`/`OFFSET` validados).
11. **Lanza `App\Core\Exceptions\ResourceNotFoundException`** desde una acción de API cuando falta un registro (EasyPHPDBCore retorna `null`, no lanza). Se mapea a 404.
12. **Mapeo excepción → HTTP** (en `ExceptionHandler`): `ResourceNotFoundException`/`RouteNotFoundException`/`ControllerNotFoundException` → 404; `EasyPHPFormValidator\ValidationException` → 422 (`details`); `EasyPHPDBCore\QueryException` con SQLSTATE `23xxx` → 409, en otro caso → 500; `EasyPHPDBCore\ConnectionException` → 503; cualquier otra cosa → 500. Los mensajes 500 se enmascaran salvo que `ENVIRONMENT` contenga `testing`.
13. **La auth es JWT + refresh tokens rotativos.** `JwtService` (HS256, `JWT_SECRET` ≥ 32 bytes) emite/verifica access tokens; `RefreshTokenService` emite refresh tokens opacos guardados hasheados, y `rotate()` revoca el viejo y retorna uno nuevo. Protege rutas con `->middleware('apiAuth')` y un header `Authorization: Bearer <access_token>`. Endpoints de auth: `POST /api/auth/{login,refresh,logout}/`.
14. **Los DTOs de salida son clases tipadas en `app/Resources/`** (p. ej. `UserResource`) con `fromRow(array): self` y `toArray(): array`. Úsalos para dar forma a la salida de la API y nunca filtres secretos (p. ej. hashes de contraseña).
15. **Las migraciones son strings DDL con nombre en `app/Database/Migrations/schema.php`**, aplicadas por `php app/Database/migrate.php` y registradas en una tabla `migrations` (idempotente). Agrega tablas nuevas al final con un nombre único.
16. **La configuración viene de `.env`** y se expone como constantes en `config/App.php`. Claves requeridas: `ENVIRONMENT`, `AUTH_SALT`, `BUSINESS_NAME`, `REPORT_ERROR_EMAIL`, y `DATABASE_HOST/NAME/USERNAME/PORT/CHARSET`. La auth agrega `JWT_SECRET`, `JWT_TTL`, `REFRESH_TTL`. El CRUD admin con datatables agrega las claves `DT_*` (ver esa sección). `CORS_ALLOWED_ORIGINS` es opcional (vacío = sin acceso cross-origin a `/api/`; orígenes separados por coma, o `*`). Faltar una clave requerida lanza `ConfigurationException` al arrancar.
17. **Todo el código nuevo usa `declare(strict_types=1)`**, parámetros/retornos/propiedades tipados, sin `mixed` innecesario, y arrays tipados o DTOs en vez de arrays sin forma.

## Feedback al usuario (flash messages)

`App\Core\FlashMessages` es un store de mensajes de un solo uso respaldado por sesión:

- `FlashMessages::set(string $type, string $message)` guarda un mensaje; `$type` se renderiza como la clase Bootstrap `alert-{type}`.
- `FlashMessages::display()` hace echo y limpia todos los mensajes pendientes.

Reglas:

- **`$type` debe ser una variante de alerta Bootstrap válida** — `success`, `danger`, `warning`, `info`. **No** uses `'error'`: `alert-error` no es una clase Bootstrap y se renderiza invisible (sobre todo en tema oscuro). Usa `'danger'`.
- **Llama a `display()` exactamente una vez, en el layout** (p. ej. `resources/views/Layouts/app.php`), nunca en una vista de contenido. Plates renderiza la vista hija *antes* del layout, así que ponerlo en una vista o lo renderiza en el lugar equivocado o silenciosamente no muestra nada cuando el layout de esa vista es distinto.

```php
FlashMessages::set('success', 'Usuario creado.');
$this->route('admin.usersList'); // redirige; el layout lo muestra en la siguiente página
```

## CRUD de vista admin (datatables)

El panel admin lista registros con un datatable server-side y los edita con
controladores de formulario planos. Cooperan tres piezas: una **definición**
(columnas + botones de fila + fuente SQL), un **handler AJAX** que transmite las
filas, y **componentes de botón reutilizables**.

### 1. Definición — `app/DatatablesDefinitions/{Nombre}.php`

Una clase plana resuelta por nombre a través de `DT_DEFINITIONS_NAMESPACE`.
Propiedades clave:

- `public string $dbTable` — la tabla **SQL** (usada para el query).
- `public string $primaryKey` — normalmente `'id'`.
- `public string $model` — la **base de ruta absoluta** usada para construir los
  hrefs de los botones de fila, p. ej. `'/admin/user'`. Esto *no* es la tabla SQL.
  El paquete construye cada href de botón como `{model}/{path}/{id}/`; como ese
  href es relativo, `$model` **debe** empezar con `/` y ser la base de ruta
  completa, o el navegador lo resuelve contra la URL del listado y duplica el
  segmento (`/admin/users/user/editar/1/` en vez de `/admin/user/editar/1/`).

`getColumns()` retorna definiciones de columna (`view_name`, `db_name`, `field`,
`format`). `getButtons()` retorna los botones de acción por fila; cada entrada
define `view_name` (el header de la columna, requerido), `path` (el segmento de URL,
p. ej. `editar`/`borrar`) y `buttonClass` (un componente de botón reutilizable).
`getJoinQuery()` retorna
`FROM \`{$this->dbTable}\` AS \`a\``; `getExtraCondition()` retorna un `WHERE`
opcional.

```php
namespace App\DatatablesDefinitions;

class User
{
    public string $dbTable    = 'users';
    public string $model      = '/admin/user'; // base de ruta absoluta para los botones de fila
    public string $primaryKey = 'id';

    public function getColumns(): array
    {
        return [
            ['view_name' => 'Id',     'db_name' => '`a`.`id`',   'field' => 'id',   'format' => 'text'],
            ['view_name' => 'Nombre', 'db_name' => '`a`.`name`', 'field' => 'name', 'format' => 'text'],
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

### 2. Construir la tabla para la vista

Usa `hstanleycrow\EasyPHPDatatableCRUD\DatatableUIBuilder`. En PHP 8.2 no puedes
encadenar un método directamente sobre `new` — envuélvelo `(new X())->...` o usa
sentencias separadas:

```php
$datatable = new DatatableUIBuilder('user', []); // ('user' resuelve DT_DEFINITIONS_NAMESPACE\User)
$datatable->setAddButtonClass(\App\Components\Buttons\AddButton::class); // botón de crear a nivel tabla
$datatable->setAjaxUrl('/datatable_handler.php');                        // de dónde el grid trae las filas
// pasa $datatable a la vista; llama a ->autoLoadDatatableJS() en la plantilla para emitir el JS
```

### 3. Handler AJAX — `public/datatable_handler.php`

Un endpoint independiente (sin ruta) que transmite las filas. **Inyecta el PDO de
EasyPHPDBCore** en `SSP::handle()` para que el datatable reutilice la conexión de
la app y no arme la suya — EasyPHPDBCore trata `DATABASE_CHARSET` como un comando
de init `SET NAMES ...` completo, que el paquete de datatables interpretaría mal
como un nombre de charset (error 2019 "Unknown character set"):

```php
<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->load();
require __DIR__ . '/../config/App.php';

hstanleycrow\EasyPHPDatatables\SSP::handle(App\Core\Database::connection()->getConnection());
```

### 4. Botones reutilizables — `app/Components/Buttons/`

Los botones son **componentes de UI reutilizables** (usables en datatables,
formularios, modales), así que viven bajo `app/Components/`, no en una carpeta de
feature. Cada uno extiende `BaseLink` + `LinkClient` del paquete y decora con
`EasyPHPWebComponents\Icon`, renderizando un `<a class="btn ...">` Bootstrap:

- **`EditButton`/`DeleteButton` por fila** — conectados vía el `buttonClass` de la definición; el builder pasa el **href** de la fila (`{model}/{path}/{id}/`) al constructor.
- **`AddButton` a nivel tabla** — conectado vía `setAddButtonClass()`; el builder pasa el **nombre de la definición** al constructor, que construye el href de crear (ajusta su default `/admin/{def}/add/` para que coincida con tu ruta de crear, p. ej. `agregar`).

Para reestilizar, edita la clase CSS / icono / texto en el componente, o
subclasealo. Font Awesome debe cargarse en el layout para que los iconos se vean.

### 5. Controladores de edición/creación

Controladores de vista normales. `showForm(int $id)` carga el registro y
renderiza el formulario; la acción de guardar lee la request, valida, escribe a
través de un modelo, pone un flash message y redirige. Las rutas de los botones
de fila se ven como `/admin/user/editar/[i:id]/` y `/admin/user/borrar/[i:id]/`,
coincidiendo con el `$model` + `path` de la definición.

### 6. Dropdowns de formulario — `app/Components/Dropdowns/`

Los dropdowns se **arman en el controlador** y la vista los echa (`<?= $dropdown ?>`),
nunca se ensamblan inline en la plantilla. Dos componentes:

- **ENUM / opciones fijas → `Dropdown` + `DropdownClient`.** La **clave** del array es el `<option value>`, el valor es el label, y `selected` es el valor actual:
  ```php
  $client = new DropdownClient(['S' => 'Activo', 'N' => 'Inactivo'], $selected);
  $html = (new Dropdown($client))->setName('active')->setId('active')->addClass('form-select')->render();
  ```
- **Foreign key (desde una tabla) → `DBDropdown`.** Le pasas la conexión, el modelo relacionado y un método que retorna `[['id' => .., 'name' => ..], ...]` con un query vinculado:
  ```php
  $html = (new DBDropdown($this->db(), \Models\Category::class, 'getForDropdownOptions', $selected))
      ->setName('category_id')->addClass('form-select')->render();
  ```
  El método del modelo:
  ```php
  public function getForDropdownOptions(): array
  {
      return $this->query('SELECT id, name FROM categories ORDER BY name ASC')->getRecords();
  }
  ```

El molde Users (`UsersCreateController`/`UsersUpdateController`) muestra el patrón
ENUM con un helper `buildEnumDropdown()`.

## Recetario de CRUD admin (paso a paso)

Para agregar un CRUD admin nuevo, **replica el molde Users incluido** y adapta los nombres.

**Molde Users (leer antes de generar):** `app/Models/User.php`,
`app/DatatablesDefinitions/User.php`,
`app/Controllers/Admin/Users/{UsersIndex,UsersCreate,UsersUpdate,UsersDelete}Controller.php` +
`UsersFormValidator.php`, `resources/views/admin/sections/Users/{UserForm,userList}.tpl.php`,
el bloque `# Users CRUD` en `routes/admin.php`, y el dropdown "Configuración" en
`resources/views/admin/sections/Nav/mainMenuNav.php`.

**Nombres:** Modelo + Definición **singular** (`Product`); tabla **plural** (`products`);
carpeta de controladores `app/Controllers/Admin/{Plural}/`; ruta de listado plural
(`/admin/products/`), rutas de acción singular (`/admin/product/...`).

**Pasos para `{Singular}` / tabla `{plural}`:**

1. **Migración** — agrega una entrada `CREATE TABLE IF NOT EXISTS` a `app/Database/Migrations/schema.php`, corre `php app/Database/migrate.php`.
2. **Modelo** — `Models\{Singular} extends Model` con `protected ?string $table = '{plural}';` (ver reglas 9-10 para queries propios vinculados).
3. **Definición** — `App\DatatablesDefinitions\{Singular}` con `$dbTable`, `$primaryKey`, `$model = '/admin/{singular}'` (absoluto), `getColumns()`, `getButtons()`, `getJoinQuery()`, `getExtraCondition()` (ver §1).
4. **Controladores** — `app/Controllers/Admin/{Plural}/` con Index/Create/Update/Delete + FormValidator. Index declara `protected string $DTDefinition = '{singular}';` y arma el grid con `(new CrudController())->generateDatatable($this->DTDefinition)`. Create/Update llaman a `$this->validate($rules, $messages)` **primero** (el controlador base lee POST y maneja errores/redirect — sin recolección manual), luego leen los campos con `$this->request->get()`, escriben, flash, `$this->route('admin.{plural}List')`. Los dropdowns se arman aquí (§6).
5. **Vistas** — `resources/views/admin/sections/{Plural}/` con `{Singular}Form.tpl.php` + `{plural}List.tpl.php`, ambas `$this->layout('Layouts/admin', [...])`. El listado renderiza `$datatable->setTableId('{plural}')->render()`. Nunca llames a `FlashMessages::display()` en una vista.
6. **Rutas** — seis líneas en `routes/admin.php`, todas `->middleware('auth', 'admin')` (listado, form de crear `agregar`, crear, form de editar `editar/[i:id]`, editar, borrar `borrar/[i:id]`).
7. **Menú** — en `mainMenuNav.php` agrega `${plural}URL = Route::getUrlFromName('admin.{plural}List');` y un `<li><a class="dropdown-item" href="<?= ${plural}URL; ?>">{Label}</a></li>` dentro del dropdown correspondiente.

**Gotchas del validador:** `min`/`max` miden **longitud** en strings pero **valor** en int/float reales. La regla `in` no se separa con pipe: `required in:a,b`. **Una regla desconocida se ignora en silencio** (el validador solo aplica las reglas presentes en su mapa, no lanza error) — así que una regla inventada como `numeric` no valida nada y nunca avisa. Las reglas válidas son: `required`, `string`, `integer`, `min`, `max`, `in`, `email`, `url`, `phone`, `svphone`, `confirmed`, `decimal`, `decimalNumber`, `greaterThanZero`, `nullable`, `date`, `after`, `afterOrEqual`, `before`, `beforeOrEqual`, `fileExtension`. Usa `integer` para enteros.

## Mínimo end-to-end (un recurso REST)

**Para un CRUD REST completo, replica el molde incluido `App\Controllers\Api\UsersController`**
y su bloque `# Users resource` en `routes/api.php`: un `index` paginado (retorna
`meta` con `page`/`per_page`/`total`/`total_pages`, topado por `MAX_PER_PAGE`),
más `show`/`store`/`update`/`destroy`, con la salida moldeada por un DTO
(`App\Resources\UserResource`, regla 14). El `index` paginado necesita `paginate()`
y `countAll()` en el modelo — cópialos de `Models\User`. El ejemplo de abajo es
solo la forma mínima:

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

Agrega la tabla `products` a `app/Database/Migrations/schema.php` y corre
`php app/Database/migrate.php`.

## Errores comunes a evitar

- Hacer `echo` desde un controlador de API en vez de retornar un `JsonResponse`.
- Tipar un parámetro de acción de ruta como `string` y castear a mano — el router ya castea al tipo declarado; tipa `int $id` directamente.
- Registrar una ruta de API sin la barra final, u olvidar `->middleware('apiAuth')` en una ruta protegida.
- Armar un `new MySQLPDOConnection(...)` en el código de la app en vez de `$this->db()` / `Database::connection()`.
- Interpolar entrada en `query('... WHERE id = ' . $id)` en vez de `->getRecords([$id])`.
- Retornar una fila cruda de BD que contiene un hash de contraseña en vez de un DTO `Resource`.
- Esperar que EasyPHPDBCore lance cuando falta un registro — `getById()` retorna `null`; lanza tú `ResourceNotFoundException` para un 404.
- Olvidar `protected ?string $table` en una subclase de `Model`.
- Poner el `$model` de una definición de datatable con el nombre pelado de la tabla — debe ser la base de ruta absoluta (`/admin/user`) o las URLs de los botones de fila duplican el segmento.
- Encadenar un método directamente sobre `new DatatableUIBuilder(...)` en PHP 8.2 (error de parseo) — envuelve `(new X())->...`.
- Usar el tipo de flash `'error'` (invisible) en vez de `'danger'`, o llamar a `FlashMessages::display()` en una vista en vez de una sola vez en el layout.

## Tests

`vendor/bin/phpunit`. Los tests unitarios/de integración usan una conexión SQLite
en memoria (`Tests\Support\SqliteConnection`) inyectada vía
`App\Core\Database::swap(...)`; los controladores de API se testean llamando a la
acción y verificando el `JsonResponse` retornado. Los tests de routing cubren la
coerción de tipos de parámetros. Unos pocos tests de integración con MySQL apuntan
a `db_corvagia_test` y se saltan cuando no está disponible.
