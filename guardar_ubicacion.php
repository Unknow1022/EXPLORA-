<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}
require '../includes/conexion.php';

$datos = json_decode(file_get_contents("php://input"), true);

if (!$datos || !isset($datos['nombre'], $datos['lat'], $datos['lng'], $datos['historia'], $datos['categoria'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    exit;
}

try {
    $sql = "INSERT INTO lugares (nombre, descripcion, latitud, longitud, categoria_id, distrito_id, historia) VALUES (?, '', ?, ?, ?, 1, ?)"; // Assuming distrito_id default 1
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        htmlspecialchars($datos['nombre'], ENT_QUOTES, 'UTF-8'), 
        $datos['lat'], 
        $datos['lng'], 
        $datos['categoria'],
        htmlspecialchars($datos['historia'], ENT_QUOTES, 'UTF-8')
    ]);
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save location']);
}
?>