<?php
session_start();
require_once '../components/db_connection.php';

// Verificar si el usuario está autenticado como administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../php/login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Obtener datos completos de la factura
$stmt = $conn->prepare("
    SELECT f.*, 
           e.name AS cliente_nombre, 
           e.email AS cliente_email, 
           e.phone AS cliente_telefono,
           e.tracking_number,
           e.origin AS origen,
           e.destination AS destino,
           e.description AS descripcion,
           e.value AS valor_declarado,
           e.estimated_cost AS costo_estimado,
           u.nombre_usuario
    FROM facturas f
    JOIN envios e ON f.envio_id = e.id
    LEFT JOIN usuarios u ON e.usuario_id = u.id
    WHERE f.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Factura no encontrada";
    header("Location: facturas.php");
    exit();
}

$factura = $result->fetch_assoc();

// Obtener historial de pagos
$stmt = $conn->prepare("
    SELECT mc.* 
    FROM movimientos_contables mc 
    WHERE mc.factura_id = ? AND mc.tipo = 'ingreso'
    ORDER BY mc.fecha_movimiento DESC
");
$stmt->bind_param("i", $id);
$stmt->execute();
$pagos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Factura - MENDEZ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .factura-header {
            background-color: #f8f9fa;
            border-bottom: 2px solid #0057B8;
            padding: 20px;
            border-radius: 5px 5px 0 0;
        }

        .factura-body {
            padding: 20px;
        }

        .factura-footer {
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 0 0 5px 5px;
        }

        .badge-lg {
            font-size: 1rem;
            padding: 0.5rem 0.7rem;
        }

        .actions-bar {
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }
    </style>
</head>

<body>
    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="actions-bar d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-receipt"></i> Detalle de Factura</h1>
                <div class="btn-group">
                    <a href="facturas.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                    <?php if ($factura['status'] == 'pendiente'): ?>
                        <a href="registrar_pago.php?id=<?php echo $id; ?>" class="btn btn-success">
                            <i class="bi bi-cash"></i> Registrar Pago
                        </a>
                    <?php endif; ?>
                    <a href="factura_pdf.php?id=<?php echo $id; ?>" class="btn btn-primary" target="_blank">
                        <i class="bi bi-file-pdf"></i> Ver PDF
                    </a>
                    <button class="btn btn-info text-white" onclick="enviarPorEmail()">
                        <i class="bi bi-envelope"></i> Enviar por Email
                    </button>
                    <?php if ($factura['status'] != 'cancelado'): ?>
                        <button class="btn btn-danger" onclick="confirmarCancelacion()">
                            <i class="bi bi-x-circle"></i> Cancelar Factura
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success'];
                    unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error'];
                    unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Detalles de la factura -->
                    <div class="card shadow mb-4">
                        <div class="factura-header d-flex justify-content-between">
                            <div>
                                <h3 class="mb-1">Factura: <?php echo htmlspecialchars($factura['numero_factura']); ?>
                                </h3>
                                <p class="text-muted mb-0">
                                    Fecha de emisión: <?php echo date('d/m/Y', strtotime($factura['fecha_emision'])); ?>
                                </p>
                            </div>
                            <div class="text-end">
                                <h5>Estado:</h5>
                                <span class="badge badge-lg <?php
                                                            echo match ($factura['status']) {
                                                                'pendiente' => 'bg-warning',
                                                                'pagado' => 'bg-success',
                                                                'cancelado' => 'bg-danger',
                                                                'vencido' => 'bg-secondary',
                                                                default => 'bg-info'
                                                            };
                                                            ?>">
                                    <?php echo ucfirst(htmlspecialchars($factura['status'])); ?>
                                </span>
                            </div>
                        </div>
                        <div class="factura-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h5>Cliente</h5>
                                    <p class="mb-1"><?php echo htmlspecialchars($factura['cliente_nombre']); ?></p>
                                    <p class="mb-1">Email: <?php echo htmlspecialchars($factura['cliente_email']); ?>
                                    </p>
                                    <p class="mb-1">Teléfono:
                                        <?php echo htmlspecialchars($factura['cliente_telefono']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h5>Detalles de Envío</h5>
                                    <p class="mb-1">Tracking:
                                        <?php echo htmlspecialchars($factura['tracking_number']); ?></p>
                                    <p class="mb-1">Origen: <?php echo htmlspecialchars($factura['origen']); ?></p>
                                    <p class="mb-1">Destino: <?php echo htmlspecialchars($factura['destino']); ?></p>
                                </div>
                            </div>

                            <h5>Detalles del Paquete</h5>
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Descripción</th>
                                        <th>Valor Declarado</th>
                                        <th>Costo de Envío</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?php echo htmlspecialchars($factura['descripcion']); ?></td>
                                        <td>$<?php echo number_format($factura['valor_declarado'], 2); ?></td>
                                        <td>$<?php echo number_format($factura['costo_estimado'], 2); ?></td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2" class="text-end fw-bold">Total:</td>
                                        <td class="fw-bold">$<?php echo number_format($factura['monto'], 2); ?></td>
                                    </tr>
                                </tfoot>
                            </table>

                            <?php if ($factura['status'] == 'pagado'): ?>
                                <div class="alert alert-success mt-4">
                                    <h5><i class="bi bi-check-circle"></i> Factura Pagada</h5>
                                    <p>Esta factura fue pagada el
                                        <?php echo date('d/m/Y', strtotime($factura['fecha_pago'])); ?></p>
                                </div>
                            <?php elseif ($factura['status'] == 'vencido'): ?>
                                <div class="alert alert-warning mt-4">
                                    <h5><i class="bi bi-exclamation-triangle"></i> Factura Vencida</h5>
                                    <p>Esta factura venció el
                                        <?php echo date('d/m/Y', strtotime($factura['fecha_vencimiento'])); ?></p>
                                </div>
                            <?php elseif ($factura['status'] == 'cancelado'): ?>
                                <div class="alert alert-danger mt-4">
                                    <h5><i class="bi bi-x-circle"></i> Factura Cancelada</h5>
                                    <p>Esta factura ha sido cancelada.</p>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mt-4">
                                    <h5><i class="bi bi-info-circle"></i> Información de Pago</h5>
                                    <p>Esta factura debe ser pagada antes del
                                        <?php echo date('d/m/Y', strtotime($factura['fecha_vencimiento'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="factura-footer">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-0">
                                        <small>CFDI disponible en:</small><br>
                                        <?php if (!empty($factura['cfdi_xml'])): ?>
                                            <a href="../<?php echo $factura['cfdi_xml']; ?>" download
                                                class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-file-earmark-code"></i> XML
                                            </a>
                                        <?php endif; ?>

                                        <?php if (!empty($factura['cfdi_pdf'])): ?>
                                            <a href="../<?php echo $factura['cfdi_pdf']; ?>" download
                                                class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-file-earmark-pdf"></i> PDF
                                            </a>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="col-md-6 text-end">
                                    <p class="mb-0">
                                        <small>Factura generada por
                                            <?php echo htmlspecialchars($factura['nombre_usuario'] ?? 'Sistema'); ?></small>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Historial de pagos -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Historial de Pagos</h6>
                        </div>
                        <div class="card-body">
                            <?php if (count($pagos) > 0): ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Monto</th>
                                            <th>Referencia</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pagos as $pago): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($pago['fecha_movimiento'])); ?></td>
                                                <td>$<?php echo number_format($pago['monto'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($pago['referencia'] ?? '-'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td class="fw-bold">Total Pagado:</td>
                                            <td colspan="2" class="fw-bold">
                                                $<?php
                                                    $total_pagado = array_reduce($pagos, function ($carry, $item) {
                                                        return $carry + $item['monto'];
                                                    }, 0);
                                                    echo number_format($total_pagado, 2);
                                                    ?>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-info-circle fs-1 text-muted"></i>
                                    <p class="mt-2">No hay pagos registrados para esta factura</p>

                                    <?php if ($factura['status'] == 'pendiente'): ?>
                                        <a href="registrar_pago.php?id=<?php echo $id; ?>" class="btn btn-primary">
                                            <i class="bi bi-cash"></i> Registrar Pago
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recordatorios y acciones -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Acciones Rápidas</h6>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <?php if ($factura['status'] == 'pendiente'): ?>
                                    <button class="list-group-item list-group-item-action" onclick="enviarRecordatorio()">
                                        <i class="bi bi-bell"></i> Enviar recordatorio de pago
                                    </button>
                                <?php endif; ?>
                                <button class="list-group-item list-group-item-action" onclick="notaCredito()">
                                    <i class="bi bi-journal-text"></i> Generar nota de crédito
                                </button>
                                <button class="list-group-item list-group-item-action" onclick="descargarDocumentos()">
                                    <i class="bi bi-file-earmark-arrow-down"></i> Descargar todos los documentos
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function enviarPorEmail() {
            // Confirmar envío de email
            if (confirm('¿Desea enviar esta factura por correo electrónico?')) {
                fetch('enviar_factura_email.php?id=<?php echo $id; ?>')
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al enviar el correo electrónico');
                    });
            }
        }

        function confirmarCancelacion() {
            if (confirm('¿Está seguro que desea cancelar esta factura? Esta acción no se puede deshacer.')) {
                window.location.href = 'cancelar_factura.php?id=<?php echo $id; ?>';
            }
        }

        function enviarRecordatorio() {
            fetch('enviar_recordatorio.php?id=<?php echo $id; ?>')
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al enviar el recordatorio');
                });
        }

        function notaCredito() {
            alert('Funcionalidad de nota de crédito en desarrollo');
        }

        function descargarDocumentos() {
            alert('Funcionalidad de descarga de documentos en desarrollo');
        }
    </script>
</body>

</html>