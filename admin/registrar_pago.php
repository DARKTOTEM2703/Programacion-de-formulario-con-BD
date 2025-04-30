<?php
session_start();
require_once '../components/db_connection.php';

// Verificar si el usuario está autenticado como administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../php/login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Obtener datos de la factura
$stmt = $conn->prepare("
    SELECT f.*, e.name AS cliente_nombre, e.tracking_number 
    FROM facturas f
    JOIN envios e ON f.envio_id = e.id
    WHERE f.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$factura = $stmt->get_result()->fetch_assoc();

// Verificar si existe la factura
if (!$factura) {
    $_SESSION['error'] = "Factura no encontrada";
    header("Location: facturas.php");
    exit();
}

// Si ya está pagada, redirigir
if ($factura['status'] == 'pagado') {
    $_SESSION['info'] = "Esta factura ya ha sido pagada";
    header("Location: facturas.php");
    exit();
}

// Procesar el formulario de pago
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monto_pagado = $_POST['monto_pagado'] ?? 0;
    $metodo_pago = $_POST['metodo_pago'] ?? '';
    $referencia = $_POST['referencia'] ?? '';
    $fecha_pago = $_POST['fecha_pago'] ?? date('Y-m-d');

    try {
        // Iniciar transacción
        $conn->begin_transaction();

        // Actualizar estado de la factura
        $stmt = $conn->prepare("
            UPDATE facturas 
            SET status = 'pagado', fecha_pago = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("si", $fecha_pago, $id);
        $stmt->execute();

        // Registrar el pago en movimientos contables
        $stmt = $conn->prepare("
            INSERT INTO movimientos_contables (
                tipo, factura_id, concepto, monto, fecha_movimiento,
                categoria, created_by, referencia
            ) VALUES ('ingreso', ?, ?, ?, ?, 'pagos', ?, ?)
        ");

        $concepto = "Pago de factura: " . $factura['numero_factura'] . " - Método: " . $metodo_pago;
        $stmt->bind_param(
            "isdsss",
            $id,
            $concepto,
            $monto_pagado,
            $fecha_pago,
            $_SESSION['usuario_id'],
            $referencia
        );
        $stmt->execute();

        // Confirmar transacción
        $conn->commit();

        $_SESSION['success'] = "Pago registrado correctamente";
        header("Location: facturas.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error al registrar el pago: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Pago - MENDEZ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>

<body>
    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Registrar Pago de Factura</h6>
                            <a href="facturas.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Volver
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h5>Detalles de Factura</h5>
                                    <dl class="row">
                                        <dt class="col-sm-4">Número de Factura</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($factura['numero_factura']); ?>
                                        </dd>

                                        <dt class="col-sm-4">Cliente</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($factura['cliente_nombre']); ?>
                                        </dd>

                                        <dt class="col-sm-4">Tracking</dt>
                                        <dd class="col-sm-8">
                                            <?php echo htmlspecialchars($factura['tracking_number']); ?></dd>

                                        <dt class="col-sm-4">Monto Total</dt>
                                        <dd class="col-sm-8">$<?php echo number_format($factura['monto'], 2); ?></dd>

                                        <dt class="col-sm-4">Fecha Emisión</dt>
                                        <dd class="col-sm-8">
                                            <?php echo date('d/m/Y', strtotime($factura['fecha_emision'])); ?></dd>

                                        <dt class="col-sm-4">Fecha Vencimiento</dt>
                                        <dd class="col-sm-8">
                                            <?php echo date('d/m/Y', strtotime($factura['fecha_vencimiento'])); ?></dd>

                                        <dt class="col-sm-4">Estado</dt>
                                        <dd class="col-sm-8">
                                            <span
                                                class="badge <?php echo $factura['status'] == 'pendiente' ? 'bg-warning' : 'bg-secondary'; ?>">
                                                <?php echo ucfirst($factura['status']); ?>
                                            </span>
                                        </dd>
                                    </dl>
                                </div>

                                <div class="col-md-6">
                                    <h5>Registrar Pago</h5>
                                    <form method="post">
                                        <div class="mb-3">
                                            <label for="monto_pagado" class="form-label">Monto Pagado</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" step="0.01" class="form-control" id="monto_pagado"
                                                    name="monto_pagado" value="<?php echo $factura['monto']; ?>"
                                                    required>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="metodo_pago" class="form-label">Método de Pago</label>
                                            <select class="form-select" id="metodo_pago" name="metodo_pago" required>
                                                <option value="">Seleccionar...</option>
                                                <option value="Efectivo">Efectivo</option>
                                                <option value="Transferencia">Transferencia Bancaria</option>
                                                <option value="Tarjeta">Tarjeta de Crédito/Débito</option>
                                                <option value="Cheque">Cheque</option>
                                                <option value="Otro">Otro</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="referencia" class="form-label">Número de Referencia</label>
                                            <input type="text" class="form-control" id="referencia" name="referencia"
                                                placeholder="Opcional">
                                        </div>

                                        <div class="mb-3">
                                            <label for="fecha_pago" class="form-label">Fecha de Pago</label>
                                            <input type="date" class="form-control" id="fecha_pago" name="fecha_pago"
                                                value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>

                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-success">
                                                <i class="bi bi-check-circle"></i> Registrar Pago
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>