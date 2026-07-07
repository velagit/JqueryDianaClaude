<?php
/**
 * API REST para la Consulta General de Existencias.
 * Equivalente en PHP + PDO del ConsartController de Laravel.
 *
 * Endpoints:
 *   GET api/consulta_existencias.php?action=marcas                                        -> catálogo de marcas
 *   GET api/consulta_existencias.php?action=corridas&marcano=1                              -> corridas de una marca
 *   GET api/consulta_existencias.php?action=consulta&marcano=1&modelo=&corrida=&page=1       -> resultados de la consulta
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$accion = $_GET['action'] ?? '';

switch ($accion) {
    case 'marcas':
        listarMarcas($pdo);
        break;
    case 'corridas':
        listarCorridas($pdo, $_GET['marcano'] ?? null);
        break;
    case 'consulta':
        consultarExistencias($pdo);
        break;
    default:
        responder(false, 'Acción no reconocida.', null, 400);
}

/* ------------------------------------------------------------------ */
/* Funciones auxiliares                                               */
/* ------------------------------------------------------------------ */

function responder($success, $message = '', $data = null, $code = 200)
{
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data,
    ]);
    exit;
}

function listarMarcas($pdo)
{
    $stmt = $pdo->query('SELECT MARCANO, MARCADES FROM marcas ORDER BY MARCADES ASC');
    responder(true, '', $stmt->fetchAll());
}

function listarCorridas($pdo, $marcano)
{
    if (!$marcano) {
        responder(false, 'MARCANO es requerido.', null, 400);
    }

    $sql = 'SELECT c.MARCANO, c.CORRIDANO, c.TALLAINI, c.TALLAFIN, c.CLASIFICA, cl.CLASIFIDES
            FROM corridas c
            LEFT JOIN clasifica cl ON cl.CLASIFINO = c.CLASIFICA
            WHERE c.MARCANO = :marcano
            ORDER BY c.CORRIDANO ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':marcano' => $marcano]);

    responder(true, '', $stmt->fetchAll());
}

function consultarExistencias($pdo)
{
    $marcano      = $_GET['marcano'] ?? '';
    $modeloFiltro = trim($_GET['modelo'] ?? '');
    $corridaId    = $_GET['corrida'] ?? '';
    $pagina       = max(1, (int) ($_GET['page'] ?? 1));
    $porPagina    = 10; // igual que ->paginate(10) en Laravel
    $offset       = ($pagina - 1) * $porPagina;

    if ($marcano === '') {
        // Sin marca seleccionada: respuesta vacía, igual que la vista inicial en Laravel
        responder(true, '', [
            'marca'       => null,
            'corridas'    => [],
            'sinCorridas' => false,
            'modelos'     => [],
            'existencias' => [],
            'tallas'      => [],
            'total'       => 0,
            'totalPaginas'=> 1,
            'paginaActual'=> 1,
            'noModelos'   => false,
        ]);
    }

    // Verificar que la marca exista
    $stmt = $pdo->prepare('SELECT MARCANO, MARCADES FROM marcas WHERE MARCANO = :marcano');
    $stmt->execute([':marcano' => $marcano]);
    $marca = $stmt->fetch();
    if (!$marca) {
        responder(false, 'Marca no encontrada.', null, 404);
    }

    // Corridas de la marca (con clasificación)
    $stmt = $pdo->prepare(
        'SELECT c.MARCANO, c.CORRIDANO, c.TALLAINI, c.TALLAFIN, c.CLASIFICA, cl.CLASIFIDES
         FROM corridas c LEFT JOIN clasifica cl ON cl.CLASIFINO = c.CLASIFICA
         WHERE c.MARCANO = :marcano ORDER BY c.CORRIDANO ASC'
    );
    $stmt->execute([':marcano' => $marcano]);
    $corridas = $stmt->fetchAll();

    if (empty($corridas)) {
        responder(true, '', [
            'marca'       => $marca,
            'corridas'    => [],
            'sinCorridas' => true,
            'modelos'     => [],
            'existencias' => [],
            'tallas'      => [],
            'total'       => 0,
            'totalPaginas'=> 1,
            'paginaActual'=> 1,
            'noModelos'   => false,
        ]);
    }

    // Construir filtro de modelos
    $where  = 'WHERE MARCANO = :marcano';
    $params = [':marcano' => $marcano];

    if ($modeloFiltro !== '') {
        $where .= ' AND MODELODES LIKE :modelo';
        $params[':modelo'] = "%$modeloFiltro%";
    }
    if ($corridaId !== '') {
        $where .= ' AND CORRIDANO = :corrida';
        $params[':corrida'] = $corridaId;
    }

    $stmtTotal = $pdo->prepare("SELECT COUNT(*) AS total FROM modelos $where");
    $stmtTotal->execute($params);
    $total = (int) $stmtTotal->fetch()['total'];

    $sql = "SELECT MARCANO, MODELONO, MODELODES, MATERIAL, COLOR, SUELA, CORRIDANO
            FROM modelos $where ORDER BY MODELONO ASC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $modelos = $stmt->fetchAll();

    // Determinar rango de tallas
    $tallas = [];
    if ($corridaId !== '') {
        $corridaSeleccionada = null;
        foreach ($corridas as $c) {
            if ((string) $c['CORRIDANO'] === (string) $corridaId) {
                $corridaSeleccionada = $c;
                break;
            }
        }
        if ($corridaSeleccionada) {
            for ($t = (int) $corridaSeleccionada['TALLAINI']; $t <= (int) $corridaSeleccionada['TALLAFIN']; $t += 5) {
                $tallas[] = $t;
            }
        }
    } else {
        $tallaIni = min(array_column($corridas, 'TALLAINI'));
        $tallaFin = max(array_column($corridas, 'TALLAFIN'));
        for ($t = (int) $tallaIni; $t <= (int) $tallaFin; $t += 5) {
            $tallas[] = $t;
        }
    }

    // Construir la matriz de existencias por modelo x talla
    $existencias = [];
    foreach ($modelos as $modelo) {
        $fila = [
            'MODELODES' => $modelo['MODELODES'],
            'MATERIAL'  => $modelo['MATERIAL'],
            'COLOR'     => $modelo['COLOR'],
            'SUELA'     => $modelo['SUELA'],
            'tallas'    => [],
        ];

        foreach ($tallas as $talla) {
            $stmt = $pdo->prepare(
                'SELECT EXISTENCIA FROM articulos WHERE MARCANO = :marcano AND MODELONO = :modelono AND TALLA = :talla'
            );
            $stmt->execute([
                ':marcano'  => $marcano,
                ':modelono' => $modelo['MODELONO'],
                ':talla'    => $talla,
            ]);
            $articulo = $stmt->fetch();
            $fila['tallas'][$talla] = $articulo ? (int) $articulo['EXISTENCIA'] : 0;
        }

        $existencias[] = $fila;
    }

    $noModelos = ($corridaId !== '' && empty($modelos));

    responder(true, '', [
        'marca'        => $marca,
        'corridas'     => $corridas,
        'sinCorridas'  => false,
        'modelos'      => $modelos,
        'existencias'  => $existencias,
        'tallas'       => $tallas,
        'total'        => $total,
        'totalPaginas' => (int) ceil($total / $porPagina) ?: 1,
        'paginaActual' => $pagina,
        'noModelos'    => $noModelos,
    ]);
}
