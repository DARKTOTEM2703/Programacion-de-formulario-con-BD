<?php
require_once '../components/db_connection.php';
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['envio_id'], $data['lat'], $data['lng'])) {
    $stmt = $conn->prepare("UPDATE envios SET lat=?, lng=? WHERE id=?");
    $stmt->bind_param("ddi", $data['lat'], $data['lng'], $data['envio_id']);
    $stmt->execute();
    echo json_encode(['ok' => true]);
} else {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
}
