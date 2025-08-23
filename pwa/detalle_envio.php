<?php
session_start();
require_once '../components/db_connection.php';

// Verificar si el repartidor está autenticado
if (!isset($_SESSION['repartidor_id'])) {
    header("Location: login.php");
    exit();
}

$repartidor_id = $_SESSION['repartidor_id'];

// Verificar que se recibe un ID de envío
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$envio_id = $_GET['id'];

// Buscar el envío por su ID
$stmt = $conn->prepare("
    SELECT e.*, c.nombre_usuario as cliente_nombre, c.email as cliente_email
    FROM envios e
    LEFT JOIN usuarios c ON e.usuario_id = c.id
    WHERE e.id = ?
");
$stmt->bind_param("i", $envio_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Envío no encontrado";
    header("Location: dashboard.php");
    exit();
}

$envio = $result->fetch_assoc();

// Verificar si este envío está asignado al repartidor
$stmt = $conn->prepare("
    SELECT COUNT(*) as count FROM repartidores_envios 
    WHERE usuario_id = ? AND envio_id = ?
");
$stmt->bind_param("ii", $repartidor_id, $envio_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$is_assigned = ($row['count'] > 0);

// Obtener el historial de tracking
$stmt = $conn->prepare("
    SELECT th.*, u.nombre_usuario as usuario_nombre
    FROM tracking_history th
    LEFT JOIN usuarios u ON th.created_by = u.id
    WHERE th.envio_id = ?
    ORDER BY th.created_at DESC
");
$stmt->bind_param("i", $envio_id);
$stmt->execute();
$result = $stmt->get_result();

$tracking_history = [];
while ($row = $result->fetch_assoc()) {
    $tracking_history[] = $row;
}

// Procesar la actualización de estado
$update_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    // Verificar que el envío está asignado al repartidor
    if (!$is_assigned) {
        $update_message = '<div class="alert alert-danger">No tienes permiso para actualizar este envío</div>';
    } else {
        $new_status = $_POST['status'];
        $notes = $_POST['notes'] ?? '';

        // Si el estado es "Entregado", verificar que se ha proporcionado una firma
        if ($new_status === 'Entregado' && (!isset($_POST['signature']) || empty($_POST['signature']))) {
            $update_message = '<div class="alert alert-danger">Se requiere la firma del destinatario para marcar como entregado</div>';
        } else {
            // Obtener ubicación (en una app real se usaría geolocalización)
            $location = $_POST['location'] ?? '';

            // Guardar la firma si existe
            $signature_path = '';
            if (isset($_POST['signature']) && !empty($_POST['signature'])) {
                $signature_data = $_POST['signature'];
                $signature_data = str_replace('data:image/png;base64,', '', $signature_data);
                $signature_data = str_replace(' ', '+', $signature_data);
                $signature_data = base64_decode($signature_data);

                $signature_filename = 'signature_' . $envio_id . '_' . time() . '.png';
                $signature_path = '../uploads/signatures/' . $signature_filename;

                // Asegurarse de que el directorio existe
                if (!file_exists('../uploads/signatures')) {
                    mkdir('../uploads/signatures', 0777, true);
                }

                file_put_contents($signature_path, $signature_data);

                // Guardar la ruta relativa en la nota
                $signature_path = 'uploads/signatures/' . $signature_filename;
                $notes .= "\nFirma: " . $signature_path;
            }

            // Iniciar transacción
            $conn->begin_transaction();

            try {
                // Actualizar estado del envío
                $stmt = $conn->prepare("UPDATE envios SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $new_status, $envio_id);
                $stmt->execute();

                // Registrar en el historial de tracking
                $stmt = $conn->prepare("
                    INSERT INTO tracking_history (envio_id, status, location, notes, created_by) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("isssi", $envio_id, $new_status, $location, $notes, $repartidor_id);
                $stmt->execute();

                $conn->commit();

                // Actualizar la variable del envío con el nuevo estado
                $envio['status'] = $new_status;

                $update_message = '<div class="alert alert-success">Estado actualizado correctamente</div>';

                // Recargar el historial
                $stmt = $conn->prepare("
                    SELECT th.*, u.nombre_usuario as usuario_nombre
                    FROM tracking_history th
                    LEFT JOIN usuarios u ON th.created_by = u.id
                    WHERE th.envio_id = ?
                    ORDER BY th.created_at DESC
                ");
                $stmt->bind_param("i", $envio_id);
                $stmt->execute();
                $result = $stmt->get_result();

                $tracking_history = [];
                while ($row = $result->fetch_assoc()) {
                    $tracking_history[] = $row;
                }
            } catch (Exception $e) {
                $conn->rollback();
                $update_message = '<div class="alert alert-danger">Error al actualizar: ' . $e->getMessage() . '</div>';
            }

            // Después de actualizar a "Entregado" y después del commit
            if ($new_status === 'Entregado') {
                // Generar factura automáticamente
                require_once '../components/invoice_handler.php';
                $invoice_number = generateInvoice($envio_id);

                if ($invoice_number) {
                    $update_message .= '<div class="alert alert-info mt-2">Factura generada: ' . $invoice_number . '</div>';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Envío - App Repartidores</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <style>
    .signature-container {
        width: 100%;
        height: 200px;
        border: 1px solid #ccc;
        background-color: #fff;
        margin-bottom: 10px;
    }

    .timeline {
        position: relative;
        padding-left: 30px;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 7px;
        top: 0;
        height: 100%;
        width: 2px;
        background-color: #dee2e6;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 25px;
    }

    .timeline-marker {
        position: absolute;
        left: -30px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background-color: #0d6efd;
        border: 2px solid #fff;
        top: 5px;
    }

    .status-Entregado .timeline-marker {
        background-color: #198754;
    }

    .status-Cancelado .timeline-marker {
        background-color: #dc3545;
    }

    .timeline-date {
        color: #6c757d;
        font-size: 0.85rem;
    }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-arrow-left me-2"></i> Detalle
            </a>
        </div>
    </nav>

    <div class="container mt-4 mb-5 pb-5">
        <!-- Mensaje de actualización si existe -->
        <?php echo $update_message; ?>

        <!-- Información del envío -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Envío #<?php echo $envio['tracking_number']; ?></h5>
                    <?php if ($envio['urgent']): ?>
                    <span class="badge bg-danger">URGENTE</span>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <span class="badge bg-<?php
                                            switch ($envio['status']) {
                                                case 'Entregado':
                                                    echo 'success';
                                                    break;
                                                case 'En tránsito':
                                                    echo 'primary';
                                                    break;
                                                case 'Procesando':
                                                    echo 'info';
                                                    break;
                                                case 'Cancelado':
                                                    echo 'danger';
                                                    break;
                                                default:
                                                    echo 'secondary';
                                            }
                                            ?> mb-3"><?php echo $envio['status']; ?></span>
                </div>

                <div class="row">
                    <div class="col-6">
                        <p class="mb-1"><strong>Origen:</strong></p>
                        <p class="text-muted small"><?php echo htmlspecialchars($envio['origin']); ?></p>
                    </div>
                    <div class="col-6">
                        <p class="mb-1"><strong>Destino:</strong></p>
                        <p class="text-muted small"><?php echo htmlspecialchars($envio['destination']); ?></p>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-6">
                        <p class="mb-1"><strong>Remitente:</strong></p>
                        <p class="text-muted small"><?php echo htmlspecialchars($envio['name']); ?></p>
                    </div>
                    <div class="col-6">
                        <p class="mb-1"><strong>Teléfono remitente:</strong></p>
                        <p class="text-muted small"><?php echo htmlspecialchars($envio['phone']); ?></p>
                    </div>
                </div>

                <hr>

                <p class="mb-1"><strong>Detalles:</strong></p>
                <p class="text-muted small">
                    <strong>Peso:</strong> <?php echo htmlspecialchars($envio['weight']); ?> kg<br>
                    <strong>Descripción:</strong> <?php echo htmlspecialchars($envio['description']); ?>
                </p>

                <?php if ($is_assigned && $envio['status'] !== 'Entregado' && $envio['status'] !== 'Cancelado'): ?>
                <div class="d-grid gap-2 mt-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#updateStatusModal">
                        <i class="bi bi-arrow-clockwise"></i> Actualizar estado
                    </button>
                    <a href="https://www.google.com/maps/search/<?php echo urlencode($envio['recipient_address']); ?>"
                        class="btn btn-outline-secondary" target="_blank">
                        <i class="bi bi-geo-alt"></i> Ver en mapa
                    </a>
                    <a href="tel:<?php echo preg_replace('/[^0-9]/', '', $envio['recipient_phone']); ?>"
                        class="btn btn-outline-success">
                        <i class="bi bi-telephone"></i> Llamar al destinatario
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Rastreo en vivo -->
        <?php if ($is_assigned && $envio['status'] !== 'Entregado' && $envio['status'] !== 'Cancelado'): ?>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-geo-alt-fill me-2"></i> Rastreo en vivo
            </div>
            <div class="card-body">
                <p class="mb-3">Comparte tu ubicación en tiempo real con el cliente para que pueda seguir el envío.</p>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <button id="btnIniciarRastreo" class="btn btn-success">
                        <i class="bi bi-play-fill me-2"></i> Iniciar rastreo
                    </button>
                    <button id="btnDetenerRastreo" class="btn btn-danger d-none">
                        <i class="bi bi-stop-fill me-2"></i> Detener rastreo
                    </button>
                </div>

                <div class="mt-3">
                    <p id="statusRastreo" class="mb-1 fw-bold text-muted">Rastreo no iniciado</p>
                    <p id="coordenadas" class="small text-muted">--</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Historial de tracking -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Historial de seguimiento</h5>
            </div>
            <div class="card-body">
                <?php if (count($tracking_history) > 0): ?>
                <div class="timeline">
                    <?php foreach ($tracking_history as $track): ?>
                    <div class="timeline-item status-<?php echo $track['status']; ?>">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <div class="d-flex justify-content-between">
                                <h6 class="mb-1"><?php echo htmlspecialchars($track['status']); ?></h6>
                                <span class="timeline-date">
                                    <?php echo date('d/m/Y H:i', strtotime($track['created_at'])); ?>
                                </span>
                            </div>
                            <?php if ($track['location']): ?>
                            <p class="text-muted small mb-1">
                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($track['location']); ?>
                            </p>
                            <?php endif; ?>
                            <?php if ($track['notes']): ?>
                            <p class="text-muted small mb-0">
                                <?php echo nl2br(htmlspecialchars($track['notes'])); ?>
                            </p>
                            <?php endif; ?>
                            <p class="small text-muted mt-1">
                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($track['usuario_nombre']); ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center text-muted">
                    <p>No hay historial de seguimiento disponible</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- PIN de Entrega -->
        <?php if ($envio['status'] === 'En tránsito'): ?>
        <form id="entregarPedidoForm" method="POST">
            <div class="mb-3">
                <label for="pin_seguro" class="form-label">PIN de Entrega</label>
                <input type="text" class="form-control" id="pin_seguro" name="pin_seguro" maxlength="6" required>
            </div>
            <button type="submit" class="btn btn-success">Validar y Entregar</button>
        </form>
        <div id="pinResult" class="mt-3"></div>

        <script>
            document.getElementById('entregarPedidoForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                const pin = document.getElementById('pin_seguro').value.trim();
                const res = await fetch('../api/validate_pin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ tracking_number: '<?= $envio['tracking_number'] ?>', pin_seguro: pin })
                });
                const data = await res.json();
                const resultDiv = document.getElementById('pinResult');
                if (data.success) {
                    resultDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                    setTimeout(() => location.reload(), 2000);
                } else {
                    resultDiv.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                }
            });
        </script>
        <?php endif; ?>
    </div>

    <!-- Modal para actualizar estado -->
    <?php if ($is_assigned): ?>
    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">Actualizar estado del envío</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updateStatusForm" method="post" action="">
                        <div class="mb-3">
                            <label for="status" class="form-label">Nuevo estado</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="En tránsito"
                                    <?php echo ($envio['status'] === 'En tránsito') ? 'selected' : ''; ?>>En tránsito
                                </option>
                                <option value="En punto de entrega">En punto de entrega</option>
                                <option value="Intentado entregar">Intentado entregar</option>
                                <option value="Entregado">Entregado</option>
                                <option value="No entregado">No entregado</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="location" class="form-label">Ubicación</label>
                            <input type="text" class="form-control" id="location" name="location"
                                placeholder="Dirección actual">
                            <div class="form-text">Deja en blanco para usar tu ubicación actual</div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notas</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>

                        <div id="signature-container" class="mb-3" style="display: none;">
                            <label class="form-label">Firma del destinatario</label>
                            <div class="signature-container" id="signature-pad"></div>
                            <input type="hidden" name="signature" id="signature-data">
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                    id="clear-signature">Borrar</button>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="update_status" class="btn btn-primary">Actualizar
                                estado</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Barra de navegación inferior -->
    <nav class="navbar fixed-bottom navbar-dark bg-primary">
        <div class="container-fluid">
            <div class="row w-100">
                <div class="col text-center">
                    <a href="dashboard.php" class="nav-link">
                        <i class="bi bi-house-door"></i><br>
                        <small>Inicio</small>
                    </a>
                </div>
                <div class="col text-center">
                    <a href="escanear.php" class="nav-link">
                        <i class="bi bi-qr-code-scan"></i><br>
                        <small>Escanear</small>
                    </a>
                </div>
                <div class="col text-center">
                    <a href="mapa.php" class="nav-link">
                        <i class="bi bi-geo-alt"></i><br>
                        <small>Mapa</small>
                    </a>
                </div>
                <div class="col text-center">
                    <a href="perfil.php" class="nav-link">
                        <i class="bi bi-person"></i><br>
                        <small>Perfil</small>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script src="assets/js/pwa-init.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const statusSelect = document.getElementById('status');
        const signatureContainer = document.getElementById('signature-container');
        let signaturePad = null;

        // Mostrar/ocultar firma según el estado seleccionado
        if (statusSelect) {
            statusSelect.addEventListener('change', function() {
                if (this.value === 'Entregado') {
                    signatureContainer.style.display = 'block';
                    initSignaturePad();
                } else {
                    signatureContainer.style.display = 'none';
                }
            });

            // Inicializar según el valor actual
            if (statusSelect.value === 'Entregado') {
                signatureContainer.style.display = 'block';
                initSignaturePad();
            }
        }

        function initSignaturePad() {
            if (signaturePad !== null) return;

            const canvas = document.getElementById('signature-pad');
            signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgb(255, 255, 255)'
            });

            // Ajustar tamaño del canvas
            function resizeCanvas() {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext("2d").scale(ratio, ratio);
                signaturePad.clear(); // otherwise isEmpty() might return incorrect value
            }

            window.onresize = resizeCanvas;
            resizeCanvas();

            // Botón para borrar firma
            document.getElementById('clear-signature').addEventListener('click', function() {
                signaturePad.clear();
            });

            // Al enviar el formulario, guardar la firma
            document.getElementById('updateStatusForm').addEventListener('submit', function() {
                if (statusSelect.value === 'Entregado' && signaturePad.isEmpty()) {
                    alert('Por favor, solicita la firma del destinatario');
                    event.preventDefault();
                    return false;
                }

                if (!signaturePad.isEmpty() && statusSelect.value === 'Entregado') {
                    document.getElementById('signature-data').value = signaturePad.toDataURL();
                }
            });
        }

        // Obtener ubicación actual
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                // Opcional: usar la API de geocodificación inversa para obtener la dirección
                // Por simplicidad, solo guardaremos las coordenadas
                document.getElementById('location').placeholder =
                    `Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}`;
                document.getElementById('location').value =
                    `Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}`;
            }, function(error) {
                console.log('Error al obtener ubicación:', error);
            });
        }
    });
    </script>
    <div id="map" style="height: 400px;"></div>
    <script>
    const map = L.map('map').setView([0, 0], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);
    const marker = L.marker([0, 0]).addTo(map);

    function actualizarMapa() {
        fetch('/Programacion-de-formulario-con-BD/api/obtener_ubicacion.php?envio_id=<?php echo $envio_id; ?>')
            .then(r => r.json())
            .then(data => {
                if (data.lat && data.lng) {
                    marker.setLatLng([data.lat, data.lng]);
                    map.setView([data.lat, data.lng], 15);
                }
            });
    }
    setInterval(actualizarMapa, 10000); // cada 10 segundos
    actualizarMapa();
    </script>
</body>

</html>