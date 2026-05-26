<?php
require_once '../includes/conexion.php';
header('Content-Type: application/json');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    http_response_code(400);
    exit(json_encode(['error' => 'ID inválido']));
}

try {
    // Incluimos la columna 'puntuacion' en el SELECT
    $stmt = $pdo->prepare("SELECT r.comentario, r.puntuacion, r.fecha, CONCAT(SUBSTRING(u.correo, 1, 3), '***') as correo 
                           FROM resenas r 
                           JOIN usuarios u ON r.usuario_id = u.id 
                           WHERE r.ubicacion_id = ? 
                           ORDER BY r.fecha DESC");
    $stmt->execute([$id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo json_encode([]);
}