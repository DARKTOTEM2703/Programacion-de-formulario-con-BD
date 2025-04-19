<?php
header('Content-Type: application/json');
require_once '../components/db_connection.php';

if (!isset($_GET['tracking_number'])) {
    echo json_encode(['success' => false, 'error' => 'Número de tracking requerido']);
    exit();
}

$tracking_number = $_GET['tracking_number'];

// Obtener información del envío
$stmt = $conn->prepare("SELECT id, status FROM envios WHERE tracking_number = ?");
$stmt->bind_param("s", $tracking_number);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'error' => 'Envío no encontrado']);
    exit();
}

$shipment = $result->fetch_assoc();
$envio_id = $shipment['id'];

// Obtener la última ubicación
$stmt = $conn->prepare("SELECT th.status, th.location, th.created_at 
                       FROM tracking_history th
                       WHERE th.envio_id = ? AND th.location LIKE '%Lat:%'
                       ORDER BY th.created_at DESC LIMIT 1");
$stmt->bind_param("i", $envio_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'error' => 'No hay ubicación disponible']);
    exit();
}

$track = $result->fetch_assoc();
$location_str = $track['location'];
preg_match('/Lat: ([\d.-]+), Lng: ([\d.-]+)/', $location_str, $matches);

if (count($matches) == 3) {
    echo json_encode([
        'success' => true,
        'status' => $track['status'],
        'timestamp' => $track['created_at'],
        'location' => [
            'lat' => floatval($matches[1]),
            'lng' => floatval($matches[2])
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Formato de ubicación inválido']);
}
