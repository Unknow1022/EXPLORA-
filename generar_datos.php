<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}
require_once '../includes/conexion.php';

$opiniones = [
    ["texto" => "¡El mejor lugar de Talara! No se lo pierdan.", "min" => 4, "max" => 5],
    ["texto" => "La atención fue regular, pero la vista es increíble.", "min" => 3, "max" => 4],
    ["texto" => "Un poco caro para lo que ofrecen, pero está bien.", "min" => 2, "max" => 3],
    ["texto" => "¡Me encantó! Volveré con toda mi familia.", "min" => 5, "max" => 5],
    ["texto" => "No es lo que esperaba, falta limpieza.", "min" => 1, "max" => 2],
    ["texto" => "Excelente servicio y ambiente muy norteño.", "min" => 4, "max" => 5]
];

try {
    // Obtenemos IDs de lugares y usuarios
    $lugares = $pdo->query("SELECT id FROM lugares")->fetchAll(PDO::FETCH_COLUMN);
    $usuarios = $pdo->query("SELECT id FROM usuarios")->fetchAll(PDO::FETCH_COLUMN);

    if (empty($usuarios)) die("Crea al menos un usuario primero.");

    $pdo->beginTransaction();
    foreach ($lugares as $lugar_id) {
        $cantidad = rand(3, 6); // De 3 a 6 reseñas por sitio
        for ($i = 0; $i < $cantidad; $i++) {
            $op = $opiniones[array_rand($opiniones)];
            $puntos = rand($op['min'], $op['max']);
            $user = $usuarios[array_rand($usuarios)];
            
            $stmt = $pdo->prepare("INSERT INTO resenas (comentario, puntuacion, ubicacion_id, usuario_id, fecha) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$op['texto'], $puntos, $lugar_id, $user]);
        }
    }
    $pdo->commit();
    echo "¡Reseñas generadas con éxito! <a href='../dashboard.php'>Ver mapa</a>";
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("DB Error: " . $e->getMessage());
    echo "Error interno del servidor.";
}