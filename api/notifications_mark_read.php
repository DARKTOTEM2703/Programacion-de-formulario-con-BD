<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../components/db_connection.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'error' => 'no_session']);
    exit();
}

$usuario_id = (int) $_SESSION['usuario_id'];
$input = json_decode(file_get_contents('php://input'), true);

if (!empty($input['all'])) {
    $stmt = $conn->prepare("UPDATE notificaciones SET status = 'leido', read_at = NOW() WHERE usuario_id = ? AND status = 'pendiente'");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    echo json_encode(['ok' => true, 'updated' => $affected]);
    exit();
}

$id = isset($input['id']) ? (int)$input['id'] : 0;
if ($id > 0) {
    $stmt = $conn->prepare("UPDATE notificaciones SET status = 'leido', read_at = NOW() WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $id, $usuario_id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    echo json_encode(['ok' => true, 'updated' => $affected]);
    exit();
}

echo json_encode(['ok' => false, 'error' => 'invalid_request']);