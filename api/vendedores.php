<?php
/**
 * API REST para el CRUD de Vendedores (usados en ventas por vale/remisión).
 * La tabla `vendedores` ya existía en la base de datos.
 *
 * Endpoints:
 *   GET    api/vendedores.php                     -> listar
 *   GET    api/vendedores.php?id=5                 -> obtener uno
 *   POST   api/vendedores.php  (body JSON)          -> crear
 *   PUT    api/vendedores.php?id=5 (body JSON)      -> actualizar
 *   DELETE api/vendedores.php?id=5                  -> eliminar
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json; charset=utf-8');
requerirLoginApi();

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'GET':
        if (isset($_GET['id'])) {
            obtenerVendedor($pdo, $_GET['id']);
        } else {
            listarVendedores($pdo);
        }
        break;

    case 'POST':
        crearVendedor($pdo);
        break;

    case 'PUT':
        if (!isset($_GET['id'])) {
            responder(false, 'Número de vendedor requerido.', null, 400);
        }
        actualizarVendedor($pdo, $_GET['id']);
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            responder(false, 'Número de vendedor requerido.', null, 400);
        }
        eliminarVendedor($pdo, $_GET['id']);
        break;

    default:
        responder(false, 'Método no permitido.', null, 405);
}

/* ------------------------------------------------------------------ */

function responder($success, $message = '', $data = null, $code = 200)
{
    http_response_code($code);
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

function listarVendedores($pdo)
{
    $busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
    $where = '';
    $params = [];
    if ($busqueda !== '') {
        $where = 'WHERE NOMBRE LIKE :busqueda OR VENDEDORNO LIKE :busqueda2';
        $params[':busqueda'] = "%$busqueda%";
        $params[':busqueda2'] = "%$busqueda%";
    }
    $stmt = $pdo->prepare("SELECT VENDEDORNO, NOMBRE, DIRECCION, TELEFONO1, TELEFONO2, TELEFONO3, VALEINICIA, VALETERMINA, ESTADO FROM vendedores $where ORDER BY NOMBRE ASC");
    $stmt->execute($params);
    responder(true, '', $stmt->fetchAll());
}

function obtenerVendedor($pdo, $id)
{
    $stmt = $pdo->prepare('SELECT VENDEDORNO, NOMBRE, DIRECCION, TELEFONO1, TELEFONO2, TELEFONO3, VALEINICIA, VALETERMINA, ESTADO FROM vendedores WHERE VENDEDORNO = :id');
    $stmt->execute([':id' => $id]);
    $vendedor = $stmt->fetch();
    if (!$vendedor) {
        responder(false, 'Vendedor no encontrado.', null, 404);
    }
    responder(true, '', $vendedor);
}

function validarDatos($pdo, $vendedorno, $nombre, $esCreacion, array &$errores)
{
    if ($vendedorno === '' || !ctype_digit((string) $vendedorno)) {
        $errores['vendedorno'] = 'El número de vendedor es obligatorio y debe ser numérico.';
    } elseif ($esCreacion) {
        $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM vendedores WHERE VENDEDORNO = :no');
        $stmt->execute([':no' => $vendedorno]);
        if ((int) $stmt->fetch()['total'] > 0) {
            $errores['vendedorno'] = 'Ese número de vendedor ya existe.';
        }
    }

    if (trim((string) $nombre) === '') {
        $errores['nombre'] = 'El nombre es obligatorio.';
    }
}

function crearVendedor($pdo)
{
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $vendedorno   = trim((string) ($input['vendedorno'] ?? ''));
    $nombre       = trim((string) ($input['nombre'] ?? ''));
    $direccion    = trim((string) ($input['direccion'] ?? ''));
    $telefono1    = trim((string) ($input['telefono1'] ?? ''));
    $telefono2    = trim((string) ($input['telefono2'] ?? ''));
    $telefono3    = trim((string) ($input['telefono3'] ?? ''));
    $valeinicia   = $input['valeinicia'] ?? null;
    $valetermina  = $input['valetermina'] ?? null;
    $estado       = $input['estado'] ?? 'A';

    $errores = [];
    validarDatos($pdo, $vendedorno, $nombre, true, $errores);
    if (!empty($errores)) {
        responder(false, 'Errores de validación.', ['errores' => $errores], 422);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO vendedores (VENDEDORNO, NOMBRE, DIRECCION, TELEFONO1, TELEFONO2, TELEFONO3, VALEINICIA, VALETERMINA, ESTADO)
         VALUES (:no, :nombre, :direccion, :tel1, :tel2, :tel3, :valeini, :valefin, :estado)'
    );
    $stmt->execute([
        ':no'       => $vendedorno,
        ':nombre'   => $nombre,
        ':direccion'=> $direccion ?: null,
        ':tel1'     => $telefono1 ?: null,
        ':tel2'     => $telefono2 ?: null,
        ':tel3'     => $telefono3 ?: null,
        ':valeini'  => $valeinicia !== '' ? $valeinicia : null,
        ':valefin'  => $valetermina !== '' ? $valetermina : null,
        ':estado'   => $estado,
    ]);

    responder(true, 'Vendedor(a) creado(a) correctamente.');
}

function actualizarVendedor($pdo, $id)
{
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $nombre       = trim((string) ($input['nombre'] ?? ''));
    $direccion    = trim((string) ($input['direccion'] ?? ''));
    $telefono1    = trim((string) ($input['telefono1'] ?? ''));
    $telefono2    = trim((string) ($input['telefono2'] ?? ''));
    $telefono3    = trim((string) ($input['telefono3'] ?? ''));
    $valeinicia   = $input['valeinicia'] ?? null;
    $valetermina  = $input['valetermina'] ?? null;
    $estado       = $input['estado'] ?? 'A';

    $stmt = $pdo->prepare('SELECT VENDEDORNO FROM vendedores WHERE VENDEDORNO = :id');
    $stmt->execute([':id' => $id]);
    if (!$stmt->fetch()) {
        responder(false, 'Vendedor no encontrado.', null, 404);
    }

    $errores = [];
    validarDatos($pdo, $id, $nombre, false, $errores);
    if (!empty($errores)) {
        responder(false, 'Errores de validación.', ['errores' => $errores], 422);
    }

    $stmt = $pdo->prepare(
        'UPDATE vendedores SET NOMBRE = :nombre, DIRECCION = :direccion, TELEFONO1 = :tel1, TELEFONO2 = :tel2,
             TELEFONO3 = :tel3, VALEINICIA = :valeini, VALETERMINA = :valefin, ESTADO = :estado
         WHERE VENDEDORNO = :id'
    );
    $stmt->execute([
        ':nombre'    => $nombre,
        ':direccion' => $direccion ?: null,
        ':tel1'      => $telefono1 ?: null,
        ':tel2'      => $telefono2 ?: null,
        ':tel3'      => $telefono3 ?: null,
        ':valeini'   => $valeinicia !== '' ? $valeinicia : null,
        ':valefin'   => $valetermina !== '' ? $valetermina : null,
        ':estado'    => $estado,
        ':id'        => $id,
    ]);

    responder(true, 'Vendedor(a) actualizado(a) correctamente.');
}

function eliminarVendedor($pdo, $id)
{
    $stmt = $pdo->prepare('SELECT VENDEDORNO FROM vendedores WHERE VENDEDORNO = :id');
    $stmt->execute([':id' => $id]);
    if (!$stmt->fetch()) {
        responder(false, 'Vendedor no encontrado.', null, 404);
    }

    // Si tiene ventas de vale registradas, no se recomienda eliminar (rompería el historial)
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM rventas WHERE VENDENO = :id');
    $stmt->execute([':id' => $id]);
    if ((int) $stmt->fetch()['total'] > 0) {
        responder(false, 'No se puede eliminar: tiene ventas de vale registradas. Puedes marcarlo(a) como "Cancelado" en vez de eliminarlo(a).', null, 409);
    }

    $stmt = $pdo->prepare('DELETE FROM vendedores WHERE VENDEDORNO = :id');
    $stmt->execute([':id' => $id]);

    responder(true, 'Vendedor(a) eliminado(a) correctamente.');
}
