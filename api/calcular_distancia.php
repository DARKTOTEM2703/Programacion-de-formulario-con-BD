<?php
header('Content-Type: application/json');
if (!isset($_GET['origin']) || !isset($_GET['destination'])) {
    echo json_encode(['error' => 'Faltan parámetros']);
    exit;
}
$origin = $_GET['origin'];
$destination = $_GET['destination'];
$url = "https://router.project-osrm.org/route/v1/driving/$origin;$destination?overview=false";
$response = file_get_contents($url);
echo $response;
