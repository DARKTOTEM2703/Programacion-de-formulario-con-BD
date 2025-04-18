<?php
include 'components/db_connection.php';
$tracking_number = isset($_GET['tracking']) ? $_GET['tracking'] : '';
$shipment = null;
$history = [];

if (!empty($tracking_number)) {
    // Obtener información del envío
    $stmt = $conn->prepare("SELECT * FROM envios WHERE tracking_number = ?");
    $stmt->bind_param("s", $tracking_number);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $shipment = $result->fetch_assoc();

        // Obtener historial de seguimiento
        $stmt = $conn->prepare("SELECT th.*, u.nombre_usuario FROM tracking_history th 
                               LEFT JOIN usuarios u ON th.created_by = u.id 
                               WHERE th.envio_id = ? ORDER BY th.created_at DESC");
        $stmt->bind_param("i", $shipment['id']);
        $stmt->execute();
        $history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <title>Rastrear Envío - MENDEZ</title>
    <!-- CSS y meta tags aquí -->
</head>

<body>
    <div class="tracking-container">
        <h1>Rastrear Envío</h1>

        <form method="GET" action="" class="tracking-form">
            <div class="input-group mb-3">
                <input type="text" name="tracking" class="form-control" placeholder="Ingresa tu número de seguimiento"
                    value="<?php echo htmlspecialchars($tracking_number); ?>">
                <button class="btn btn-primary" type="submit">Rastrear</button>
            </div>
        </form>

        <?php if ($shipment): ?>
            <!-- Mostrar detalles del envío y su historial -->
        <?php elseif (!empty($tracking_number)): ?>
            <div class="alert alert-danger">No se encontró el envío con el número de seguimiento proporcionado.</div>
        <?php endif; ?>
    </div>
</body>

</html>