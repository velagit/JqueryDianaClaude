<?php
/**
 * API para el módulo de Inventario.
 * Equivalente en PHP + PDO del InventarioController de Laravel.
 * Mantiene el mismo formato de respuesta JSON que las rutas originales.
 *
 * Endpoints:
 *   GET api/inventario.php?action=marcas                                     -> catálogo de marcas
 *   GET api/inventario.php?action=modelos&marcano=1                           -> modelos de una marca (array plano)
 *   GET api/inventario.php?action=corridas&marcano=1                          -> corridas de una marca (con "clasifica" anidado)
 *   GET api/inventario.php?action=consulta&marcano=1&modelodes=&corridano=     -> { tallas, datos } o { error }
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$accion = $_GET['action'] ?? '';

switch ($accion) {
    case 'marcas':
        listarMarcas($pdo);
        break;
    case 'modelos':
        modelosPorMarca($pdo);
        break;
    case 'corridas':
        corridasPorMarca($pdo);
        break;
    case 'consulta':
        consulta($pdo);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no reconocida.']);
        exit;
}

/* ------------------------------------------------------------------ */
/* Funciones auxiliares                                               */
/* ------------------------------------------------------------------ */

function listarMarcas($pdo)
{
    $stmt = $pdo->query('SELECT MARCANO, MARCADES FROM marcas ORDER BY MARCADES ASC');
    echo json_encode($stmt->fetchAll());
    exit;
}

function modelosPorMarca($pdo)
{
    $marcano = $_GET['marcano'] ?? '';

    $stmt = $pdo->prepare(
        'SELECT MARCANO, MODELONO, MODELODES, COLOR, MATERIAL, SUELA, PRECIOCO, PRECIOVE, SITUACION, CORRIDANO, fecha
         FROM modelos WHERE MARCANO = :marcano ORDER BY MODELONO ASC'
    );
    $stmt->execute([':marcano' => $marcano]);

    echo json_encode($stmt->fetchAll());
    exit;
}

function corridasPorMarca($pdo)
{
    $marcano = $_GET['marcano'] ?? '';

    $stmt = $pdo->prepare(
        'SELECT c.MARCANO, c.CORRIDANO, c.TALLAINI, c.TALLAFIN, c.CLASIFICA, cl.CLASIFINO, cl.CLASIFIDES
         FROM corridas c LEFT JOIN clasifica cl ON cl.CLASIFINO = c.CLASIFICA
         WHERE c.MARCANO = :marcano ORDER BY c.CORRIDANO ASC'
    );
    $stmt->execute([':marcano' => $marcano]);
    $filas = $stmt->fetchAll();

    // Anidar "clasifica" como objeto, igual que Eloquent con ->with('clasifica')
    $corridas = array_map(function ($fila) {
        return [
            'MARCANO'   => $fila['MARCANO'],
            'CORRIDANO' => $fila['CORRIDANO'],
            'TALLAINI'  => $fila['TALLAINI'],
            'TALLAFIN'  => $fila['TALLAFIN'],
            'CLASIFICA' => $fila['CLASIFICA'],
            'clasifica' => $fila['CLASIFINO'] !== null ? [
                'CLASIFINO'  => $fila['CLASIFINO'],
                'CLASIFIDES' => $fila['CLASIFIDES'],
            ] : null,
        ];
    }, $filas);

    echo json_encode($corridas);
    exit;
}

function consulta($pdo)
{
    $marcano   = $_GET['marcano'] ?? '';
    $modelodes = trim($_GET['modelodes'] ?? '');
    $corridano = $_GET['corridano'] ?? '';

    // Modelos de la marca, con filtros opcionales
    $where  = 'WHERE MARCANO = :marcano';
    $params = [':marcano' => $marcano];

    if ($modelodes !== '') {
        $where .= ' AND MODELODES LIKE :modelodes';
        $params[':modelodes'] = "%$modelodes%";
    }
    if ($corridano !== '') {
        $where .= ' AND CORRIDANO = :corridano';
        $params[':corridano'] = $corridano;
    }

    $stmt = $pdo->prepare("SELECT MARCANO, MODELONO, MODELODES, MATERIAL, COLOR, SUELA FROM modelos $where ORDER BY MODELONO ASC");
    $stmt->execute($params);
    $modelos = $stmt->fetchAll();

    // Corridas de la marca (filtradas por corridano si se especificó)
    $whereCorridas  = 'WHERE MARCANO = :marcano';
    $paramsCorridas = [':marcano' => $marcano];
    if ($corridano !== '') {
        $whereCorridas .= ' AND CORRIDANO = :corridano';
        $paramsCorridas[':corridano'] = $corridano;
    }
    $stmt = $pdo->prepare("SELECT TALLAINI, TALLAFIN FROM corridas $whereCorridas");
    $stmt->execute($paramsCorridas);
    $corridas = $stmt->fetchAll();

    if (empty($corridas)) {
        http_response_code(404);
        echo json_encode(['error' => 'No hay corridas disponibles para esta marca.']);
        exit;
    }

    $tallaInicio = min(array_column($corridas, 'TALLAINI'));
    $tallaFin    = max(array_column($corridas, 'TALLAFIN'));

    $tallas = [];
    for ($t = (int) $tallaInicio; $t <= (int) $tallaFin; $t += 5) {
        $tallas[] = $t;
    }

    $datos = [];
    foreach ($modelos as $modelo) {
        $fila = [
            'MODELODES' => $modelo['MODELODES'],
            'MATERIAL'  => $modelo['MATERIAL'],
            'COLOR'     => $modelo['COLOR'],
            'SUELA'     => $modelo['SUELA'],
        ];

        foreach ($tallas as $talla) {
            $stmtEx = $pdo->prepare(
                'SELECT EXISTENCIA FROM articulos WHERE MARCANO = :marcano AND MODELONO = :modelono AND TALLA = :talla'
            );
            $stmtEx->execute([
                ':marcano'  => $modelo['MARCANO'],
                ':modelono' => $modelo['MODELONO'],
                ':talla'    => $talla,
            ]);
            $existencia = $stmtEx->fetchColumn();
            $fila[$talla] = $existencia !== false ? (int) $existencia : 0;
        }

        $datos[] = $fila;
    }

    echo json_encode([
        'tallas' => $tallas,
        'datos'  => $datos,
    ]);
    exit;
}
