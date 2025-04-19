<?php
header('Content-Type: application/json');
session_start();
require_once '../components/db_connection.php';

// Verificar autenticación
if (!isset($_SESSION['repartidor_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit();
}

$repartidor_id = $_SESSION['repartidor_id'];

// Verificar que se recibieron los datos necesarios
if (!isset($_POST['envio_id']) || !isset($_POST['latitude']) || !isset($_POST['longitude'])) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit();
}

$envio_id = intval($_POST['envio_id']);
$latitude = floatval($_POST['latitude']);
$longitude = floatval($_POST['longitude']);
$status = $_POST['status'] ?? 'En tránsito';

// Verificar que el envío está asignado al repartidor
$stmt = $conn->prepare("
    SELECT COUNT(*) as count FROM repartidores_envios 
    WHERE repartidor_id = ? AND envio_id = ?
");
$stmt->bind_param("ii", $repartidor_id, $envio_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    echo json_encode(['success' => false, 'error' => 'No tienes permiso para este envío']);
    exit();
}

// Preparar datos para inserción
$location = "Lat: $latitude, Lng: $longitude";
$notes = "Actualización automática de ubicación";

// Insertar en tracking_history
$stmt = $conn->prepare("
    INSERT INTO tracking_history (envio_id, status, location, notes, created_by) 
    VALUES (?, ?, ?, ?, ?)
");
$stmt->bind_param("isssi", $envio_id, $status, $location, $notes, $repartidor_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Ubicación actualizada correctamente',
        'tracking_id' => $conn->insert_id
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al guardar: ' . $conn->error]);
}
