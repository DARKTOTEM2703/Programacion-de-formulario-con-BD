<?php
session_start();
include '../components/db_connection.php';

// Solo conductores y operadores pueden escanear
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol_id'] != 2 && $_SESSION['rol_id'] != 3)) {
    header("Location: login.php");
    exit();
}

// Procesar escaneo de código QR/barcode
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tracking_number = $_POST['tracking_number'];
    $new_status = $_POST['status'];
    $location = $_POST['location'] ?? '';

    // Actualizar estado
    $stmt = $conn->prepare("UPDATE envios SET status = ?, last_location = ?, updated_at = NOW() WHERE tracking_number = ?");
    $stmt->bind_param("sss", $new_status, $location, $tracking_number);
    $stmt->execute();

    // Registrar historial de seguimiento
    $stmt = $conn->prepare("INSERT INTO tracking_history (envio_id, status, location, created_by) VALUES ((SELECT id FROM envios WHERE tracking_number = ?), ?, ?, ?)");
    $stmt->bind_param("sssi", $tracking_number, $new_status, $location, $_SESSION['usuario_id']);
    $stmt->execute();
}
