<?php
// includes/conexion.php
$envFile = __DIR__ . '/../.env';
$env = file_exists($envFile) ? parse_ini_file($envFile) : [];

$host = $env['DB_HOST'] ?? 'localhost';
$db   = $env['DB_NAME'] ?? 'Exploramos';
$user = $env['DB_USER'] ?? 'root';
$pass = $env['DB_PASS'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('DB Connection Error: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Fallo interno del servidor.']);
    exit;
}