<?php
// filepath: api/test_status_change.php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../components/db_connection.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'error' => 'no_session']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$tracking_number = $input['tracking_number'] ?? '';
$new_status = $input['status'] ?? '';

if (empty($tracking_number) || empty($new_status)) {
    echo json_encode(['ok' => false, 'error' => 'missing_params']);
    exit;
}

try {
    // Buscar el envío
    $stmt = $conn->prepare("SELECT id, status, usuario_id FROM envios WHERE tracking_number = ?");
    $stmt->bind_param("s", $tracking_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['ok' => false, 'error' => 'shipment_not_found']);
        exit;
    }

    $envio = $result->fetch_assoc();
    $old_status = $envio['status'];

    // Actualizar el status (esto disparará el trigger automáticamente)
    $stmt = $conn->prepare("UPDATE envios SET status = ?, updated_at = NOW() WHERE tracking_number = ?");
    $stmt->bind_param("ss", $new_status, $tracking_number);
    $success = $stmt->execute();

    if ($success) {
        // Agregar entrada al historial de tracking
        $stmt = $conn->prepare("INSERT INTO tracking_history (envio_id, status, location, notes, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $location = "Actualización de prueba";
        $notes = "Estado cambiado de '$old_status' a '$new_status' (prueba automática)";
        $stmt->bind_param("isssi", $envio['id'], $new_status, $location, $notes, $_SESSION['usuario_id']);
        $stmt->execute();
        
        echo json_encode([
            'ok' => true, 
            'message' => "Estado cambiado de '$old_status' a '$new_status'",
            'tracking_number' => $tracking_number,
            'old_status' => $old_status,
            'new_status' => $new_status
        ]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'update_failed']);
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => 'database_error: ' . $e->getMessage()]);
}
?>