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

## Módulo de Usuarios, Sesión y Layout Compartido

Estructura añadida:

```
marcas-crud/
├── config/
│   └── session.php              <- Bootstrap de sesión + funciones de autenticación
├── includes/
│   ├── header.php                <- <head> + barra de navegación + apertura de <div class="container-fluid">
│   └── footer.php                <- Cierra el contenedor + carga jQuery/Bootstrap/SweetAlert2
├── api/
│   ├── auth.php                  <- login / logout / estado de sesión
│   └── usuarios.php              <- CRUD de usuarios (solo administradores)
├── assets/js/
│   ├── login.js
│   └── usuarios.js
├── login.php                     <- Página de inicio de sesión (sin navbar)
├── logout.php                    <- Cierra sesión y redirige a login.php
├── usuarios.php                  <- Gestión de usuarios (solo administradores)
├── instalar_admin.php            <- Script de un solo uso: crea el primer administrador
└── setup_usuarios.sql            <- CREATE TABLE de la tabla `usuarios`
```

### Instalación del módulo de usuarios

1. Ejecuta `setup_usuarios.sql` en tu base de datos (crea la tabla `usuarios`).
2. Abre `instalar_admin.php` **una sola vez** en el navegador — crea el usuario administrador inicial (`admin` / `admin123` por defecto; puedes cambiar esos valores editando el script antes de ejecutarlo).
3. **Elimina `instalar_admin.php` del servidor** una vez creado el administrador (por seguridad — no debe quedar accesible).
4. Inicia sesión en `login.php` y, desde el módulo **Usuarios**, cambia la contraseña o crea más usuarios.

> ⚠️ No usé una contraseña con hash "inventado" en el SQL porque bcrypt genera un hash distinto cada vez y depende del PHP de tu servidor — por eso el hash se genera con `instalar_admin.php`, garantizando que sea válido en tu entorno.

### Roles y permisos

| Rol | Acceso |
|---|---|
| **general** | Marcas, Corridas, Modelos, Existencias, Consulta de Existencias, Inventario |
| **administrador** | Todo lo anterior **+** módulo de Usuarios (crear/editar/eliminar usuarios) |

Reglas de seguridad implementadas en `api/usuarios.php`:
- Un administrador no puede quitarse su propio rol ni desactivarse si es el **único administrador activo** del sistema (evita quedarte sin acceso).
- Un usuario no puede eliminar su propia cuenta mientras tiene la sesión iniciada.
- Las contraseñas se guardan con `password_hash()` (bcrypt) y se verifican con `password_verify()` — nunca en texto plano.

### Cómo funciona la protección de páginas y endpoints

- **Páginas** (`index.php`, `corridas.php`, etc.): incluyen `config/session.php` y llaman a `requerirLogin()` al inicio — si no hay sesión, redirige a `login.php`. `usuarios.php` usa `requerirAdmin()` en su lugar.
- **Endpoints de la API** (`api/marcas.php`, `api/corridas.php`, etc.): ahora incluyen `config/session.php` y llaman a `requerirLoginApi()` — si no hay sesión, responden `401` en JSON en vez de redirigir (correcto para llamadas AJAX). `api/usuarios.php` usa `requerirAdminApi()`.

### Layout compartido (`header.php` / `footer.php`)

Todas las páginas ahora siguen este patrón:

```php
<?php
require_once __DIR__ . '/config/session.php';
requerirLogin(); // o requerirAdmin() en usuarios.php

$tituloPagina = 'Título de la página';
$paginaActiva = 'marcas'; // 'marcas' | 'existencias' | 'inventario' | 'usuarios'
require_once __DIR__ . '/includes/header.php';
?>

<!-- contenido de la página -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/mi-pagina.js"></script>
</body>
</html>
```

La barra de navegación (`header.php`) muestra: **Marcas**, **Existencias**, **Inventario**, y — solo si el usuario es administrador — **Usuarios**. También muestra el nombre y rol del usuario en sesión, y un botón para cerrar sesión.

## Módulo de Ventas — origen Pascal/Delphi (¡nuevo!)

A diferencia del resto del proyecto (que venía de Laravel), este módulo se convirtió a partir de un
programa de punto de venta en **Object Pascal/Delphi** (`FVentasa.pas`, `FSelPago.pas`, `FM_SelVenta.pas`,
`FConcentra.pas`) que usaba las mismas tablas MySQL que ya veníamos usando — así que **no se necesitó
ninguna tabla nueva**, solo se completó `rventas` (que tenía datos en el respaldo pero no su `CREATE TABLE`)
y se documentó la vista `vmodecorr` que ya existía.

### Estructura añadida (Fase 1)

```
marcas-crud/
├── api/
│   └── ventas.php          <- Captura, costeo PEPS, contadores de empresa, guardado (solo contado)
├── assets/js/
│   └── ventas.js
├── ventas.php                <- Punto de venta (código de barras + búsqueda manual + cobro)
└── setup_ventas.sql          <- CREATE TABLE rventas (faltaba) + vista vmodecorr
```

### Qué cubre esta fase (venta de CONTADO)

| Original Pascal | Conversión |
|---|---|
| `ECodBarraKeyPress` (Enter, código de 12 dígitos → toma 9 centrales) | JS extrae los 9 caracteres centrales igual que `Copy(ECodBarra.Text,3,9)` |
| Prefijo `D` = devolución (`Agrega_Entrada`) | `POST api/ventas.php?action=devolucion` — regresa 1 pieza a `articulos.EXISTENCIA` |
| `Agrega_Detalle()` (busca articulo+marca+modelo) | `GET api/ventas.php?action=buscar_codbarra` |
| `FM_SelVenta` (búsqueda manual marca/modelo/talla) | `GET api/ventas.php?action=buscar_manual` + `action=modelos_marca` (usa la vista `vmodecorr`) |
| F12 "Facturado" → `FSelPago` (efectivo) | Modal de cobro en `ventas.php`, valida que lo pagado ≥ total |
| `Actualiza_Inventario(True)` — costeo **PEPS** | `POST api/ventas.php?action=guardar_contado`: busca en `peps` la entrada más antigua con existencia, toma su `PRECIOCO`, la descuenta |
| Actualiza contadores en `empresa` (RECFISCAL, GTVTA, GTIVA, VENTADIA, etc.) | Mismo cálculo, dentro de la misma transacción PDO |
| `Actualiza_Inventario_General()` (descuenta `articulos.EXISTENCIA`) | Igual, dentro de la transacción |
| Inserta en `ventas` + `detventas` | Igual, con folio calculado como `MAX(VENTANO)+1` |
| Desglose de IVA (÷1.16) en `Imprime_Ticket` | Se calcula igual (subtotal/IVA) y se regresa en la respuesta para el resumen en pantalla |
| `FConcentra` (aviso de inventario tras cada artículo) | Se decidió **no** reproducir el grid completo de Delphi (que muestra existencias de TODOS los modelos de la marca) como una serie de ventanas modales bloqueantes — en su lugar, el resumen de la venta muestra la **existencia restante de cada artículo vendido**. Si quieres el grid completo estilo FConcentra, puedes abrir **Consulta de Existencias** filtrando por esa marca (módulo ya existente). |

### Lo que se agregó en la Fase 2

| Original Pascal | Conversión |
|---|---|
| F6 "Remisión" → `FSelPago` con `RGFormaPago` (Efectivo/Vale) | Modal "Vale / Remisión" en `ventas.php`, con las mismas dos sub-opciones |
| Total en remisión = precio de vale (`PRECIOCO`), no precio de venta | `guardar_remision` suma `preciovale` de cada artículo, no `precio` |
| Validación de vendedor: debe existir y `ESTADO <> 'C'` | Réplica exacta en `guardarVentaRemision()` (¡`'C'`, no `'I'`, como en otras tablas!) |
| Validación de vale bloqueado (`bloqueados.novendedor` + `novale`) | Réplica exacta, con el motivo del bloqueo incluido en el mensaje de error |
| `vgImporteTot / 4` ("importe de vale") mostrado en pantalla | Se calcula igual y se muestra en el resumen y en el ticket |
| `DiaPago()` — calcula cuándo se descontará el vale de nómina | Función `calcularDiaPago()` con las mismas reglas exactas por rango de día del mes |
| `Guarda_Venta` (rama `vbgRegVenta=False`) → `rventas`/`rdetventas`, folio `COUNT(*)+1` | Igual, incluido el detalle de que `rdetventas.PRECIOCO` se deja `NULL` (el original nunca lo llena en esta rama) |
| Reportes Rave `ReciboPago` / `ReciboPagoVale` | Ticket HTML generado en el navegador (`imprimirTicket()` en `ventas.js`), pensado para impresoras térmicas de 80mm, que se abre en una ventana nueva y llama a `window.print()` automáticamente |
| `Abre_Cajon_Dinero()` (comando ESC/POS directo) | Botón "Cajón" — permanece como aviso informativo; muchas impresoras de tickets abren el cajón automáticamente al recibir el trabajo de impresión, así que en la práctica el ticket impreso puede resolver esto sin código adicional, dependiendo del modelo de impresora |

## Módulo de Vales — Fase 3 (Vendedores, Bloqueados, Corte de Caja, Consulta de Vales)

Convertido a partir de `FBloqueaVale.pas`, `FCVales.pas` y `CorteD.pas` (todos parte del mismo sistema Pascal/Delphi de ventas).

### Estructura añadida

```
marcas-crud/
├── api/
│   ├── vendedores.php        <- CRUD de vendedores (tabla vendedores, ya existente)
│   ├── bloqueados.php        <- Equivalente a FBloqueaVale.pas
│   ├── corte.php              <- Equivalente a CorteD.pas
│   └── consulta_vales.php     <- Equivalente a FCVales.pas
├── assets/js/
│   ├── vendedores.js, bloqueados.js, corte.js, consulta_vales.js
├── vendedores.php, bloqueados.php, corte.php, consulta_vales.php
```

Todo agrupado en el menú **Vales ▾** de la barra de navegación, más un enlace directo a **Corte de Caja**.

### Vendedores (`vendedores.php`)

CRUD completo sobre la tabla `vendedores` ya existente (no estaba en el alcance original como pantalla dedicada,
pero era necesario para dar de alta a quien va a usar vales — antes solo se podía hacer directo en la base de datos).
Protecciones agregadas: no se puede eliminar un vendedor(a) que ya tenga ventas de vale registradas (se sugiere
marcarlo(a) como "Cancelado" en su lugar).

### Vales Bloqueados (`bloqueados.php`) — de `FBloqueaVale.pas`

| Original | Conversión |
|---|---|
| `Guarda_Datos(Nuevo: Boolean)` (INSERT/UPDATE directo con SQL concatenado) | Mismo INSERT/UPDATE pero con **sentencias preparadas** (el original era vulnerable a inyección SQL) |
| Si el campo queda vacío, se guarda como `'0'` | Réplica exacta en `crearBloqueo()` |
| Llave compuesta `(novendedor, novale)` | Igual, usada en `PUT`/`DELETE` |
| `Borrar()` | `DELETE api/bloqueados.php?novendedor=&novale=` |

Estos bloqueos son justo los que valida `api/ventas.php` al pagar con vale (Fase 2).

### Corte de Caja (`corte.php`) — de `CorteD.pas`

| Original | Conversión |
|---|---|
| Reporte Rave `VentasDia` (`RSOCIAL`, `RECIBOSDIA`, `SUBTOTAL`, `VENTADIA`, `IVADIA`) | Mismo contenido, como HTML imprimible (`imprimirCorte()` en `corte.js`) |
| `UPDATE empresa SET RECIBOSDIA=0, SUBTOTAL=0, VENTADIA=0, IVADIA=0` | Idéntico, en `POST api/corte.php?action=confirmar` |

**Cambio deliberado respecto al original:** el Delphi original imprimía y reiniciaba los contadores en un solo
clic, sin confirmación. Agregué una alerta de confirmación explícita antes de reiniciar (acción irreversible) y
separé "Imprimir vista previa" de "Confirmar corte", para evitar que alguien reinicie el día por accidente.
Si prefieres el comportamiento original de un solo paso, dímelo y lo ajusto.

### Consulta de Vales (`consulta_vales.php`) — de `FCVales.pas`

Pantalla de consulta histórica de ventas por vale/remisión (tabla `rventas`), con los mismos filtros del
original (número de vale, número de vendedor(a), fecha) y el mismo total sumado de las notas que hacen match.
Al hacer clic en una nota se muestra su detalle de artículos, usando la vista `vmodrventas` (igual que
`IBVModRVentas` en el original).

### Nota técnica: tabla `peps` sin llave primaria

La tabla `peps` no tiene una columna `id`. El código de la API actualiza la fila más antigua con existencia
usando `MARCANO+MODELONO+TALLA+FECHAENT+PRECIOCO` como filtro (con `LIMIT 1`), igual de seguro que el original
en Delphi. Si vas a manejar mucho volumen, `setup_ventas.sql` incluye el `ALTER TABLE` opcional para agregarle
un `id AUTO_INCREMENT`.

## Todos los módulos convertidos ✅

- **Autenticación y Usuarios** (login, roles, layout compartido)
- **Marcas**, **Corridas**, **Modelos** (CRUD completos)
- **Existencias** (captura por modelo/talla)
- **Consulta de Existencias**, **Inventario**
- **Ventas**: captura, cobro de contado con costeo PEPS, pago con vale/remisión, ticket imprimible en HTML, devoluciones simples
- **Vales**: gestión de vendedores, vales bloqueados, corte de caja diario, consulta histórica de vales
