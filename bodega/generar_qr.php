<?php

session_start();
require_once '../components/db_connection.php';
require_once '../components/auth_helper.php';

$auth = inicializarAuth($conn);
$auth->verificarAcceso('bodega', 'recibir_paquetes');

$mensaje = '';
$tipo_mensaje = '';
$qr_data = null;
$envio = null;

if (isset($_GET['id'])) {
    $envio_id = (int)$_GET['id'];
    
    // Obtener datos del envío
    $stmt = $conn->prepare("
        SELECT e.*, u.nombre_usuario as cliente_nombre, re.usuario_id as repartidor_id,
               ur.nombre_usuario as repartidor_nombre
        FROM envios e
        LEFT JOIN usuarios u ON e.usuario_id = u.id
        LEFT JOIN repartidores_envios re ON e.id = re.envio_id
        LEFT JOIN usuarios ur ON re.usuario_id = ur.id
        WHERE e.id = ?
    ");
    $stmt->bind_param("i", $envio_id);
    $stmt->execute();
    $envio = $stmt->get_result()->fetch_assoc();
    
    if (!$envio) {
        $mensaje = "Envío no encontrado";
        $tipo_mensaje = "danger";
    } else {
        // Generar datos para el QR
        $stmt = $conn->prepare("CALL sp_generar_qr_envio(?, @p_qr_data, @p_ok, @p_msg)");
        $stmt->bind_param("i", $envio_id);
        $stmt->execute();
        $stmt->close();
        
        $result = $conn->query("SELECT @p_qr_data AS qr_data, @p_ok AS ok, @p_msg AS msg")->fetch_assoc();
        
        if ($result['ok']) {
            $qr_data = $result['qr_data'];
            $mensaje = "QR generado correctamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al generar QR: " . $result['msg'];
            $tipo_mensaje = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar QR - MENDEZ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .qr-container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .qr-code {
            text-align: center;
            padding: 20px 0;
        }
        .tracking-box {
            background: #f1f3f5;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-size: 1.2rem;
            font-family: monospace;
            margin: 15px 0;
        }
        .badge-urgent {
            position: absolute;
            top: -10px;
            right: -10px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3"><i class="bi bi-qr-code-scan me-2"></i> Código QR para Envío</h1>
            <span class="badge bg-primary"><?= $auth->getRolNombre() ?></span>
        </div>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-<?= $tipo_mensaje ?>"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>
        
        <?php if ($envio && $qr_data): ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Detalles del Envío</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <p class="mb-1"><strong>Tracking:</strong></p>
                                <div class="tracking-box"><?= htmlspecialchars($envio['tracking_number']) ?></div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-6">
                                    <p class="mb-1"><strong>Cliente:</strong> <?= htmlspecialchars($envio['cliente_nombre']) ?></p>
                                    <p class="mb-1"><strong>Estado:</strong> <span class="badge bg-secondary"><?= htmlspecialchars($envio['status']) ?></span></p>
                                </div>
                                <div class="col-6">
                                    <p class="mb-1"><strong>Tipo:</strong> <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $envio['package_type']))) ?></p>
                                    <p class="mb-1"><strong>Peso:</strong> <?= htmlspecialchars(number_format($envio['weight'], 2)) ?> kg</p>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <p class="mb-1"><strong>Destino:</strong></p>
                                <p class="bg-light p-2 rounded small"><?= htmlspecialchars($envio['destination']) ?></p>
                            </div>
                            
                            <?php if ($envio['repartidor_id']): ?>
                                <div class="mb-3">
                                    <p class="mb-1"><strong>Repartidor asignado:</strong> <?= htmlspecialchars($envio['repartidor_nombre']) ?></p>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>No hay repartidor asignado
                                </div>
                            <?php endif; ?>
                            
                            <a href="dashboard_bodega.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Volver
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Código QR</h5>
                        </div>
                        <div class="card-body">
                            <div class="qr-container">
                                <?php if ($envio['urgent']): ?>
                                    <span class="badge bg-danger badge-urgent">URGENTE</span>
                                <?php endif; ?>
                                
                                <div class="qr-code">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($qr_data) ?>" 
                                         alt="QR Code" class="img-fluid">
                                </div>
                                
                                <div class="text-center mt-3">
                                    <p class="small text-muted">Escanea este código con la aplicación del repartidor</p>
                                    
                                    <div class="btn-group">
                                        <button class="btn btn-primary" onclick="window.print()">
                                            <i class="bi bi-printer me-2"></i>Imprimir
                                        </button>
                                        <button class="btn btn-outline-primary" onclick="downloadQR()">
                                            <i class="bi bi-download me-2"></i>Descargar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>Selecciona un envío para generar su código QR
            </div>
            
            <a href="dashboard_bodega.php" class="btn btn-primary">
                <i class="bi bi-arrow-left me-2"></i>Volver al Dashboard
            </a>
        <?php endif; ?>
    </div>

    <script>
        function downloadQR() {
            const imgSrc = document.querySelector('.qr-code img').src;
            const fileName = 'qr-<?= htmlspecialchars($envio['tracking_number'] ?? 'envio') ?>.png';
            
            // Crear un enlace temporal para la descarga
            const a = document.createElement('a');
            a.href = imgSrc;
            a.download = fileName;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    </script>
</body>
</html>