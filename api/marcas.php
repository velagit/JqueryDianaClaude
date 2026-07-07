<?php
/**
 * API REST para el CRUD de Marcas.
 * Equivalente en PHP + PDO del MarcaController de Laravel.
 *
 * Endpoints:
 *   GET    api/marcas.php?buscar=texto&page=1        -> listar (paginado, 5 por página)
 *   GET    api/marcas.php?MARCANO=5                   -> obtener una marca
 *   GET    api/marcas.php?action=nuevo_codigo         -> siguiente código disponible
 *   POST   api/marcas.php   (body JSON: MARCANO, MARCADES) -> crear
 *   PUT    api/marcas.php?MARCANO=5 (body JSON: MARCADES)  -> actualizar
 *   DELETE api/marcas.php?MARCANO=5                   -> eliminar
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'GET':
        if (isset($_GET['action']) && $_GET['action'] === 'nuevo_codigo') {
            obtenerSiguienteCodigo($pdo);
        } elseif (isset($_GET['MARCANO']) && $_GET['MARCANO'] !== '') {
            obtenerMarca($pdo, $_GET['MARCANO']);
        } else {
            listarMarcas($pdo);
        }
        break;

    case 'POST':
        crearMarca($pdo);
        break;

    case 'PUT':
        if (!isset($_GET['MARCANO']) || $_GET['MARCANO'] === '') {
            responder(false, 'MARCANO es requerido para actualizar.', null, 400);
        }
        actualizarMarca($pdo, $_GET['MARCANO']);
        break;

    case 'DELETE':
        if (!isset($_GET['MARCANO']) || $_GET['MARCANO'] === '') {
            responder(false, 'MARCANO es requerido para eliminar.', null, 400);
        }
        eliminarMarca($pdo, $_GET['MARCANO']);
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

function listarMarcas($pdo)
{
    $busqueda  = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
    $pagina    = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $porPagina = 5; // igual que ->paginate(5) en Laravel
    $offset    = ($pagina - 1) * $porPagina;

    $where  = '';
    $params = [];
    if ($busqueda !== '') {
        $where = 'WHERE MARCADES LIKE :busqueda';
        $params[':busqueda'] = "%$busqueda%";
    }

    // Total de registros (para la paginación)
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) AS total FROM marcas $where");
    $stmtTotal->execute($params);
    $total = (int) $stmtTotal->fetch()['total'];

    // Registros de la página actual
    $sql  = "SELECT MARCANO, MARCADES FROM marcas $where ORDER BY MARCANO ASC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $marcas = $stmt->fetchAll();

    responder(true, '', [
        'marcas'       => $marcas,
        'total'        => $total,
        'porPagina'    => $porPagina,
        'paginaActual' => $pagina,
        'totalPaginas' => (int) ceil($total / $porPagina) ?: 1,
        'busqueda'     => $busqueda,
    ]);
}

function obtenerMarca($pdo, $marcano)
{
    $stmt = $pdo->prepare('SELECT MARCANO, MARCADES FROM marcas WHERE MARCANO = :marcano');
    $stmt->execute([':marcano' => $marcano]);
    $marca = $stmt->fetch();

    if (!$marca) {
        responder(false, 'Marca no encontrada.', null, 404);
    }

    responder(true, '', $marca);
}

function obtenerSiguienteCodigo($pdo)
{
    // Equivalente a Marca::siguienteCodigo() del modelo
    $stmt    = $pdo->query('SELECT MAX(MARCANO) AS maximo FROM marcas');
    $maximo  = $stmt->fetch()['maximo'];
    $siguiente = $maximo ? ((int) $maximo + 1) : 1;

    responder(true, '', ['siguienteCodigo' => $siguiente]);
}

function crearMarca($pdo)
{
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $marcano  = isset($input['MARCANO']) ? trim((string) $input['MARCANO']) : '';
    $marcades = isset($input['MARCADES']) ? trim((string) $input['MARCADES']) : '';

    // Validaciones equivalentes a: 'MARCANO' => 'required|integer|unique:marcas', 'MARCADES' => 'required|string|max:20'
    $errores = [];

    if ($marcano === '' || !ctype_digit($marcano)) {
        $errores['MARCANO'] = 'El código de marca es obligatorio y debe ser un número entero.';
    }

    if ($marcades === '') {
        $errores['MARCADES'] = 'La descripción es obligatoria.';
    } elseif (mb_strlen($marcades) > 20) {
        $errores['MARCADES'] = 'La descripción no debe exceder 20 caracteres.';
    }

    if (empty($errores['MARCANO'])) {
        $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM marcas WHERE MARCANO = :marcano');
        $stmt->execute([':marcano' => $marcano]);
        if ((int) $stmt->fetch()['total'] > 0) {
            $errores['MARCANO'] = 'Ese código de marca ya existe.';
        }
    }

    if (!empty($errores)) {
        responder(false, 'Errores de validación.', ['errores' => $errores], 422);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO marcas (MARCANO, MARCADES, created_at, updated_at) VALUES (:marcano, :marcades, NOW(), NOW())'
    );
    $stmt->execute([
        ':marcano'  => $marcano,
        ':marcades' => $marcades,
    ]);

    responder(true, 'Marca creada con éxito.');
}

function actualizarMarca($pdo, $marcano)
{
    $input    = json_decode(file_get_contents('php://input'), true) ?? [];
    $marcades = isset($input['MARCADES']) ? trim((string) $input['MARCADES']) : '';

    $stmt = $pdo->prepare('SELECT MARCANO FROM marcas WHERE MARCANO = :marcano');
    $stmt->execute([':marcano' => $marcano]);
    if (!$stmt->fetch()) {
        responder(false, 'Marca no encontrada.', null, 404);
    }

    // Validación equivalente a: 'MARCADES' => 'required|string|max:20'
    $errores = [];
    if ($marcades === '') {
        $errores['MARCADES'] = 'La descripción es obligatoria.';
    } elseif (mb_strlen($marcades) > 20) {
        $errores['MARCADES'] = 'La descripción no debe exceder 20 caracteres.';
    }

    if (!empty($errores)) {
        responder(false, 'Errores de validación.', ['errores' => $errores], 422);
    }

    $stmt = $pdo->prepare('UPDATE marcas SET MARCADES = :marcades, updated_at = NOW() WHERE MARCANO = :marcano');
    $stmt->execute([
        ':marcades' => $marcades,
        ':marcano'  => $marcano,
    ]);

    responder(true, 'Marca actualizada con éxito.');
}

function eliminarMarca($pdo, $marcano)
{
    $stmt = $pdo->prepare('SELECT MARCANO FROM marcas WHERE MARCANO = :marcano');
    $stmt->execute([':marcano' => $marcano]);
    if (!$stmt->fetch()) {
        responder(false, 'Marca no encontrada.', null, 404);
    }

    // Igual que $marca->modelos()->exists() en el controlador original
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM modelos WHERE MARCANO = :marcano');
    $stmt->execute([':marcano' => $marcano]);
    if ((int) $stmt->fetch()['total'] > 0) {
        responder(false, 'No se puede eliminar la marca porque tiene modelos registrados.', null, 409);
    }

    // Igual que la consulta a DB::table('corridas') en el controlador original
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM corridas WHERE MARCANO = :marcano');
    $stmt->execute([':marcano' => $marcano]);
    if ((int) $stmt->fetch()['total'] > 0) {
        responder(false, 'No se puede eliminar la marca porque tiene corridas registradas.', null, 409);
    }

    $stmt = $pdo->prepare('DELETE FROM marcas WHERE MARCANO = :marcano');
    $stmt->execute([':marcano' => $marcano]);

    responder(true, 'Marca eliminada exitosamente.');
}
