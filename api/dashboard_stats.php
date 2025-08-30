<?php
require_once '../components/db_connection.php';

header('Content-Type: application/json');

// Obtener estadísticas
$stats = $conn->query("SELECT * FROM vista_dashboard_estadisticas")->fetch_assoc();

// Obtener envíos pendientes
$pendientes = [];
$res = $conn->query("SELECT * FROM vista_bodega_envios_completa WHERE status = 'Recibido bodega'");
while ($row = $res->fetch_assoc()) {
    $pendientes[] = $row;
}

// Paquetes recibidos hoy
$hoy = date('Y-m-d');
$recibidos_hoy = [];
$res_hoy = $conn->query("SELECT tracking_number, TIME(created_at) as hora, status FROM envios WHERE DATE(created_at) = '$hoy' AND status = 'Recibido bodega' ORDER BY created_at DESC");
while ($row = $res_hoy->fetch_assoc()) {
    $recibidos_hoy[] = $row;
}

echo json_encode([
    'stats' => $stats,
    'pendingShipments' => $pendientes,
    'todayPackages' => $recibidos_hoy,
]);