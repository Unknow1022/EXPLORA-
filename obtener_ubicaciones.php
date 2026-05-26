<?php
// api/obtener_ubicaciones.php
header('Content-Type: application/json');

// Buscamos la conexión de forma segura
$path = dirname(__DIR__) . '/includes/conexion.php';
if (!file_exists($path)) {
    echo json_encode(['error' => 'No se encontró conexion.php en: ' . $path]);
    exit;
}
require_once $path;

try {
    // Consulta optimizada para tu estructura real
    $sql = "SELECT l.id, l.nombre, l.descripcion, l.latitud, l.longitud, 
                   COALESCE(c.nombre, 'General') as categoria, 
                   COALESCE(c.icono, '📍') as icono,
                   COALESCE(d.nombre, 'Talara') as distrito,
                   (SELECT COUNT(*) FROM resenas WHERE ubicacion_id = l.id) as num_reviews
            FROM lugares l
            LEFT JOIN categorias c ON l.categoria_id = c.id
            LEFT JOIN distritos d ON l.distrito_id = d.id";

    $stmt = $pdo->query($sql);
    echo json_encode($stmt->fetchAll());
} catch (Exception $e) {
    error_log("DB Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor.']);
}