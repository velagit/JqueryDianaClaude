# CRUD de Marcas — PHP + PDO + jQuery

Conversión del módulo de Marcas de Laravel a PHP nativo con PDO (backend) y jQuery + Bootstrap 5 + SweetAlert2 (frontend).

## Estructura

```
marcas-crud/
├── config/
│   └── database.php      <- Configura aquí tus credenciales de MySQL
├── api/
│   └── marcas.php         <- Endpoint REST (reemplaza a MarcaController)
├── assets/
│   ├── css/style.css       <- Tema azul y blanco
│   └── js/marcas.js        <- Lógica jQuery (AJAX + SweetAlert2)
├── index.php               <- Página única (listado + modal crear/editar)
└── README.md
```

## Instalación

1. Coloca la carpeta `marcas-crud` dentro de tu servidor (XAMPP/WAMP: `htdocs`, o cualquier servidor con PHP 7.4+ y la extensión `pdo_mysql` habilitada).
2. Edita `config/database.php` con tus datos de conexión:
   ```php
   $host     = 'localhost';
   $dbname   = 'tu_base_de_datos';
   $username = 'root';
   $password = '';
   ```
3. La tabla `marcas` ya existe según tu migración original:
   ```sql
   CREATE TABLE marcas (
       MARCANO INT NOT NULL,
       MARCADES VARCHAR(20) NULL,
       created_at TIMESTAMP NULL,
       updated_at TIMESTAMP NULL,
       PRIMARY KEY (MARCANO)
   );
   ```
   No necesitas ejecutar nada nuevo si ya la tenías creada por Laravel.
4. El módulo de eliminación consulta las tablas `modelos` y `corridas` (columna `MARCANO`), igual que el controlador original — asegúrate de que existan si usas esa validación.
5. Abre `index.php` en el navegador.

## Equivalencias con el código Laravel original

| Laravel                                     | Conversión                                              |
|----------------------------------------------|-----------------------------------------------------------|
| `MarcaController@index` (con paginación 5)   | `GET api/marcas.php?buscar=&page=`                        |
| `Marca::siguienteCodigo()`                   | `GET api/marcas.php?action=nuevo_codigo`                  |
| `MarcaController@store` (+ validación)       | `POST api/marcas.php` (body JSON)                          |
| `MarcaController@update` (+ validación)      | `PUT api/marcas.php?MARCANO=x` (body JSON)                 |
| `MarcaController@destroy` (valida relaciones)| `DELETE api/marcas.php?MARCANO=x`                           |
| Vistas `create.blade.php` / `edit.blade.php` | Un solo modal Bootstrap reutilizado (`#modalMarca`)         |
| `confirm()` del navegador / SweetAlert2      | SweetAlert2 (igual que ya usabas)                           |
| `@if($errors->any())`                        | Validación inline con clases `is-invalid` / `invalid-feedback` |

## Notas importantes

- **Seguridad**: todas las consultas usan sentencias preparadas de PDO (protección contra SQL injection).
- **Validaciones**: se replican del lado del servidor exactamente igual que en `MarcaController` (`required`, `max:20`, `unique` para MARCANO al crear).
- **No hay recarga de página**: todo el CRUD (listar, crear, editar, eliminar, buscar, paginar) funciona vía AJAX.
- Los enlaces a "Corridas", "Modelos" y "Entradas" en la tabla apuntan a `corridas.php`, `modelos.php` y `existencias.php` — deberás crear esos módulos cuando conviertas esas secciones (dime cuándo quieras y seguimos con ellas).

## Módulo de Corridas (anidado a Marcas)

Estructura añadida:

```
marcas-crud/
├── api/
│   └── corridas.php        <- Reemplaza a CorridaController
├── assets/js/corridas.js   <- Lógica jQuery del CRUD de corridas
└── corridas.php             <- Página (listado + modal crear/editar)
```

Se accede vía `corridas.php?marcano=1` (el botón "Corridas" del listado de marcas ya enlaza aquí).

### Equivalencias

| Laravel                                    | Conversión                                                        |
|----------------------------------------------|----------------------------------------------------------------------|
| `CorridaController@index($marcano)`          | `GET api/corridas.php?marcano=x`                                     |
| Cálculo de siguiente `CORRIDANO` por marca   | Automático en `POST api/corridas.php` (MAX + 1, igual que el original) |
| `CorridaController@store`                    | `POST api/corridas.php` (body JSON: MARCANO, TALLAINI, TALLAFIN, CLASIFICA) |
| `CorridaController@update`                   | `PUT api/corridas.php?marcano=x&corridano=y`                          |
| `CorridaController@destroy` (valida modelos) | `DELETE api/corridas.php?marcano=x&corridano=y`                        |
| Combo de `Clasifica::all()`                  | `GET api/corridas.php?action=clasificaciones`                          |
| `$corrida->clasifica->CLASIFIDES`            | `LEFT JOIN clasifica` dentro de la consulta de listado                 |

### Dependencia importante

El módulo asume que existe una tabla **`clasifica`** con al menos las columnas `CLASIFINO` y `CLASIFIDES` (igual que en tu proyecto Laravel). No fue necesario tocarla, solo se consulta.

## Módulo de Modelos (anidado a Marcas)

Estructura añadida:

```
marcas-crud/
├── api/
│   └── modelos.php        <- Reemplaza a ModeloController
├── assets/js/modelos.js   <- Lógica jQuery del CRUD de modelos
└── modelos.php              <- Página (listado + modal crear/editar)
```

Se accede vía `modelos.php?marcano=1` (el botón "Modelos" del listado de marcas ya enlaza aquí).

### Equivalencias

| Laravel                                            | Conversión                                                              |
|-------------------------------------------------------|------------------------------------------------------------------------|
| `ModeloController@index` (paginate 7 + search)         | `GET api/modelos.php?marcano=x&search=&page=y`                          |
| Cálculo de siguiente `MODELONO` por marca              | Automático en `POST api/modelos.php` (MAX + 1, igual que el original)   |
| `ModeloController@store`                               | `POST api/modelos.php` (body JSON con todos los campos del modelo)      |
| `ModeloController@update`                               | `PUT api/modelos.php?marcano=x&modelono=y`                              |
| `ModeloController@destroy` (valida artículos con existencia) | `DELETE api/modelos.php?marcano=x&modelono=y`                     |
| Combo de `Corrida::where('MARCANO', ...)`               | `GET api/modelos.php?action=corridas&marcano=x`                          |
| `$corrida->clasifica->CLASIFIDES` en tabla y combo      | `LEFT JOIN corridas` + `LEFT JOIN clasifica` en las consultas            |

### Lógica de eliminación replicada exactamente

Igual que en el `ModeloController` original:
1. Si existen artículos con `EXISTENCIA > 0` para ese modelo → se bloquea el borrado.
2. Si no los hay → se elimina el modelo **y** se limpian (eliminan) los artículos con `EXISTENCIA <= 0` asociados a ese modelo.

### Dependencia importante

El módulo consulta una tabla **`articulos`** (columnas `MARCANO`, `MODELONO`, `EXISTENCIA`) para la validación de borrado — no fue necesario tocarla, solo se consulta, igual que en el original.

## Módulo de Existencias (captura por modelo/talla)

Estructura añadida:

```
marcas-crud/
├── api/
│   └── existencias.php        <- Reemplaza a ExistenciaController
├── assets/js/existencias.js   <- Lógica jQuery de captura de existencias
└── existencias.php              <- Página (grid modelos x tallas)
```

Se accede vía `existencias.php?marcano=1` (el botón "Entradas" del listado de marcas ya enlaza aquí).

### Equivalencias

| Laravel                                                | Conversión                                                                 |
|-----------------------------------------------------------|-----------------------------------------------------------------------------|
| `ExistenciaController@index` (calcula rango de tallas)     | `GET api/existencias.php?action=tallas&marcano=x`                            |
| Filtro `$request->modelo` sobre la colección de modelos    | `GET api/existencias.php?action=modelos&marcano=x&modelo=filtro` (SQL `LIKE`) |
| `ExistenciaController@store` (upsert de artículos + precio/fecha) | `POST api/existencias.php?action=store` (body JSON, dentro de una transacción PDO) |
| Generación de `CODBARRA`                                    | Misma fórmula: `str_pad` de MARCANO(3) + MODELONO(4) + TALLA(3)              |
| Validación "al menos una existencia > 0" (JS original)       | Replicada en `existencias.js`, y también revalidada en el servidor            |

### ⚠️ Nota sobre precio y fecha (revisar)

En el `ExistenciaController` original, `store()` espera `precios[$modelono]` y `fecha[$modelono]` (un valor **por modelo**), pero la vista Blade solo enviaba un **precio y fecha generales** (`precio_general`, `fecha_general`). Esto significa que en tu app actual, **todo modelo presente en la cuadrícula recibe el mismo precio/fecha general**, sin importar si tuvo existencia capturada o no.

Repliqué exactamente ese comportamiento (precio/fecha generales aplicados a todos los modelos visibles en el grid al guardar). Si en realidad querías un precio/fecha **por modelo**, dime y agrego esos campos a la tabla del formulario.

## Módulo de Consulta de Existencias (ConsartController)

Estructura añadida:

```
marcas-crud/
├── api/
│   └── consulta_existencias.php        <- Reemplaza a ConsartController
├── assets/js/consulta_existencias.js   <- Lógica jQuery de la consulta
└── consulta_existencias.php              <- Página de consulta general
```

Se accede vía `consulta_existencias.php` (sin parámetros — el usuario elige la marca desde un combo, igual que en Laravel).

### Equivalencias

| Laravel                                              | Conversión                                                                    |
|----------------------------------------------------------|--------------------------------------------------------------------------------|
| `ConsartController@index` (marca + modelo + corrida)       | `GET api/consulta_existencias.php?action=consulta&marcano=&modelo=&corrida=&page=` |
| `Marca::all()` para el combo                                | `GET api/consulta_existencias.php?action=marcas`                                |
| `$marca->corridas` para el combo de corridas                | `GET api/consulta_existencias.php?action=corridas&marcano=x`                     |
| Cálculo de `$tallas` (según corrida elegida o rango total)   | Misma lógica replicada con bucles PHP (`for` con paso 5)                         |
| `$modelos = $modelosQuery->paginate(10)`                     | Paginación de 10 en la consulta SQL (`LIMIT`/`OFFSET`)                           |
| `session()->flash('no_modelos', true)`                       | Se devuelve `noModelos: true` en la respuesta JSON, y el JS muestra el SweetAlert2 |
| Swal "Sin corridas" cuando la marca no tiene corridas         | Igual, disparado desde `consulta_existencias.js`                                |

### Nota sobre `resultado.blade.php`

Esta vista referenciaba una ruta `existencias.filtrar` que **no existe** en tu `web.php` actual — parece código huérfano de una versión anterior, así que no la convertí. Si en realidad la usas en otro flujo, compárteme la ruta correspondiente y la agrego.

## Módulo de Inventario (InventarioController)

Estructura añadida:

```
marcas-crud/
├── api/
│   └── inventario.php        <- Reemplaza a InventarioController
├── assets/js/inventario.js   <- Lógica jQuery (basada en la original, ya usaba AJAX)
└── inventario.php              <- Página de consulta
```

Se accede vía `inventario.php` (sin parámetros).

### Equivalencias

| Laravel                                        | Conversión                                                          |
|-----------------------------------------------------|-----------------------------------------------------------------------|
| `InventarioController@index` (combo de marcas)         | `GET api/inventario.php?action=marcas`                                 |
| `InventarioController@modelosPorMarca`                  | `GET api/inventario.php?action=modelos&marcano=x`                       |
| `InventarioController@corridasPorMarca` (con `clasifica`)| `GET api/inventario.php?action=corridas&marcano=x` (anida `clasifica` igual que Eloquent) |
| `InventarioController@consulta`                         | `GET api/inventario.php?action=consulta&marcano=x&modelodes=&corridano=` |
| `response()->json(['error' => ...], 404)`                | Mismo formato exacto: `{"error": "..."}` con código 404                 |

Como la vista original **ya usaba jQuery y AJAX puro** (no Blade con formularios POST), esta conversión fue casi directa — solo cambié las rutas de Laravel por el endpoint PHP y mantuve el mismo formato de respuesta JSON (`{tallas, datos}` / `{error}`) para no romper la lógica del frontend.

### Nota

El endpoint `modelosPorMarca` existe en la API (igual que en tu Laravel original) pero no lo usa la vista `index.blade.php` — lo dejé disponible por si lo consumes desde otro lugar.

## Todos los módulos convertidos ✅

Con esto quedan completos los 5 módulos de tu proyecto Laravel:
- **Marcas** (CRUD completo)
- **Corridas** (anidado a Marcas)
- **Modelos** (anidado a Marcas)
- **Existencias** (captura por modelo/talla)
- **Consulta de Existencias** (ConsartController)
- **Inventario** (InventarioController)




1. Páginas normales (cualquier usuario con sesión)
Se coloca al inicio absoluto del archivo, antes de cualquier HTML o echo:
php<?php
require_once __DIR__ . '/config/session.php';
requerirLogin();

$tituloPagina = 'Título de la página';
$paginaActiva = 'marcas'; // 'marcas' | 'existencias' | 'inventario' | 'usuarios'
require_once __DIR__ . '/includes/header.php';
?>
Ejemplos donde ya está así: index.php, corridas.php, modelos.php, existencias.php, consulta_existencias.php, inventario.php.
2. Páginas solo para administradores
Igual que arriba, pero con requerirAdmin() en vez de requerirLogin():


php<?php
require_once __DIR__ . '/config/session.php';
requerirAdmin();

$tituloPagina = 'Gestión de Usuarios';
$paginaActiva = 'usuarios';
require_once __DIR__ . '/includes/header.php';
?>
Ejemplo: usuarios.php.
3. Endpoints de la API (cualquier usuario con sesión)
Se coloca justo después de incluir database.php, antes de procesar la petición:
php<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
requerirLoginApi();

// ... resto del código del endpoint
Ejemplo: api/marcas.php, api/corridas.php, api/modelos.php, api/existencias.php, api/consulta_existencias.php, api/inventario.php.
4. Endpoints de la API solo para administradores
php<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
requerirAdminApi();

// ... resto del código del endpoint

¿Por qué hay una versión "página" y otra "API"?
FunciónUsoQué hace si no hay sesiónrequerirLogin()Páginas HTMLRedirige a login.phprequerirAdmin()Páginas HTML (admin)Redirige a login.php, o a index.php si no es adminrequerirLoginApi()Endpoints AJAXResponde JSON {"success":false,...} con código 401requerirAdminApi()Endpoints AJAX (admin)Responde JSON con código 403 si no es admin

Una página HTML necesita redirigir (el navegador cambia de URL), mientras que un endpoint AJAX necesita responder JSON (para que el $.ajax() de jQuery lo capture en .fail() sin romper la página). Por eso son funciones separadas, aunque ambas están definidas en el mismo archivo config/session.php que ya tienes.