<?php
/**
 * API de Consulta de Vales (histórico de ventas por remisión).
 * Equivalente en PHP + PDO de FCVales.pas.
 *
 * Endpoints:
 *   GET api/consulta_vales.php?action=vendedor&numero=5        -> datos de un vendedor (para autocompletar nombre)
 *   GET api/consulta_vales.php?action=buscar&novale=&novendedor=&fecha=  -> lista de notas (rventas) que coinciden
 *   GET api/consulta_vales.php?action=detalle&ventano=123        -> detalle de artículos de una nota (vía vmodrventas)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json; charset=utf-8');
requerirLoginApi();

$accion = $_GET['action'] ?? '';

switch ($accion) {
    case 'vendedor':
        obtenerVendedor($pdo, $_GET['numero'] ?? '');
        break;
    case 'buscar':
        buscarNotas($pdo);
        break;
    case 'detalle':
        detalleNota($pdo, $_GET['ventano'] ?? '');
        break;
    default:
        responder(false, 'Acción no reconocida.', null, 400);
}

function responder($success, $message = '', $data = null, $code = 200)
{
    http_response_code($code);
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

function obtenerVendedor($pdo, $numero)
{
    if ($numero === '') {
        responder(false, 'Número de vendedor requerido.', null, 400);
    }
    $stmt = $pdo->prepare('SELECT VENDEDORNO, NOMBRE FROM vendedores WHERE VENDEDORNO = :no');
    $stmt->execute([':no' => $numero]);
    $vendedor = $stmt->fetch();

    if (!$vendedor) {
        responder(false, 'No se encontró el número de vendedor.', null, 404);
    }
    responder(true, '', $vendedor);
}

/**
 * Equivalente a los distintos filtros combinados de FCVales
 * (LEValeNoExit, LEVendedorNoExit, BBuscarClick, EFechaCloseUp).
 */
function buscarNotas($pdo)
{
    $novale     = trim($_GET['novale'] ?? '');
    $novendedor = trim($_GET['novendedor'] ?? '');
    $fecha      = trim($_GET['fecha'] ?? '');

    $condiciones = [];
    $params      = [];

    if ($novale !== '') {
        $condiciones[] = 'VALENO = :novale';
        $params[':novale'] = $novale;
    }
    if ($novendedor !== '') {
        $condiciones[] = 'VENDENO = :novendedor';
        $params[':novendedor'] = $novendedor;
    }
    if ($fecha !== '') {
        $condiciones[] = 'FECHAVEN = :fecha';
        $params[':fecha'] = $fecha;
    }

    $where = empty($condiciones) ? '' : 'WHERE ' . implode(' AND ', $condiciones);

    $sql = "SELECT r.VENTANO, r.FECHAVEN, r.IMPORTEVEN, r.VALENO, r.VENDENO, v.NOMBRE AS vendedor_nombre
            FROM rventas r
            LEFT JOIN vendedores v ON v.VENDEDORNO = r.VENDENO
            $where
            ORDER BY r.FECHAVEN DESC, r.VENTANO DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $notas = $stmt->fetchAll();

    $total = 0;
    foreach ($notas as $nota) {
        $total += (float) $nota['IMPORTEVEN'];
    }

    responder(true, '', ['notas' => $notas, 'total' => round($total, 2)]);
}

function detalleNota($pdo, $ventano)
{
    if ($ventano === '') {
        responder(false, 'Número de venta requerido.', null, 400);
    }

    $stmt = $pdo->prepare(
        'SELECT VENTANO, FECHAVEN, IMPORTEVEN, MARCANO, MODELONO, TALLA, CANTIDAD, PRECIOVE, IMPORTEART, PRECIOCO, MODELODES, COLOR, MATERIAL, SUELA, CODBARRA
         FROM vmodrventas WHERE VENTANO = :ventano'
    );
    $stmt->execute([':ventano' => $ventano]);

    responder(true, '', $stmt->fetchAll());
}
