<?php
/**
 * API REST para el CRUD de Corridas (anidado a una Marca).
 * Equivalente en PHP + PDO del CorridaController de Laravel.
 *
 * Endpoints:
 *   GET    api/corridas.php?action=marca&marcano=1            -> datos de la marca (para el encabezado)
 *   GET    api/corridas.php?action=clasificaciones             -> catálogo de clasificaciones
 *   GET    api/corridas.php?marcano=1                          -> listar corridas de esa marca
 *   GET    api/corridas.php?marcano=1&corridano=2               -> obtener una corrida
 *   POST   api/corridas.php  (body JSON: MARCANO, TALLAINI, TALLAFIN, CLASIFICA) -> crear
 *   PUT    api/corridas.php?marcano=1&corridano=2 (body JSON: TALLAINI, TALLAFIN, CLASIFICA) -> actualizar
 *   DELETE api/corridas.php?marcano=1&corridano=2               -> eliminar
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'GET':
        if (isset($_GET['action']) && $_GET['action'] === 'clasificaciones') {
            listarClasificaciones($pdo);
        } elseif (isset($_GET['action']) && $_GET['action'] === 'marca') {
            obtenerMarca($pdo, $_GET['marcano'] ?? null);
        } elseif (isset($_GET['marcano']) && isset($_GET['corridano']) && $_GET['corridano'] !== '') {
            obtenerCorrida($pdo, $_GET['marcano'], $_GET['corridano']);
        } elseif (isset($_GET['marcano']) && $_GET['marcano'] !== '') {
            listarCorridas($pdo, $_GET['marcano']);
        } else {
            responder(false, 'MARCANO es requerido.', null, 400);
        }
        break;

    case 'POST':
        crearCorrida($pdo);
        break;

    case 'PUT':
        if (!isset($_GET['marcano']) || !isset($_GET['corridano'])) {
            responder(false, 'MARCANO y CORRIDANO son requeridos para actualizar.', null, 400);
        }
        actualizarCorrida($pdo, $_GET['marcano'], $_GET['corridano']);
        break;

    case 'DELETE':
        if (!isset($_GET['marcano']) || !isset($_GET['corridano'])) {
            responder(false, 'MARCANO y CORRIDANO son requeridos para eliminar.', null, 400);
        }
        eliminarCorrida($pdo, $_GET['marcano'], $_GET['corridano']);
        break;

    default:
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

function listarClasificaciones($pdo)
{
    $stmt = $pdo->query('SELECT CLASIFINO, CLASIFIDES FROM clasifica ORDER BY CLASIFINO ASC');
    responder(true, '', $stmt->fetchAll());
}

function listarCorridas($pdo, $marcano)
{
    // Verificar que la marca exista, igual que Marca::findOrFail($marcano)
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM marcas WHERE MARCANO = :marcano');
    $stmt->execute([':marcano' => $marcano]);
    if ((int) $stmt->fetch()['total'] === 0) {
        responder(false, 'Marca no encontrada.', null, 404);
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

function obtenerCorrida($pdo, $marcano, $corridano)
{
    $stmt = $pdo->prepare(
        'SELECT MARCANO, CORRIDANO, TALLAINI, TALLAFIN, CLASIFICA
         FROM corridas WHERE MARCANO = :marcano AND CORRIDANO = :corridano'
    );
    $stmt->execute([':marcano' => $marcano, ':corridano' => $corridano]);
    $corrida = $stmt->fetch();

    if (!$corrida) {
        responder(false, 'Corrida no encontrada.', null, 404);
    }

    responder(true, '', $corrida);
}

function validarDatos($pdo, $tallaini, $tallafin, $clasifica, array &$errores)
{
    // Igual que 'TALLAINI' => 'required' + max 999 (smallint / input max="999")
    if ($tallaini === '' || $tallaini === null || !ctype_digit((string) $tallaini)) {
        $errores['TALLAINI'] = 'La talla inicial es obligatoria y debe ser un número entero.';
    } elseif ((int) $tallaini > 999) {
        $errores['TALLAINI'] = 'La talla inicial no debe exceder 999.';
    }

    if ($tallafin === '' || $tallafin === null || !ctype_digit((string) $tallafin)) {
        $errores['TALLAFIN'] = 'La talla final es obligatoria y debe ser un número entero.';
    } elseif ((int) $tallafin > 999) {
        $errores['TALLAFIN'] = 'La talla final no debe exceder 999.';
    }

    if ($clasifica === '' || $clasifica === null) {
        $errores['CLASIFICA'] = 'Debe seleccionar una clasificación.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM clasifica WHERE CLASIFINO = :clasifica');
        $stmt->execute([':clasifica' => $clasifica]);
        if ((int) $stmt->fetch()['total'] === 0) {
            $errores['CLASIFICA'] = 'La clasificación seleccionada no es válida.';
        }
    }
}

function crearCorrida($pdo)
{
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $marcano   = isset($input['MARCANO']) ? trim((string) $input['MARCANO']) : '';
    $tallaini  = $input['TALLAINI'] ?? '';
    $tallafin  = $input['TALLAFIN'] ?? '';
    $clasifica = isset($input['CLASIFICA']) ? trim((string) $input['CLASIFICA']) : '';

    $errores = [];

    if ($marcano === '' || !ctype_digit($marcano)) {
        $errores['MARCANO'] = 'MARCANO inválido.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM marcas WHERE MARCANO = :marcano');
        $stmt->execute([':marcano' => $marcano]);
        if ((int) $stmt->fetch()['total'] === 0) {
            $errores['MARCANO'] = 'La marca no existe.';
        }
    }

    validarDatos($pdo, $tallaini, $tallafin, $clasifica, $errores);

    if (!empty($errores)) {
        responder(false, 'Errores de validación.', ['errores' => $errores], 422);
    }

    // Siguiente CORRIDANO por marca, igual que en el controlador original
    $stmt = $pdo->prepare('SELECT MAX(CORRIDANO) AS maximo FROM corridas WHERE MARCANO = :marcano');
    $stmt->execute([':marcano' => $marcano]);
    $maximo = $stmt->fetch()['maximo'];
    $siguienteCorridano = $maximo ? ((int) $maximo + 1) : 1;

    $stmt = $pdo->prepare(
        'INSERT INTO corridas (MARCANO, CORRIDANO, TALLAINI, TALLAFIN, CLASIFICA, created_at, updated_at)
         VALUES (:marcano, :corridano, :tallaini, :tallafin, :clasifica, NOW(), NOW())'
    );
    $stmt->execute([
        ':marcano'   => $marcano,
        ':corridano' => $siguienteCorridano,
        ':tallaini'  => $tallaini,
        ':tallafin'  => $tallafin,
        ':clasifica' => $clasifica,
    ]);

    responder(true, 'Corrida creada correctamente.');
}

function actualizarCorrida($pdo, $marcano, $corridano)
{
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $tallaini  = $input['TALLAINI'] ?? '';
    $tallafin  = $input['TALLAFIN'] ?? '';
    $clasifica = isset($input['CLASIFICA']) ? trim((string) $input['CLASIFICA']) : '';

    $stmt = $pdo->prepare('SELECT MARCANO FROM corridas WHERE MARCANO = :marcano AND CORRIDANO = :corridano');
    $stmt->execute([':marcano' => $marcano, ':corridano' => $corridano]);
    if (!$stmt->fetch()) {
        responder(false, 'Corrida no encontrada.', null, 404);
    }

    $errores = [];
    validarDatos($pdo, $tallaini, $tallafin, $clasifica, $errores);

    if (!empty($errores)) {
        responder(false, 'Errores de validación.', ['errores' => $errores], 422);
    }

    $stmt = $pdo->prepare(
        'UPDATE corridas SET TALLAINI = :tallaini, TALLAFIN = :tallafin, CLASIFICA = :clasifica, updated_at = NOW()
         WHERE MARCANO = :marcano AND CORRIDANO = :corridano'
    );
    $stmt->execute([
        ':tallaini'  => $tallaini,
        ':tallafin'  => $tallafin,
        ':clasifica' => $clasifica,
        ':marcano'   => $marcano,
        ':corridano' => $corridano,
    ]);

    responder(true, 'Corrida actualizada.');
}

function eliminarCorrida($pdo, $marcano, $corridano)
{
    $stmt = $pdo->prepare('SELECT MARCANO FROM corridas WHERE MARCANO = :marcano AND CORRIDANO = :corridano');
    $stmt->execute([':marcano' => $marcano, ':corridano' => $corridano]);
    if (!$stmt->fetch()) {
        responder(false, 'Corrida no encontrada.', null, 404);
    }

    // Igual que la verificación de $modelosRelacionados en el controlador original
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM modelos WHERE MARCANO = :marcano AND CORRIDANO = :corridano');
    $stmt->execute([':marcano' => $marcano, ':corridano' => $corridano]);
    if ((int) $stmt->fetch()['total'] > 0) {
        responder(false, 'No se puede borrar: existen modelos relacionados.', null, 409);
    }

    $stmt = $pdo->prepare('DELETE FROM corridas WHERE MARCANO = :marcano AND CORRIDANO = :corridano');
    $stmt->execute([':marcano' => $marcano, ':corridano' => $corridano]);

    responder(true, 'Corrida eliminada.');
}
