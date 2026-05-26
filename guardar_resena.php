<?php
session_start();
require_once '../includes/conexion.php';
header('Content-Type: application/json');

// Evitar que errores PHP "ensucien" el JSON
ob_start();

$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Verificación de seguridad básica
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Debes iniciar sesión para comentar.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$lugar_id = $data['ubicacion_id'] ?? null;
$comentario = htmlspecialchars(trim($data['comentario'] ?? ''), ENT_QUOTES, 'UTF-8');
$puntuacion = intval($data['puntuacion'] ?? 5);

if ($puntuacion < 1 || $puntuacion > 5) {
    echo json_encode(['status' => 'error', 'message' => 'Puntuación inválida.']);
    exit;
}

if (!$lugar_id || empty($comentario)) {
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO resenas (comentario, puntuacion, ubicacion_id, usuario_id, fecha) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$comentario, $puntuacion, $lugar_id, $user_id]);

    ob_clean(); // Limpiar basura
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    ob_clean();
    error_log("DB Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error interno del servidor.']);
}