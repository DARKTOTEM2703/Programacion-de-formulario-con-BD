<?php
session_start();
require_once '../components/db_connection.php';

$data = json_decode(file_get_contents('php://input'), true);
$tracking_number = $data['tracking_number'] ?? '';
$pin_seguro = $data['pin_seguro'] ?? '';
$repartidor_id = $_SESSION['usuario_id'] ?? null;

if (!$tracking_number || !$pin_seguro || !$repartidor_id) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

// Validar el PIN
$stmt = $conn->prepare("SELECT id FROM envios WHERE tracking_number = ? AND pin_seguro = ?");
$stmt->bind_param("si", $tracking_number, $pin_seguro);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $envio = $result->fetch_assoc();

    // Cambiar el estado a "Entregado"
    $stmt = $conn->prepare("UPDATE envios SET status = 'Entregado', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $envio['id']);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Entrega validada correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'PIN incorrecto']);
}
?>