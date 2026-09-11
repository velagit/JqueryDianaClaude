<?php
/**
 * API del módulo de Pedidos (orden de compra a proveedor).
 * Equivalente en PHP + PDO de FM_Pedidos.pas.
 *
 * Reutiliza las tablas de staging `pcabecera` y `pedidos` que ya existían
 * en tu base de datos, con el mismo esquema T_01..T_20 por talla, para
 * no romper compatibilidad si sigues usando el reporte original en algún
 * otro lado.
 *
 * Endpoints:
 *   GET  api/pedidos.php?action=grid&marcano=&corridano=&modelo=   -> tallas + modelos + existencias
 *   GET  api/pedidos.php?action=resumen                             -> lo que ya está acumulado (pcabecera+pedidos)
 *   POST api/pedidos.php?action=agregar   (body JSON)                -> agrega un pedido de marca/corrida al acumulado
 *   POST api/pedidos.php?action=imprimir                             -> arma el reporte final y vacía el acumulado
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json; charset=utf-8');
requerirLoginApi();

$metodo = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['action'] ?? '';

if ($metodo === 'GET' && $accion === 'grid') {
    obtenerGrid($pdo);
} elseif ($metodo === 'GET' && $accion === 'resumen') {
    obtenerResumen($pdo);
} elseif ($metodo === 'POST' && $accion === 'agregar') {
    agregarPedido($pdo);
} elseif ($metodo === 'POST' && $accion === 'imprimir') {
    imprimirYVaciar($pdo);
} else {
    responder(false, 'Acción no reconocida.', null, 400);
}

/* ------------------------------------------------------------------ */

function responder($success, $message = '', $data = null, $code = 200)
{
    http_response_code($code);
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

/**
 * Equivalente a Llena_Grid(): tallas de la corrida elegida + modelos de
 * esa marca/corrida (filtrables por descripción) + existencia actual
 * por talla (de referencia, tomada de `articulos`).
 */
function obtenerGrid($pdo)
{
    $marcano   = $_GET['marcano'] ?? '';
    $corridano = $_GET['corridano'] ?? '';
    $modelo    = trim($_GET['modelo'] ?? '');

    if ($marcano === '' || $corridano === '') {
        responder(false, 'Marca y corrida son requeridas.', null, 400);
    }

    $stmt = $pdo->prepare('SELECT TALLAINI, TALLAFIN FROM corridas WHERE MARCANO = :marcano AND CORRIDANO = :corridano');
    $stmt->execute([':marcano' => $marcano, ':corridano' => $corridano]);
    $corrida = $stmt->fetch();
    if (!$corrida) {
        responder(false, 'Corrida no encontrada.', null, 404);
    }

    $tallas = [];
    for ($t = (int) $corrida['TALLAINI']; $t <= (int) $corrida['TALLAFIN']; $t += 5) {
        $tallas[] = $t;
    }
    // El original limita a 20 columnas de talla (T_01..T_20)
    $tallas = array_slice($tallas, 0, 20);

    $where  = 'WHERE MARCANO = :marcano AND CORRIDANO = :corridano';
    $params = [':marcano' => $marcano, ':corridano' => $corridano];
    if ($modelo !== '') {
        $where .= ' AND MODELODES LIKE :modelo';
        $params[':modelo'] = "%$modelo%";
    }

    $stmt = $pdo->prepare("SELECT MODELONO, MODELODES, COLOR, MATERIAL, SUELA, fecha FROM modelos $where ORDER BY MODELODES ASC");
    $stmt->execute($params);
    $modelos = $stmt->fetchAll();

    // Existencia actual por modelo/talla (solo de referencia visual)
    $stmt = $pdo->prepare('SELECT MODELONO, TALLA, EXISTENCIA FROM articulos WHERE MARCANO = :marcano');
    $stmt->execute([':marcano' => $marcano]);
    $existenciasPorModelo = [];
    foreach ($stmt->fetchAll() as $fila) {
        $existenciasPorModelo[$fila['MODELONO']][$fila['TALLA']] = (int) $fila['EXISTENCIA'];
    }

    $resultado = [];
    foreach ($modelos as $modelo) {
        $existencias = [];
        foreach ($tallas as $talla) {
            $existencias[$talla] = $existenciasPorModelo[$modelo['MODELONO']][$talla] ?? 0;
        }
        $resultado[] = [
            'modelono'   => (int) $modelo['MODELONO'],
            'modelodes'  => $modelo['MODELODES'],
            'color'      => $modelo['COLOR'],
            'material'   => $modelo['MATERIAL'],
            'suela'      => $modelo['SUELA'],
            'fecha'      => $modelo['fecha'],
            'existencias'=> $existencias,
        ];
    }

    responder(true, '', ['tallas' => $tallas, 'modelos' => $resultado]);
}

/**
 * Equivalente a BBAceptarClick(): agrega/actualiza la cabecera de esa
 * marca+corrida en `pcabecera`, e inserta en `pedidos` un renglón por
 * cada modelo con cantidad total > 0.
 */
function agregarPedido($pdo)
{
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $marcano    = (int) ($input['marcano'] ?? 0);
    $corridano  = (int) ($input['corridano'] ?? 0);
    $marcades   = trim((string) ($input['marcades'] ?? ''));
    $corridades = trim((string) ($input['corridades'] ?? ''));
    $tallas     = $input['tallas'] ?? [];
    $items      = $input['items'] ?? [];

    if ($marcano <= 0 || $corridano <= 0) {
        responder(false, 'Marca y corrida son requeridas.', null, 400);
    }
    if (empty($tallas) || !is_array($tallas)) {
        responder(false, 'No se recibió el rango de tallas.', null, 400);
    }
    if (empty($items) || !is_array($items)) {
        responder(false, 'No hay artículos para agregar.', null, 400);
    }

    $pdo->beginTransaction();
    try {
        // 1) Cabecera: solo se crea una vez por marca+corrida (igual que el original)
        $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM pcabecera WHERE MARCANO = :marcano AND CORRIDANO = :corridano');
        $stmt->execute([':marcano' => $marcano, ':corridano' => $corridano]);
        if ((int) $stmt->fetch()['total'] === 0) {
            $columnas = ['MARCANO', 'CORRIDANO', 'MARCADES', 'CORRIDADES'];
            $valores  = [':marcano' => $marcano, ':corridano' => $corridano, ':marcades' => $marcades, ':corridades' => $corridades];

            foreach ($tallas as $i => $talla) {
                $col = 'T_' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
                $tallaStr = (string) $talla;
                // Igual que el original: si el 3er carácter es '5', se guarda '---'
                $etiqueta = (isset($tallaStr[2]) && $tallaStr[2] === '5') ? '---' : (substr($tallaStr, 0, 2) . ' ');
                $columnas[] = $col;
                $valores[':' . strtolower($col)] = $etiqueta;
            }

            $placeholders = array_map(fn($c) => ':' . strtolower($c), $columnas);
            $sql = 'INSERT INTO pcabecera (' . implode(',', $columnas) . ') VALUES (' . implode(',', $placeholders) . ')';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($valores);
        }

        // 2) Detalle: un renglón por modelo con cantidad total > 0
        $stmtIns = $pdo->prepare(
            'INSERT INTO pedidos (MARCANO, CORRIDANO, MODELODES, COLOR, PIEL, SUELA, TOTMOD, ' .
            implode(',', array_map(fn($i) => 'T_' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT), array_keys($tallas))) .
            ') VALUES (:marcano, :corridano, :modelodes, :color, :piel, :suela, :totmod, ' .
            implode(',', array_map(fn($i) => ':t' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT), array_keys($tallas))) .
            ')'
        );

        $agregados = 0;
        foreach ($items as $item) {
            $cantidades = $item['cantidades'] ?? [];
            $total = 0;
            $valoresTallas = [];
            foreach ($tallas as $i => $talla) {
                $cantidad = (int) ($cantidades[$talla] ?? 0);
                $total += $cantidad;
                $valoresTallas[':t' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)] = $cantidad > 0 ? $cantidad : null;
            }

            if ($total > 0) {
                $stmtIns->execute(array_merge([
                    ':marcano'    => $marcano,
                    ':corridano'  => $corridano,
                    ':modelodes'  => $item['modelodes'] ?? '',
                    ':color'      => $item['color'] ?? '',
                    ':piel'       => $item['material'] ?? '',
                    ':suela'      => $item['suela'] ?? '',
                    ':totmod'     => $total,
                ], $valoresTallas));
                $agregados++;
            }
        }

        $pdo->commit();
        responder(true, "Se agregaron $agregados modelo(s) al pedido acumulado.", ['agregados' => $agregados]);
    } catch (Exception $e) {
        $pdo->rollBack();
        responder(false, 'Error al agregar el pedido: ' . $e->getMessage(), null, 500);
    }
}

/**
 * Muestra lo que ya está acumulado en pcabecera/pedidos (para que el
 * usuario vea qué se ha ido agregando antes de imprimir).
 */
function obtenerResumen($pdo)
{
    $cabeceras = $pdo->query('SELECT * FROM pcabecera ORDER BY MARCANO, CORRIDANO')->fetchAll();
    $detalles  = $pdo->query('SELECT * FROM pedidos ORDER BY MARCANO, CORRIDANO, MODELODES')->fetchAll();

    responder(true, '', ['cabeceras' => $cabeceras, 'detalles' => $detalles]);
}

/**
 * Equivalente a BImprimirClick(): arma los datos completos para el
 * reporte "Pedidos" y, una vez armados, vacía pcabecera/pedidos —
 * exactamente igual que el original.
 */
function imprimirYVaciar($pdo)
{
    $cabeceras = $pdo->query('SELECT * FROM pcabecera ORDER BY MARCANO, CORRIDANO')->fetchAll();
    $detalles  = $pdo->query('SELECT * FROM pedidos ORDER BY MARCANO, CORRIDANO, MODELODES')->fetchAll();

    if (empty($cabeceras)) {
        responder(false, 'No hay ningún pedido acumulado para imprimir.', null, 404);
    }

    $pdo->exec('DELETE FROM pcabecera');
    $pdo->exec('DELETE FROM pedidos');

    responder(true, 'Pedido impreso y acumulado vaciado.', ['cabeceras' => $cabeceras, 'detalles' => $detalles]);
}
