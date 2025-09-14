<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../components/db_connection.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'error' => 'no_session', 'notifications' => [], 'count' => 0]);
    exit();
}

$usuario_id = (int) $_SESSION['usuario_id'];

// Obtener notificaciones pendientes (pendiente + limitar)
$stmt = $conn->prepare("SELECT id, tipo, asunto, contenido, link, status, created_at FROM notificaciones WHERE usuario_id = ? ORDER BY created_at DESC LIMIT 20");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$res = $stmt->get_result();

$notifications = [];
while ($row = $res->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

echo json_encode([
    'ok' => true,
    'notifications' => $notifications,
    'count' => count($notifications)
], JSON_UNESCAPED_UNICODE);