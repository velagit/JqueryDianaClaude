<?php
/**
 * Script de instalación de un solo uso: crea el primer usuario administrador.
 *
 * INSTRUCCIONES:
 *   1. Asegúrate de haber creado la tabla "usuarios" (ver setup_usuarios.sql).
 *   2. Ajusta $nombre, $usuario y $password abajo si lo deseas.
 *   3. Abre este archivo en el navegador UNA sola vez: instalar_admin.php
 *   4. Elimina este archivo del servidor después de usarlo (por seguridad).
 */

require_once __DIR__ . '/config/database.php';

// --- Datos del administrador inicial (puedes cambiarlos) ---
$nombre   = 'Administrador';
$usuario  = 'admin';
$password = 'admin123'; // Cámbiala aquí antes de ejecutar, o cámbiala luego desde el sistema
// -------------------------------------------------------------

// Evitar crear un segundo admin por accidente si ya existe alguno
$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM usuarios WHERE tipo = 'administrador'");
$stmt->execute();
$yaHayAdmin = (int) $stmt->fetch()['total'] > 0;

if ($yaHayAdmin) {
    die('Ya existe al menos un usuario administrador. Por seguridad, este script no creará otro. '
        . 'Si necesitas restablecer una contraseña, hazlo desde el módulo de Usuarios o directamente en la base de datos. '
        . 'Elimina este archivo del servidor.');
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    'INSERT INTO usuarios (nombre, usuario, password, tipo, activo, created_at, updated_at)
     VALUES (:nombre, :usuario, :password, :tipo, 1, NOW(), NOW())'
);
$stmt->execute([
    ':nombre'   => $nombre,
    ':usuario'  => $usuario,
    ':password' => $hash,
    ':tipo'     => 'administrador',
]);

echo 'Usuario administrador creado correctamente.<br>';
echo 'Usuario: ' . htmlspecialchars($usuario) . '<br>';
echo 'Contraseña: la que configuraste en este script.<br><br>';
echo '<strong>Ahora elimina este archivo (instalar_admin.php) del servidor por seguridad.</strong><br>';
echo '<a href="login.php">Ir a iniciar sesión</a>';
