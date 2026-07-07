<?php
/**
 * API REST para el CRUD de Modelos (anidado a una Marca).
 * Equivalente en PHP + PDO del ModeloController de Laravel.
 *
 * Endpoints:
 *   GET    api/modelos.php?action=marca&marcano=1                 -> datos de la marca
 *   GET    api/modelos.php?action=corridas&marcano=1               -> corridas de la marca (para el combo)
 *   GET    api/modelos.php?marcano=1&search=&page=1                -> listar modelos (paginado, 7 por página)
 *   GET    api/modelos.php?marcano=1&modelono=2                     -> obtener un modelo
 *   POST   api/modelos.php  (body JSON con los campos del modelo)   -> crear
 *   PUT    api/modelos.php?marcano=1&modelono=2 (body JSON)         -> actualizar
 *   DELETE api/modelos.php?marcano=1&modelono=2                     -> eliminar
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'GET':
        if (isset($_GET['action']) && $_GET['action'] === 'marca') {
            obtenerMarca($pdo, $_GET['marcano'] ?? null);
        } elseif (isset($_GET['action']) && $_GET['action'] === 'corridas') {
            listarCorridas($pdo, $_GET['marcano'] ?? null);
        } elseif (isset($_GET['marcano']) && isset($_GET['modelono']) && $_GET['modelono'] !== '') {
            obtenerModelo($pdo, $_GET['marcano'], $_GET['modelono']);
        } elseif (isset($_GET['marcano']) && $_GET['marcano'] !== '') {
            listarModelos($pdo, $_GET['marcano']);
        } else {
            responder(false, 'MARCANO es requerido.', null, 400);
        }
        break;

    case 'POST':
        crearModelo($pdo);
        break;

    case 'PUT':
        if (!isset($_GET['marcano']) || !isset($_GET['modelono'])) {
            responder(false, 'MARCANO y MODELONO son requeridos para actualizar.', null, 400);
        }
        actualizarModelo($pdo, $_GET['marcano'], $_GET['modelono']);
        break;

    case 'DELETE':
        if (!isset($_GET['marcano']) || !isset($_GET['modelono'])) {
            responder(false, 'MARCANO y MODELONO son requeridos para eliminar.', null, 400);
        }
        eliminarModelo($pdo, $_GET['marcano'], $_GET['modelono']);
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

function listarModelos($pdo, $marcano)
{
    // Verificar que la marca exista, igual que Marca::findOrFail($marcano)
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM marcas WHERE MARCANO = :marcano');
    $stmt->execute([':marcano' => $marcano]);
    if ((int) $stmt->fetch()['total'] === 0) {
        responder(false, 'Marca no encontrada.', null, 404);
    }

    $busqueda  = isset($_GET['search']) ? trim($_GET['search']) : '';
    $pagina    = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $porPagina = 7; // igual que ->paginate(7) en Laravel
    $offset    = ($pagina - 1) * $porPagina;

    $where  = 'WHERE m.MARCANO = :marcano';
    $params = [':marcano' => $marcano];
    if ($busqueda !== '') {
        $where .= ' AND m.MODELODES LIKE :busqueda';
        $params[':busqueda'] = "%$busqueda%";
    }

    $stmtTotal = $pdo->prepare("SELECT COUNT(*) AS total FROM modelos m $where");
    $stmtTotal->execute($params);
    $total = (int) $stmtTotal->fetch()['total'];

    $sql = "SELECT m.MARCANO, m.MODELONO, m.MODELODES, m.COLOR, m.MATERIAL, m.SUELA,
                   m.PRECIOCO, m.PRECIOVE, m.SITUACION, m.CORRIDANO, m.fecha,
                   c.TALLAINI, c.TALLAFIN, cl.CLASIFIDES
            FROM modelos m
            LEFT JOIN corridas c ON c.MARCANO = m.MARCANO AND c.CORRIDANO = m.CORRIDANO
            LEFT JOIN clasifica cl ON cl.CLASIFINO = c.CLASIFICA
            $where
            ORDER BY m.MODELONO ASC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $modelos = $stmt->fetchAll();

    responder(true, '', [
        'modelos'      => $modelos,
        'total'        => $total,
        'porPagina'    => $porPagina,
        'paginaActual' => $pagina,
        'totalPaginas' => (int) ceil($total / $porPagina) ?: 1,
        'busqueda'     => $busqueda,
    ]);
}

function obtenerModelo($pdo, $marcano, $modelono)
{
    $stmt = $pdo->prepare(
        'SELECT MARCANO, MODELONO, MODELODES, COLOR, MATERIAL, SUELA, PRECIOCO, PRECIOVE, SITUACION, CORRIDANO, fecha
         FROM modelos WHERE MARCANO = :marcano AND MODELONO = :modelono'
    );
    $stmt->execute([':marcano' => $marcano, ':modelono' => $modelono]);
    $modelo = $stmt->fetch();

    if (!$modelo) {
        responder(false, 'Modelo no encontrado.', null, 404);
    }

    responder(true, '', $modelo);
}

function validarDatos($pdo, $marcano, $datos, array &$errores)
{
    // MODELODES: required|max:20 (según input required en el formulario original)
    $modelodes = trim((string) ($datos['MODELODES'] ?? ''));
    if ($modelodes === '') {
        $errores['MODELODES'] = 'La descripción del modelo es obligatoria.';
    } elseif (mb_strlen($modelodes) > 20) {
        $errores['MODELODES'] = 'La descripción no debe exceder 20 caracteres.';
    }

    // Campos de texto opcionales, máx 20 caracteres
    foreach (['COLOR', 'MATERIAL', 'SUELA'] as $campo) {
        $valor = trim((string) ($datos[$campo] ?? ''));
        if ($valor !== '' && mb_strlen($valor) > 20) {
            $errores[$campo] = 'No debe exceder 20 caracteres.';
        }
    }

    // Precios: numéricos opcionales
    foreach (['PRECIOCO', 'PRECIOVE'] as $campo) {
        $valor = $datos[$campo] ?? '';
        if ($valor !== '' && $valor !== null && !is_numeric($valor)) {
            $errores[$campo] = 'Debe ser un valor numérico.';
        }
    }

    // SITUACION: A o I
    $situacion = $datos['SITUACION'] ?? '';
    if ($situacion !== '' && !in_array($situacion, ['A', 'I'], true)) {
        $errores['SITUACION'] = 'Situación inválida.';
    }

    // CORRIDANO: required, debe existir para esa marca (select required en el formulario)
    $corridano = $datos['CORRIDANO'] ?? '';
    if ($corridano === '' || $corridano === null) {
        $errores['CORRIDANO'] = 'Debe seleccionar una corrida.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM corridas WHERE MARCANO = :marcano AND CORRIDANO = :corridano');
        $stmt->execute([':marcano' => $marcano, ':corridano' => $corridano]);
        if ((int) $stmt->fetch()['total'] === 0) {
            $errores['CORRIDANO'] = 'La corrida seleccionada no es válida para esta marca.';
        }
    }

    // fecha: opcional, formato Y-m-d
    $fecha = $datos['fecha'] ?? '';
    if ($fecha !== '' && $fecha !== null) {
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$d || $d->format('Y-m-d') !== $fecha) {
            $errores['fecha'] = 'La fecha no es válida.';
        }
    }
}

function crearModelo($pdo)
{
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $marcano = isset($input['MARCANO']) ? trim((string) $input['MARCANO']) : '';

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

    validarDatos($pdo, $marcano, $input, $errores);

    if (!empty($errores)) {
        responder(false, 'Errores de validación.', ['errores' => $errores], 422);
    }

    // Siguiente MODELONO por marca, igual que el controlador original
    $stmt = $pdo->prepare('SELECT MAX(MODELONO) AS maximo FROM modelos WHERE MARCANO = :marcano');
    $stmt->execute([':marcano' => $marcano]);
    $maximo = $stmt->fetch()['maximo'];
    $siguienteModelono = $maximo ? ((int) $maximo + 1) : 1;

    $stmt = $pdo->prepare(
        'INSERT INTO modelos (MARCANO, MODELONO, MODELODES, COLOR, MATERIAL, SUELA, PRECIOCO, PRECIOVE, SITUACION, CORRIDANO, fecha, created_at, updated_at)
         VALUES (:marcano, :modelono, :modelodes, :color, :material, :suela, :precioco, :precioventa, :situacion, :corridano, :fecha, NOW(), NOW())'
    );
    $stmt->execute([
        ':marcano'      => $marcano,
        ':modelono'     => $siguienteModelono,
        ':modelodes'    => trim((string) ($input['MODELODES'] ?? '')),
        ':color'        => valorONulo($input['COLOR'] ?? null),
        ':material'     => valorONulo($input['MATERIAL'] ?? null),
        ':suela'        => valorONulo($input['SUELA'] ?? null),
        ':precioco'     => valorONulo($input['PRECIOCO'] ?? null),
        ':precioventa'  => valorONulo($input['PRECIOVE'] ?? null),
        ':situacion'    => valorONulo($input['SITUACION'] ?? null),
        ':corridano'    => $input['CORRIDANO'],
        ':fecha'        => valorONulo($input['fecha'] ?? null),
    ]);

    responder(true, 'Modelo creado correctamente.');
}

function actualizarModelo($pdo, $marcano, $modelono)
{
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $stmt = $pdo->prepare('SELECT MARCANO FROM modelos WHERE MARCANO = :marcano AND MODELONO = :modelono');
    $stmt->execute([':marcano' => $marcano, ':modelono' => $modelono]);
    if (!$stmt->fetch()) {
        responder(false, 'Modelo no encontrado.', null, 404);
    }

    $errores = [];
    validarDatos($pdo, $marcano, $input, $errores);

    if (!empty($errores)) {
        responder(false, 'Errores de validación.', ['errores' => $errores], 422);
    }

    $stmt = $pdo->prepare(
        'UPDATE modelos SET MODELODES = :modelodes, COLOR = :color, MATERIAL = :material, SUELA = :suela,
             PRECIOCO = :precioco, PRECIOVE = :precioventa, SITUACION = :situacion, CORRIDANO = :corridano,
             fecha = :fecha, updated_at = NOW()
         WHERE MARCANO = :marcano AND MODELONO = :modelono'
    );
    $stmt->execute([
        ':modelodes'   => trim((string) ($input['MODELODES'] ?? '')),
        ':color'       => valorONulo($input['COLOR'] ?? null),
        ':material'    => valorONulo($input['MATERIAL'] ?? null),
        ':suela'       => valorONulo($input['SUELA'] ?? null),
        ':precioco'    => valorONulo($input['PRECIOCO'] ?? null),
        ':precioventa' => valorONulo($input['PRECIOVE'] ?? null),
        ':situacion'   => valorONulo($input['SITUACION'] ?? null),
        ':corridano'   => $input['CORRIDANO'],
        ':fecha'       => valorONulo($input['fecha'] ?? null),
        ':marcano'     => $marcano,
        ':modelono'    => $modelono,
    ]);

    responder(true, 'Modelo actualizado.');
}

function eliminarModelo($pdo, $marcano, $modelono)
{
    $stmt = $pdo->prepare('SELECT MARCANO FROM modelos WHERE MARCANO = :marcano AND MODELONO = :modelono');
    $stmt->execute([':marcano' => $marcano, ':modelono' => $modelono]);
    if (!$stmt->fetch()) {
        responder(false, 'Modelo no encontrado.', null, 404);
    }

    // Igual que $articulosConExistencia en el controlador original
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS total FROM articulos
         WHERE MARCANO = :marcano AND MODELONO = :modelono AND EXISTENCIA > 0"
    );
    $stmt->execute([':marcano' => $marcano, ':modelono' => $modelono]);
    if ((int) $stmt->fetch()['total'] > 0) {
        responder(false, 'No se puede eliminar: existen artículos con existencia mayor a cero.', null, 409);
    }

    // Eliminar el modelo
    $stmt = $pdo->prepare('DELETE FROM modelos WHERE MARCANO = :marcano AND MODELONO = :modelono');
    $stmt->execute([':marcano' => $marcano, ':modelono' => $modelono]);

    // Eliminar artículos con existencia <= 0 asociados a ese modelo
    $stmt = $pdo->prepare(
        "DELETE FROM articulos WHERE MARCANO = :marcano AND MODELONO = :modelono AND EXISTENCIA <= 0"
    );
    $stmt->execute([':marcano' => $marcano, ':modelono' => $modelono]);

    responder(true, 'Modelo y artículos con existencia cero eliminados correctamente.');
}

function valorONulo($valor)
{
    if ($valor === null) {
        return null;
    }
    $valor = trim((string) $valor);
    return $valor === '' ? null : $valor;
}
