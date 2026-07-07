<?php
/**
 * API REST para el CRUD de Usuarios.
 * Todas las acciones exigen que el usuario en sesión sea administrador.
 *
 * Endpoints:
 *   GET    api/usuarios.php               -> listar usuarios
 *   GET    api/usuarios.php?id=1           -> obtener un usuario
 *   POST   api/usuarios.php  (body JSON)   -> crear usuario
 *   PUT    api/usuarios.php?id=1 (body JSON) -> actualizar usuario
 *   DELETE api/usuarios.php?id=1           -> eliminar usuario
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json; charset=utf-8');
requerirAdminApi();

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'GET':
        if (isset($_GET['id'])) {
            obtenerUsuario($pdo, $_GET['id']);
        } else {
            listarUsuarios($pdo);
        }
        break;

    case 'POST':
        crearUsuario($pdo);
        break;

    case 'PUT':
        if (!isset($_GET['id'])) {
            responder(false, 'ID de usuario requerido.', null, 400);
        }
        actualizarUsuario($pdo, $_GET['id']);
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            responder(false, 'ID de usuario requerido.', null, 400);
        }
        eliminarUsuario($pdo, $_GET['id']);
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

function listarUsuarios($pdo)
{
    $stmt = $pdo->query('SELECT id, nombre, usuario, tipo, activo, created_at FROM usuarios ORDER BY nombre ASC');
    responder(true, '', $stmt->fetchAll());
}

function obtenerUsuario($pdo, $id)
{
    $stmt = $pdo->prepare('SELECT id, nombre, usuario, tipo, activo FROM usuarios WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        responder(false, 'Usuario no encontrado.', null, 404);
    }

    responder(true, '', $usuario);
}

function validarComun($pdo, $nombre, $usuario, $tipo, $password, $esCreacion, $idExcluir = null, array &$errores)
{
    if ($nombre === '') {
        $errores['nombre'] = 'El nombre es obligatorio.';
    } elseif (mb_strlen($nombre) > 100) {
        $errores['nombre'] = 'El nombre no debe exceder 100 caracteres.';
    }

    if ($usuario === '') {
        $errores['usuario'] = 'El usuario es obligatorio.';
    } elseif (mb_strlen($usuario) > 50) {
        $errores['usuario'] = 'El usuario no debe exceder 50 caracteres.';
    } else {
        $sql    = 'SELECT COUNT(*) AS total FROM usuarios WHERE usuario = :usuario';
        $params = [':usuario' => $usuario];
        if ($idExcluir !== null) {
            $sql .= ' AND id != :id';
            $params[':id'] = $idExcluir;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ((int) $stmt->fetch()['total'] > 0) {
            $errores['usuario'] = 'Ese nombre de usuario ya existe.';
        }
    }

    if (!in_array($tipo, ['administrador', 'general'], true)) {
        $errores['tipo'] = 'El tipo de usuario no es válido.';
    }

    // En creación la contraseña es obligatoria; en edición es opcional (solo si se desea cambiar)
    if ($esCreacion || $password !== '') {
        if (mb_strlen($password) < 6) {
            $errores['password'] = 'La contraseña debe tener al menos 6 caracteres.';
        }
    }
}

function crearUsuario($pdo)
{
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $nombre   = trim((string) ($input['nombre'] ?? ''));
    $usuario  = trim((string) ($input['usuario'] ?? ''));
    $password = (string) ($input['password'] ?? '');
    $tipo     = $input['tipo'] ?? 'general';
    $activo   = isset($input['activo']) ? (int) (bool) $input['activo'] : 1;

    $errores = [];
    validarComun($pdo, $nombre, $usuario, $tipo, $password, true, null, $errores);

    if (!empty($errores)) {
        responder(false, 'Errores de validación.', ['errores' => $errores], 422);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (nombre, usuario, password, tipo, activo, created_at, updated_at)
         VALUES (:nombre, :usuario, :password, :tipo, :activo, NOW(), NOW())'
    );
    $stmt->execute([
        ':nombre'   => $nombre,
        ':usuario'  => $usuario,
        ':password' => $hash,
        ':tipo'     => $tipo,
        ':activo'   => $activo,
    ]);

    responder(true, 'Usuario creado correctamente.');
}

function actualizarUsuario($pdo, $id)
{
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $nombre   = trim((string) ($input['nombre'] ?? ''));
    $usuario  = trim((string) ($input['usuario'] ?? ''));
    $password = (string) ($input['password'] ?? '');
    $tipo     = $input['tipo'] ?? 'general';
    $activo   = isset($input['activo']) ? (int) (bool) $input['activo'] : 1;

    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE id = :id');
    $stmt->execute([':id' => $id]);
    if (!$stmt->fetch()) {
        responder(false, 'Usuario no encontrado.', null, 404);
    }

    $errores = [];
    validarComun($pdo, $nombre, $usuario, $tipo, $password, false, $id, $errores);

    // Evitar que el propio administrador se quite el rol o se desactive si es el único admin activo
    $esUsuarioActual = (int) $id === (int) ($_SESSION['usuario_id'] ?? 0);
    if ($esUsuarioActual && ($tipo !== 'administrador' || $activo === 0)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM usuarios WHERE tipo = 'administrador' AND activo = 1 AND id != :id");
        $stmt->execute([':id' => $id]);
        if ((int) $stmt->fetch()['total'] === 0) {
            $errores['tipo'] = 'No puedes quitarte el rol de administrador ni desactivarte: eres el único administrador activo.';
        }
    }

    if (!empty($errores)) {
        responder(false, 'Errores de validación.', ['errores' => $errores], 422);
    }

    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            'UPDATE usuarios SET nombre = :nombre, usuario = :usuario, password = :password, tipo = :tipo, activo = :activo, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':nombre'   => $nombre,
            ':usuario'  => $usuario,
            ':password' => $hash,
            ':tipo'     => $tipo,
            ':activo'   => $activo,
            ':id'       => $id,
        ]);
    } else {
        $stmt = $pdo->prepare(
            'UPDATE usuarios SET nombre = :nombre, usuario = :usuario, tipo = :tipo, activo = :activo, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':nombre'  => $nombre,
            ':usuario' => $usuario,
            ':tipo'    => $tipo,
            ':activo'  => $activo,
            ':id'      => $id,
        ]);
    }

    // Si el usuario editado es el que está en sesión, refrescar sus datos de sesión
    if ($esUsuarioActual) {
        $_SESSION['usuario_nombre'] = $nombre;
        $_SESSION['usuario_login']  = $usuario;
        $_SESSION['usuario_tipo']   = $tipo;
    }

    responder(true, 'Usuario actualizado correctamente.');
}

function eliminarUsuario($pdo, $id)
{
    $stmt = $pdo->prepare('SELECT tipo, activo FROM usuarios WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        responder(false, 'Usuario no encontrado.', null, 404);
    }

    if ((int) $id === (int) ($_SESSION['usuario_id'] ?? 0)) {
        responder(false, 'No puedes eliminar tu propio usuario mientras tienes la sesión iniciada.', null, 409);
    }

    if ($usuario['tipo'] === 'administrador') {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM usuarios WHERE tipo = 'administrador' AND activo = 1 AND id != :id");
        $stmt->execute([':id' => $id]);
        if ((int) $stmt->fetch()['total'] === 0) {
            responder(false, 'No se puede eliminar: es el único administrador activo del sistema.', null, 409);
        }
    }

    $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = :id');
    $stmt->execute([':id' => $id]);

    responder(true, 'Usuario eliminado correctamente.');
}
