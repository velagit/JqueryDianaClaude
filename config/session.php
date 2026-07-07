<?php
/**
 * Bootstrap de sesión + funciones de autenticación y autorización.
 * Debe incluirse al inicio de cada página o endpoint protegido,
 * ANTES de imprimir cualquier salida (usa header() para redirigir).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * ¿Hay una sesión de usuario activa?
 */
function estaAutenticado(): bool
{
    return isset($_SESSION['usuario_id']);
}

/**
 * Devuelve los datos del usuario en sesión, o null si no hay sesión.
 */
function usuarioActual(): ?array
{
    if (!estaAutenticado()) {
        return null;
    }
    return [
        'id'      => $_SESSION['usuario_id'],
        'nombre'  => $_SESSION['usuario_nombre'],
        'usuario' => $_SESSION['usuario_login'],
        'tipo'    => $_SESSION['usuario_tipo'],
    ];
}

/**
 * ¿El usuario en sesión es administrador?
 */
function esAdministrador(): bool
{
    return estaAutenticado() && $_SESSION['usuario_tipo'] === 'administrador';
}

/**
 * Para PÁGINAS HTML: exige sesión iniciada, si no, redirige a login.php.
 */
function requerirLogin(): void
{
    if (!estaAutenticado()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Para PÁGINAS HTML: exige rol administrador, si no, redirige al listado principal.
 */
function requerirAdmin(): void
{
    requerirLogin();
    if (!esAdministrador()) {
        header('Location: index.php?error=acceso_denegado');
        exit;
    }
}

/**
 * Para ENDPOINTS DE LA API: responde 401 en JSON en vez de redirigir.
 */
function requerirLoginApi(): void
{
    if (!estaAutenticado()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Sesión no iniciada. Por favor inicia sesión nuevamente.']);
        exit;
    }
}

/**
 * Para ENDPOINTS DE LA API: exige rol administrador, responde 403 en JSON si no lo es.
 */
function requerirAdminApi(): void
{
    requerirLoginApi();
    if (!esAdministrador()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tienes permisos de administrador para esta acción.']);
        exit;
    }
}
