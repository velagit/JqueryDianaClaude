<?php
/**
 * API REST para el CRUD de Vales Bloqueados.
 * Equivalente en PHP + PDO de FBloqueaVale.pas.
 * Llave compuesta: (novendedor, novale) — igual que el original.
 *
 * Endpoints:
 *   GET    api/bloqueados.php                                  -> listar (con nombre de vendedor)
 *   POST   api/bloqueados.php  (body JSON)                       -> crear bloqueo
 *   PUT    api/bloqueados.php?novendedor=1&novale=5 (body JSON)   -> actualizar (fecha/motivo)
 *   DELETE api/bloqueados.php?novendedor=1&novale=5               -> desbloquear (eliminar)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json; charset=utf-8');
requerirLoginApi();

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'GET':
        listarBloqueados($pdo);
        break;

    case 'POST':
        crearBloqueo($pdo);
        break;

    case 'PUT':
        if (!isset($_GET['novendedor']) || !isset($_GET['novale'])) {
            responder(false, 'Número de vendedor y número de vale son requeridos.', null, 400);
        }
        actualizarBloqueo($pdo, $_GET['novendedor'], $_GET['novale']);
        break;

    case 'DELETE':
        if (!isset($_GET['novendedor']) || !isset($_GET['novale'])) {
            responder(false, 'Número de vendedor y número de vale son requeridos.', null, 400);
        }
        eliminarBloqueo($pdo, $_GET['novendedor'], $_GET['novale']);
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

function listarBloqueados($pdo)
{
    $sql = 'SELECT b.novendedor, b.novale, b.fecha, b.motivo, v.NOMBRE AS vendedor_nombre
            FROM bloqueados b
            LEFT JOIN vendedores v ON v.VENDEDORNO = b.novendedor
            ORDER BY b.fecha DESC, b.novendedor ASC';
    $stmt = $pdo->query($sql);
    responder(true, '', $stmt->fetchAll());
}

function crearBloqueo($pdo)
{
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    // Igual que BAceptarClick del original: si vienen vacíos, se guardan como 0
    $novendedor = isset($input['novendedor']) && $input['novendedor'] !== '' ? (int) $input['novendedor'] : 0;
    $novale     = isset($input['novale']) && $input['novale'] !== '' ? (int) $input['novale'] : 0;
    $fecha      = $input['fecha'] ?? date('Y-m-d');
    $motivo     = trim((string) ($input['motivo'] ?? ''));

    $errores = [];
    if ($motivo === '') {
        $errores['motivo'] = 'El motivo es obligatorio.';
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM bloqueados WHERE novendedor = :vendedor AND novale = :vale');
    $stmt->execute([':vendedor' => $novendedor, ':vale' => $novale]);
    if ((int) $stmt->fetch()['total'] > 0) {
        $errores['novale'] = 'Ya existe un bloqueo para ese vendedor y número de vale.';
    }

    if (!empty($errores)) {
        responder(false, 'Errores de validación.', ['errores' => $errores], 422);
    }

    $stmt = $pdo->prepare('INSERT INTO bloqueados (novendedor, novale, fecha, motivo) VALUES (:vendedor, :vale, :fecha, :motivo)');
    $stmt->execute([':vendedor' => $novendedor, ':vale' => $novale, ':fecha' => $fecha, ':motivo' => $motivo]);

    responder(true, 'Vale bloqueado correctamente.');
}

function actualizarBloqueo($pdo, $novendedor, $novale)
{
    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $fecha  = $input['fecha'] ?? date('Y-m-d');
    $motivo = trim((string) ($input['motivo'] ?? ''));

    $stmt = $pdo->prepare('SELECT novendedor FROM bloqueados WHERE novendedor = :vendedor AND novale = :vale');
    $stmt->execute([':vendedor' => $novendedor, ':vale' => $novale]);
    if (!$stmt->fetch()) {
        responder(false, 'Bloqueo no encontrado.', null, 404);
    }

    if ($motivo === '') {
        responder(false, 'Errores de validación.', ['errores' => ['motivo' => 'El motivo es obligatorio.']], 422);
    }

    $stmt = $pdo->prepare('UPDATE bloqueados SET fecha = :fecha, motivo = :motivo WHERE novendedor = :vendedor AND novale = :vale');
    $stmt->execute([':fecha' => $fecha, ':motivo' => $motivo, ':vendedor' => $novendedor, ':vale' => $novale]);

    responder(true, 'Bloqueo actualizado correctamente.');
}

function eliminarBloqueo($pdo, $novendedor, $novale)
{
    $stmt = $pdo->prepare('SELECT novendedor FROM bloqueados WHERE novendedor = :vendedor AND novale = :vale');
    $stmt->execute([':vendedor' => $novendedor, ':vale' => $novale]);
    if (!$stmt->fetch()) {
        responder(false, 'Bloqueo no encontrado.', null, 404);
    }

    $stmt = $pdo->prepare('DELETE FROM bloqueados WHERE novendedor = :vendedor AND novale = :vale');
    $stmt->execute([':vendedor' => $novendedor, ':vale' => $novale]);

    responder(true, 'Vale desbloqueado correctamente.');
}
