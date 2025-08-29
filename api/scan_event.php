<?php
//// php
// filepath: api/scan_event.php
session_start();
require_once '../components/db_connection.php';
require_once '../components/auth_helper.php';

$role = $_SESSION['rol'] ?? null;
$rol_id = $_SESSION['rol_id'] ?? null;

// Leer payload
$data = json_decode(file_get_contents('php://input'), true);
$tracking_number = trim($data['tracking'] ?? '');
$accion = $data['accion'] ?? '';
$repartidor_id = $_SESSION['usuario_id'] ?? null;

// Autorización por acción: intake = personal bodega/admin, transito/entrega = repartidor/admin
function unauthorized() {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado para esta acción']);
    exit();
}

if ($accion === 'intake') {
    // permitir solo si rol string es 'bodega' o admin (fall back por rol_id == 1)
    if (!in_array($role, ['bodega','admin']) && $rol_id != 1) unauthorized();
} else {
    // acciones realizadas por repartidores
    if (!in_array($role, ['repartidor','admin']) && $rol_id != 3) unauthorized();
}

// Validaciones básicas
if (!$tracking_number || !$accion) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

// Buscar el envío
$stmt = $conn->prepare("SELECT id, status FROM envios WHERE tracking_number = ?");
$stmt->bind_param("s", $tracking_number);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) { echo json_encode(['success' => false, 'message' => 'El envío no existe']); exit(); }
$envio = $result->fetch_assoc();

// Si la acción viene de repartidor (p.ej. 'transito' o 'entrega'), exigir que el paquete esté en bodega
if (in_array($accion, ['transito','entrega'])) {
    if ($envio['status'] !== 'Recibido bodega') {
        echo json_encode(['success' => false, 'message' => 'El paquete no está en la bodega. Estado actual: ' . $envio['status']]);
        exit();
    }
}

// Procesar acciones: intake -> marcar Recibido bodega; transito -> En tránsito; etc.
if ($accion === 'intake') {
    // marcar recibido en bodega
    $stmt = $conn->prepare("UPDATE envios SET status = 'Recibido bodega', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $envio['id']);
    $ok = $stmt->execute();
    echo json_encode(['success' => $ok, 'message' => $ok ? 'Recepción registrada' : 'Error al registrar recibo']);
    exit();
}

if ($accion === 'transito') {
    $stmt = $conn->prepare("UPDATE envios SET status = 'En tránsito', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $envio['id']);
    $stmt->execute();
    echo json_encode(['success' => true, 'message' => 'Estado actualizado a En tránsito']);
    exit();
}

// otras acciones: implementar según necesidad...
echo json_encode(['success' => false, 'message' => 'Acción no soportada']);
exit();