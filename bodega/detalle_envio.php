<?php
// filepath: c:\xampp\htdocs\Programacion-de-formulario-con-BD\bodega\detalle_envio.php
session_start();
require_once '../components/db_connection.php';

// Verificar que el usuario tiene rol de bodeguista
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 4) {
    header('Location: ../php/login.php');
    exit();
}

// Obtener el tracking_number desde la URL
$tracking_number = $_GET['tracking'] ?? '';
if (!$tracking_number) {
    die("Número de seguimiento no proporcionado.");
}

// Consultar los detalles del envío
$stmt = $conn->prepare("SELECT * FROM envios WHERE tracking_number = ?");
$stmt->bind_param("s", $tracking_number);
$stmt->execute();
$result = $stmt->get_result();
$envio = $result->fetch_assoc();

if (!$envio) {
    die("Envío no encontrado.");
}

// Generar el QR para el envío
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($tracking_number);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Envío - Bodega</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container py-4">
        <h1 class="h3 mb-4"><i class="bi bi-box"></i> Detalle de Envío</h1>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <strong>Envío #<?= htmlspecialchars($tracking_number) ?></strong>
            </div>
            <div class="card-body">
                <p><strong>Nombre del Cliente:</strong> <?= htmlspecialchars($envio['name']) ?></p>
                <p><strong>Correo Electrónico:</strong> <?= htmlspecialchars($envio['email']) ?></p>
                <p><strong>Estado:</strong> <?= htmlspecialchars($envio['status']) ?></p>
                <p><strong>Destino:</strong> <?= htmlspecialchars($envio['destination']) ?></p>
                <p><strong>Fecha de Registro:</strong> <?= htmlspecialchars($envio['created_at']) ?></p>

                <h5 class="mt-4">Código QR para el Repartidor</h5>
                <p>El repartidor debe escanear este QR en la bodega para recoger el paquete.</p>
                <img src="<?= $qr_url ?>" alt="QR de Envío" class="img-fluid" style="max-width: 300px;">
            </div>
        </div>

        <a href="dashboard_bodega.php" class="btn btn-secondary mt-4">
            <i class="bi bi-arrow-left"></i> Volver al Dashboard
        </a>
    </div>
</body>
</html>