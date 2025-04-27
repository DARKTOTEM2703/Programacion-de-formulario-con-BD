<?php
require_once '../components/db_connection.php';
if (isset($_GET['envio_id'])) {
    $stmt = $conn->prepare("SELECT lat, lng FROM envios WHERE id=?");
    $stmt->bind_param("i", $_GET['envio_id']);
    $stmt->execute();
    $stmt->bind_result($lat, $lng);
    if ($stmt->fetch() && $lat && $lng) {
        // Llama a la API de Nominatim para obtener la dirección
        $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=$lat&lon=$lng";
        $opts = [
            "http" => [
                "header" => "User-Agent: MENDEZ-Tracking/1.0\r\n"
            ]
        ];
        $context = stream_context_create($opts);
        $json = @file_get_contents($url, false, $context);
        $address = '';
        if ($json) {
            $data = json_decode($json, true);
            if (isset($data['display_name'])) {
                $address = $data['display_name'];
            }
        }
        echo json_encode(['lat' => $lat, 'lng' => $lng, 'address' => $address]);
    } else {
        echo json_encode(['error' => 'No encontrado']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Falta envio_id']);
}