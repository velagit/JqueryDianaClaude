<?php
/**
 * API del Corte de Caja Diario.
 * Equivalente en PHP + PDO de CorteD.pas.
 *
 * Endpoints:
 *   GET  api/corte.php?action=estado                -> totales acumulados del día + datos de empresa
 *   POST api/corte.php?action=confirmar               -> imprime/confirma el corte y resetea los contadores
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json; charset=utf-8');
requerirLoginApi();

$accion = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $accion === 'estado') {
    estadoDelDia($pdo);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'confirmar') {
    confirmarCorte($pdo);
} else {
    responder(false, 'Acción no reconocida.', null, 400);
}

function responder($success, $message = '', $data = null, $code = 200)
{
    http_response_code($code);
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

function estadoDelDia($pdo)
{
    $stmt = $pdo->query('SELECT CODIGONO, RSOCIAL, DIRECCION, CIUDAD, ESTADO, RFC, RECIBOSDIA, SUBTOTAL, VENTADIA, IVADIA FROM empresa LIMIT 1');
    $empresa = $stmt->fetch();

    if (!$empresa) {
        responder(false, 'No se encontró el registro de la empresa.', null, 404);
    }

    responder(true, '', [
        'empresa' => $empresa,
        'fecha'   => date('Y-m-d'),
    ]);
}

/**
 * Equivalente a BImprimirClick(): igual que el original, esto asume que
 * el ticket/corte ya se imprimió (el frontend abre la ventana de
 * impresión antes de llamar a este endpoint) y aquí solo se resetean
 * los contadores diarios en `empresa`, exactamente igual que el UPDATE
 * original.
 */
function confirmarCorte($pdo)
{
    $stmt = $pdo->query('SELECT CODIGONO, RSOCIAL, RECIBOSDIA, SUBTOTAL, VENTADIA, IVADIA FROM empresa LIMIT 1');
    $empresa = $stmt->fetch();

    if (!$empresa) {
        responder(false, 'No se encontró el registro de la empresa.', null, 404);
    }

    // Se guarda una copia de los totales antes de reiniciarlos, para regresarlos al ticket de corte
    $totalesCorte = [
        'recibosdia' => (int) $empresa['RECIBOSDIA'],
        'subtotal'   => (float) $empresa['SUBTOTAL'],
        'ventadia'   => (float) $empresa['VENTADIA'],
        'ivadia'     => (float) $empresa['IVADIA'],
    ];

    $stmt = $pdo->prepare(
        'UPDATE empresa SET RECIBOSDIA = 0, SUBTOTAL = 0, VENTADIA = 0, IVADIA = 0 WHERE CODIGONO = :codigono'
    );
    $stmt->execute([':codigono' => $empresa['CODIGONO']]);

    responder(true, 'Corte de caja realizado. Los contadores del día se reiniciaron a cero.', [
        'totalesCorte' => $totalesCorte,
        'fecha'        => date('Y-m-d'),
        'rsocial'      => $empresa['RSOCIAL'],
    ]);
}
