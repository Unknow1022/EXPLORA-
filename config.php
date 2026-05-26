<?php
// Configuración de la base de datos
$envFile = __DIR__ . '/../.env';
$env = file_exists($envFile) ? parse_ini_file($envFile) : [];

define('DB_HOST', $env['DB_HOST'] ?? 'localhost');
define('DB_NAME', $env['DB_NAME'] ?? 'Exploramos');
define('DB_USER', $env['DB_USER'] ?? 'root');
define('DB_PASS', $env['DB_PASS'] ?? '');

// Cabeceras de seguridad HTTP (VULN-013)
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' unpkg.com fonts.googleapis.com; style-src 'self' 'unsafe-inline' unpkg.com fonts.googleapis.com; img-src 'self' data: *.openstreetmap.org;");

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error de conexión: " . $e->getMessage());
    die("Error técnico en el servidor. Por favor, intente más tarde.");
}
?>