<?php
/**
 * Configuración de conexión a la base de datos usando PDO.
 * Ajusta estos valores según tu entorno.
 */

$host     = 'localhost';
$dbname   = 'dbdianajquery'; // <-- Cambia por el nombre real de tu BD
$username = 'root';             // <-- Cambia por tu usuario de MySQL
$password = '';                 // <-- Cambia por tu contraseña de MySQL
$charset  = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $opciones);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    die(json_encode([
        'success' => false,
        'message' => 'Error de conexión a la base de datos: ' . $e->getMessage(),
    ]));
}
