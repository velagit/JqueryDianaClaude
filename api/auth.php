<?php
/**
 * API de autenticación.
 *
 * Endpoints:
 *   POST api/auth.php?action=login   (body JSON: usuario, password) -> inicia sesión
 *   GET  api/auth.php?action=logout                                  -> cierra sesión
 *   GET  api/auth.php?action=estado                                  -> datos del usuario en sesión
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json; charset=utf-8');

$metodo = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['action'] ?? '';

if ($metodo === 'POST' && $accion === 'login') {
    login($pdo);
} elseif ($accion === 'logout') {
    logout();
} elseif ($accion === 'estado') {
    estado();
} else {
    responder(false, 'Acción no reconocida.', null, 400);
}

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

function login($pdo)
{
    $input    = json_decode(file_get_contents('php://input'), true) ?? [];
    $usuario  = trim((string) ($input['usuario'] ?? ''));
    $password = (string) ($input['password'] ?? '');

    if ($usuario === '' || $password === '') {
        responder(false, 'Usuario y contraseña son obligatorios.', null, 422);
    }

    $stmt = $pdo->prepare('SELECT id, nombre, usuario, password, tipo, activo FROM usuarios WHERE usuario = :usuario');
    $stmt->execute([':usuario' => $usuario]);
    $u = $stmt->fetch();

    if (!$u || !password_verify($password, $u['password'])) {
        responder(false, 'Usuario o contraseña incorrectos.', null, 401);
    }

    if ((int) $u['activo'] === 0) {
        responder(false, 'Este usuario está desactivado. Contacta a un administrador.', null, 403);
    }

    // Regenerar el ID de sesión al iniciar sesión (buena práctica de seguridad)
    session_regenerate_id(true);

    $_SESSION['usuario_id']     = $u['id'];
    $_SESSION['usuario_nombre'] = $u['nombre'];
    $_SESSION['usuario_login']  = $u['usuario'];
    $_SESSION['usuario_tipo']   = $u['tipo'];

    responder(true, 'Bienvenido, ' . $u['nombre'], ['tipo' => $u['tipo']]);
}

function logout()
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $parametros = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $parametros['path'], $parametros['domain'], $parametros['secure'], $parametros['httponly']);
    }
    session_destroy();
    responder(true, 'Sesión cerrada.');
}

function estado()
{
    if (estaAutenticado()) {
        responder(true, '', usuarioActual());
    } else {
        responder(false, 'No autenticado.', null, 401);
    }
}
