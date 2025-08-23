<?php
//// php
// filepath: api/scan_event.php
session_start();
require_once '../components/db_connection.php';

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol_id'], [4])) {
    // 4 = Rol de repartidor (ajusta según tu sistema)
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$tracking_number = trim($data['tracking'] ?? '');
$accion = $data['accion'] ?? '';
$repartidor_id = $_SESSION['usuario_id'];

if (!$tracking_number || !$accion) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

// Verificar si el envío existe
$stmt = $conn->prepare("SELECT id, status FROM envios WHERE tracking_number = ?");
$stmt->bind_param("s", $tracking_number);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'El envío no existe']);
    exit();
}

$envio = $result->fetch_assoc();

// Verificar si el envío ya está asignado a un repartidor
$stmt = $conn->prepare("SELECT * FROM repartidores_envios WHERE envio_id = ?");
$stmt->bind_param("i", $envio['id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Asignar automáticamente al repartidor
    $stmt = $conn->prepare("INSERT INTO repartidores_envios (envio_id, usuario_id, asignado_en) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $envio['id'], $repartidor_id);
    $stmt->execute();
} else {
    // Si ya está asignado, actualiza al repartidor actual
    $stmt = $conn->prepare("UPDATE repartidores_envios SET usuario_id = ?, asignado_en = NOW() WHERE envio_id = ?");
    $stmt->bind_param("ii", $repartidor_id, $envio['id']);
    $stmt->execute();
}

// Cambiar el estado a "En tránsito" si la acción es "transito"
if ($accion === 'transito') {
    if ($envio['status'] !== 'Recibido bodega') {
        echo json_encode(['success' => false, 'message' => 'El envío no está en estado "Recibido bodega"']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE envios SET status = 'En tránsito', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $envio['id']);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Estado actualizado a "En tránsito" y repartidor asignado']);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Acción no soportada']);