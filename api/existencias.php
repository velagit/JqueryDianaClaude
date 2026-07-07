<?php
/**
 * API REST para la Captura de Existencias (anidado a una Marca).
 * Equivalente en PHP + PDO del ExistenciaController de Laravel.
 *
 * Endpoints:
 *   GET  api/existencias.php?action=marca&marcano=1                    -> datos de la marca
 *   GET  api/existencias.php?action=tallas&marcano=1                    -> rango de tallas (según corridas) + bandera sinCorridas
 *   GET  api/existencias.php?action=modelos&marcano=1&modelo=filtro     -> modelos de la marca (filtrables por MODELODES)
 *   POST api/existencias.php?action=store  (body JSON)                  -> guardar existencias capturadas
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['action'] ?? '';

if ($metodo === 'GET') {
    switch ($accion) {
        case 'marca':
            obtenerMarca($pdo, $_GET['marcano'] ?? null);
            break;
        case 'tallas':
            obtenerTallas($pdo, $_GET['marcano'] ?? null);
            break;
        case 'modelos':
            listarModelos($pdo, $_GET['marcano'] ?? null, $_GET['modelo'] ?? '');
            break;
        default:
            responder(false, 'Acción no reconocida.', null, 400);
    }
} elseif ($metodo === 'POST' && $accion === 'store') {
    guardarExistencias($pdo);
} else {
    responder(false, 'Método no permitido.', null, 405);
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

function obtenerMarca($pdo, $marcano)
{
    if (!$marcano) {
        responder(false, 'MARCANO es requerido.', null, 400);
    }
    $stmt = $pdo->prepare('SELECT MARCANO, MARCADES FROM marcas WHERE MARCANO = :marcano');
    $stmt->execute([':marcano' => $marcano]);
    $marca = $stmt->fetch();

    if (!$marca) {
        responder(false, 'Marca no encontrada.', null, 404);
    }

    responder(true, '', $marca);
}

function obtenerTallas($pdo, $marcano)
{
    if (!$marcano) {
        responder(false, 'MARCANO es requerido.', null, 400);
    }

    $stmt = $pdo->prepare('SELECT MIN(TALLAINI) AS tallaIni, MAX(TALLAFIN) AS tallaFin, COUNT(*) AS total FROM corridas WHERE MARCANO = :marcano');
    $stmt->execute([':marcano' => $marcano]);
    $fila = $stmt->fetch();

    if ((int) $fila['total'] === 0) {
        responder(true, '', ['sinCorridas' => true, 'tallas' => []]);
    }

    $tallas = [];
    for ($t = (int) $fila['tallaIni']; $t <= (int) $fila['tallaFin']; $t += 5) {
        $tallas[] = $t;
    }

    responder(true, '', ['sinCorridas' => false, 'tallas' => $tallas]);
}

function listarModelos($pdo, $marcano, $filtroModelo)
{
    if (!$marcano) {
        responder(false, 'MARCANO es requerido.', null, 400);
    }

    // Verificar que la marca exista
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM marcas WHERE MARCANO = :marcano');
    $stmt->execute([':marcano' => $marcano]);
    if ((int) $stmt->fetch()['total'] === 0) {
        responder(false, 'Marca no encontrada.', null, 404);
    }

    $where  = 'WHERE MARCANO = :marcano';
    $params = [':marcano' => $marcano];

    // Filtro por MODELODES, igual que str_contains(strtolower(...)) del controlador original
    if (trim($filtroModelo) !== '') {
        $where .= ' AND LOWER(MODELODES) LIKE LOWER(:filtro)';
        $params[':filtro'] = '%' . trim($filtroModelo) . '%';
    }

    $sql = "SELECT MARCANO, MODELONO, MODELODES, COLOR, MATERIAL, SUELA, PRECIOCO, fecha
            FROM modelos $where ORDER BY MODELONO ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    responder(true, '', $stmt->fetchAll());
}

function guardarExistencias($pdo)
{
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $marcano       = isset($input['MARCANO']) ? trim((string) $input['MARCANO']) : '';
    $existencias   = $input['existencias'] ?? [];
    $precioGeneral = $input['precio_general'] ?? null;
    $fechaGeneral  = $input['fecha_general'] ?? null;

    if ($marcano === '' || !ctype_digit($marcano)) {
        responder(false, 'MARCANO inválido.', null, 400);
    }

    if (empty($existencias) || !is_array($existencias)) {
        responder(false, 'No se recibieron datos de existencias.', null, 400);
    }

    // Igual que la validación del lado del cliente: al menos una existencia > 0
    $hayExistenciaPositiva = false;
    foreach ($existencias as $tallas) {
        foreach ($tallas as $valor) {
            if ((int) $valor > 0) {
                $hayExistenciaPositiva = true;
                break 2;
            }
        }
    }
    if (!$hayExistenciaPositiva) {
        responder(false, 'Debes capturar al menos una existencia mayor a cero.', null, 422);
    }

    $precioGeneral = ($precioGeneral === '' || $precioGeneral === null) ? null : $precioGeneral;
    $fechaGeneral  = ($fechaGeneral === '' || $fechaGeneral === null) ? null : $fechaGeneral;

    $pdo->beginTransaction();

    try {
        foreach ($existencias as $modelono => $tallas) {
            foreach ($tallas as $talla => $existencia) {
                $existencia = (int) $existencia;
                if ($existencia > 0) {
                    // Buscar registro existente
                    $stmt = $pdo->prepare(
                        'SELECT EXISTENCIA FROM articulos WHERE MARCANO = :marcano AND MODELONO = :modelono AND TALLA = :talla'
                    );
                    $stmt->execute([':marcano' => $marcano, ':modelono' => $modelono, ':talla' => $talla]);
                    $registro = $stmt->fetch();

                    if ($registro) {
                        $nuevaExistencia = (int) $registro['EXISTENCIA'] + $existencia;
                        $stmt = $pdo->prepare(
                            'UPDATE articulos SET EXISTENCIA = :existencia WHERE MARCANO = :marcano AND MODELONO = :modelono AND TALLA = :talla'
                        );
                        $stmt->execute([
                            ':existencia' => $nuevaExistencia,
                            ':marcano'    => $marcano,
                            ':modelono'   => $modelono,
                            ':talla'      => $talla,
                        ]);
                    } else {
                        $codBarra = str_pad((string) $marcano, 3, '0', STR_PAD_LEFT)
                            . str_pad((string) $modelono, 4, '0', STR_PAD_LEFT)
                            . str_pad((string) $talla, 3, '0', STR_PAD_LEFT);

                        $stmt = $pdo->prepare(
                            'INSERT INTO articulos (MARCANO, MODELONO, TALLA, EXISTENCIA, CODBARRA)
                             VALUES (:marcano, :modelono, :talla, :existencia, :codbarra)'
                        );
                        $stmt->execute([
                            ':marcano'    => $marcano,
                            ':modelono'   => $modelono,
                            ':talla'      => $talla,
                            ':existencia' => $existencia,
                            ':codbarra'   => $codBarra,
                        ]);
                    }
                }
            }

            // Actualizar precio y fecha en el modelo (precio/fecha generales, igual que el comportamiento actual)
            $stmt = $pdo->prepare(
                'UPDATE modelos SET PRECIOCO = :precio, fecha = :fecha WHERE MARCANO = :marcano AND MODELONO = :modelono'
            );
            $stmt->execute([
                ':precio'   => $precioGeneral,
                ':fecha'    => $fechaGeneral,
                ':marcano'  => $marcano,
                ':modelono' => $modelono,
            ]);
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        responder(false, 'Error al guardar las existencias: ' . $e->getMessage(), null, 500);
    }

    responder(true, 'Existencias guardadas correctamente.');
}
