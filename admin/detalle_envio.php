<?php
session_start();
require_once '../components/db_connection.php';

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../php/login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$rol_id = $_SESSION['rol_id'];
$is_admin = ($rol_id == 1);
$is_client = ($rol_id == 2);
$is_delivery = ($rol_id == 3);

// Definir ruta de regreso según el rol
$back_url = match($rol_id) {
    1 => 'envios.php',
    2 => '../php/dashboard.php',
    3 => '../pwa/dashboard.php',
    default => '../php/login.php'
};

// Comprobar si se proporcionó un ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    $_SESSION['error'] = "ID de envío inválido";
    header("Location: $back_url");
    exit();
}

// Obtener detalles del envío usando el procedimiento almacenado
//  SELECT e.*, 
//            u.nombre_usuario as cliente_nombre, 
//            u.email as cliente_email,
//            ur.nombre_usuario as repartidor_nombre,
//            re.fecha_asignacion
//     FROM envios e
//     LEFT JOIN usuarios u ON e.usuario_id = u.id
//     LEFT JOIN repartidores_envios re ON e.id = re.envio_id
//     LEFT JOIN usuarios ur ON re.usuario_id = ur.id
//     WHERE e.id = ?

// Obtener detalles del envío usando el procedimiento almacenado
$stmt = $conn->prepare("CALL sp_obtener_detalle_envio(?, ?, ?)");
$stmt->bind_param("iii", $id, $rol_id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Envío no encontrado o no tienes permisos para verlo";
    header("Location: $back_url");
    exit();
}

$envio = $result->fetch_assoc();
$stmt->close();
$conn->next_result(); // Necesario para evitar "Commands out of sync"

// Obtener historial de tracking usando el procedimiento almacenado
$stmt = $conn->prepare("CALL sp_obtener_tracking_historial(?)");
$stmt->bind_param("i", $id);
$stmt->execute();
$tracking_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->next_result(); // Limpiar resultados para siguientes consultas

// Si es administrador, obtener repartidores activos para posible asignación
if ($is_admin) {
    $stmt = $conn->prepare("CALL sp_obtener_repartidores_activos()");
    $stmt->execute();
    $repartidores_activos = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $repartidores_activos[] = $row;
    }
    $stmt->close();
    $conn->next_result();
}

// Generar QR para este envío
$qr_data = $envio['tracking_number']; 
$qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qr_data);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Envío #<?php echo htmlspecialchars($envio['tracking_number']); ?> - MENDEZ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Usar CSS según rol -->
    <link rel="stylesheet" href="<?php echo $is_admin ? 'css/admin.css' : '../css/main.css'; ?>">
    <style>
        .qr-container {
            text-align: center;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: white;
        }
        
        .tracking-timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .tracking-timeline::before {
            content: '';
            position: absolute;
            top: 0;
            left: 11px;
            height: 100%;
            width: 2px;
            background-color: #ddd;
        }
        
        .tracking-item {
            position: relative;
            padding-bottom: 25px;
        }
        
        .tracking-item:last-child {
            padding-bottom: 0;
        }
        
        .tracking-icon {
            position: absolute;
            left: -30px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            text-align: center;
            background-color: white;
            border: 2px solid #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .tracking-icon.active {
            background-color: #4caf50;
            border-color: #4caf50;
        }
        
        .tracking-content {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        
        .tracking-date {
            color: #777;
            font-size: 0.85rem;
        }
        
        .badge-urgent {
            position: absolute;
            top: -10px;
            right: -10px;
        }
        
        @media (max-width: 768px) {
            .tracking-timeline {
                padding-left: 25px;
            }
            
            .tracking-icon {
                left: -25px;
                width: 18px;
                height: 18px;
            }
        }
    </style>
</head>

<body>
    <?php if ($is_admin): include 'components/sidebar.php'; endif; ?>

    <div class="<?php echo $is_admin ? 'main-content' : 'container mt-5 pt-3'; ?>">
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <a href="<?php echo $back_url; ?>" class="text-decoration-none me-2">
                        <i class="bi bi-arrow-left-circle"></i>
                    </a>
                    Detalle de Envío
                </h1>
                <div>
                    <?php if ($is_admin && $envio['status'] == 'Procesando'): ?>
                    <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#asignarRepartidorModal">
                        <i class="bi bi-person-plus"></i> Asignar Repartidor
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($is_admin || $is_delivery): ?>
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear"></i> Acciones
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if ($envio['status'] == 'Procesando'): ?>
                                <li><button class="dropdown-item" onclick="cambiarEstado(<?php echo $id; ?>, 'En tránsito')">
                                    <i class="bi bi-truck"></i> Marcar En Tránsito</button>
                                </li>
                            <?php endif; ?>
                            
                            <?php if ($envio['status'] == 'En tránsito'): ?>
                                <li><button class="dropdown-item" onclick="cambiarEstado(<?php echo $id; ?>, 'Entregado')">
                                    <i class="bi bi-check-circle"></i> Marcar Entregado</button>
                                </li>
                                <li><button class="dropdown-item" onclick="cambiarEstado(<?php echo $id; ?>, 'Intento fallido')">
                                    <i class="bi bi-exclamation-triangle"></i> Intento Fallido</button>
                                </li>
                            <?php endif; ?>
                            
                            <?php if ($is_admin && $envio['status'] != 'Cancelado' && $envio['status'] != 'Entregado'): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><button class="dropdown-item text-danger" onclick="cambiarEstado(<?php echo $id; ?>, 'Cancelado')">
                                    <i class="bi bi-x-circle"></i> Cancelar Envío</button>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Información del envío -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Información del Envío</h6>
                            <span class="badge <?php 
                                echo match ($envio['status']) {
                                    'Procesando' => 'bg-warning',
                                    'En tránsito' => 'bg-info',
                                    'Entregado' => 'bg-success',
                                    'Cancelado' => 'bg-danger',
                                    'Intento fallido' => 'bg-secondary',
                                    default => 'bg-secondary'
                                };
                            ?>">
                                <?php echo htmlspecialchars($envio['status']); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="position-relative mb-4">
                                <h5 class="fw-bold">#<?php echo htmlspecialchars($envio['tracking_number']); ?></h5>
                                <?php if ($envio['urgent']): ?>
                                    <span class="badge bg-danger badge-urgent">URGENTE</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h6><i class="bi bi-person"></i> Remitente</h6>
                                    <p><?php echo htmlspecialchars($envio['cliente_nombre'] ?? $envio['name']); ?><br>
                                    <?php echo htmlspecialchars($envio['email']); ?><br>
                                    <?php echo htmlspecialchars($envio['phone']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="bi bi-calendar"></i> Fechas</h6>
                                    <p><strong>Creado:</strong> <?php echo date('d/m/Y H:i', strtotime($envio['created_at'])); ?><br>
                                    <strong>Entrega estimada:</strong> <?php echo date('d/m/Y', strtotime($envio['delivery_date'])); ?>
                                    <?php if ($envio['status'] == 'Entregado'): ?>
                                        <br><strong>Entregado:</strong> <?php echo date('d/m/Y H:i', strtotime($envio['updated_at'])); ?>
                                    <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h6><i class="bi bi-geo-alt"></i> Origen</h6>
                                    <p><?php echo nl2br(htmlspecialchars($envio['origin'])); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="bi bi-geo-alt-fill"></i> Destino</h6>
                                    <p><?php echo nl2br(htmlspecialchars($envio['destination'])); ?></p>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h6><i class="bi bi-box"></i> Detalles del Paquete</h6>
                                    <p>
                                        <strong>Tipo:</strong> <?php echo ucfirst(str_replace('_', ' ', $envio['package_type'])); ?><br>
                                        <strong>Peso:</strong> <?php echo number_format($envio['weight'], 2); ?> kg<br>
                                        <strong>Descripción:</strong> <?php echo htmlspecialchars($envio['description']); ?><br>
                                        <?php if (isset($envio['value']) && $envio['value'] > 0): ?>
                                            <strong>Valor declarado:</strong> $<?php echo number_format($envio['value'], 2); ?><br>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="bi bi-truck"></i> Detalles de Entrega</h6>
                                    <p>
                                        <strong>Asegurado:</strong> <?php echo $envio['insurance'] ? 'Sí' : 'No'; ?><br>
                                        <strong>Urgente:</strong> <?php echo $envio['urgent'] ? 'Sí' : 'No'; ?><br>
                                        <strong>Costo:</strong> $<?php echo number_format($envio['estimated_cost'], 2); ?><br>
                                        <strong>Estado de pago:</strong> <?php echo ucfirst($envio['estado_pago']); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <?php if (!empty($envio['additional_notes'])): ?>
                            <div class="mb-3">
                                <h6><i class="bi bi-card-text"></i> Notas Adicionales</h6>
                                <p><?php echo nl2br(htmlspecialchars($envio['additional_notes'])); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($envio['package_image'])): ?>
                            <div class="mb-3">
                                <h6><i class="bi bi-image"></i> Imagen del Paquete</h6>
                                <img src="<?php echo '../' . htmlspecialchars($envio['package_image']); ?>" 
                                     alt="Imagen del paquete" class="img-fluid" style="max-height: 200px;">
                            </div>
                            <?php endif; ?>
                            
                            <?php if (($is_admin || $is_delivery) && isset($envio['repartidor_nombre'])): ?>
                            <hr>
                            <div class="mb-3">
                                <h6><i class="bi bi-person-check"></i> Información del Repartidor</h6>
                                <p>
                                    <strong>Asignado a:</strong> <?php echo htmlspecialchars($envio['repartidor_nombre']); ?><br>
                                    <?php if ($is_admin && isset($envio['repartidor_telefono'])): ?>
                                    <strong>Teléfono:</strong> <?php echo htmlspecialchars($envio['repartidor_telefono']); ?><br>
                                    <?php endif; ?>
                                    <?php if ($is_admin && isset($envio['repartidor_vehiculo'])): ?>
                                    <strong>Vehículo:</strong> <?php echo htmlspecialchars($envio['repartidor_vehiculo']); ?>
                                    <?php if (isset($envio['repartidor_placa'])): ?> 
                                        (Placa: <?php echo htmlspecialchars($envio['repartidor_placa']); ?>)
                                    <?php endif; ?>
                                    <br>
                                    <?php endif; ?>
                                    <strong>Fecha de asignación:</strong> 
                                    <?php echo date('d/m/Y H:i', strtotime($envio['fecha_asignacion'])); ?>
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Historial de tracking -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Historial de Tracking</h6>
                        </div>
                        <div class="card-body">
                            <div class="tracking-timeline">
                                <?php if (empty($tracking_history)): ?>
                                    <p class="text-muted text-center">No hay registros de tracking disponibles</p>
                                <?php else: ?>
                                    <?php foreach ($tracking_history as $index => $track): ?>
                                        <div class="tracking-item">
                                            <div class="tracking-icon <?php echo $index === 0 ? 'active' : ''; ?>">
                                                <i class="bi bi-circle-fill text-<?php echo $index === 0 ? 'white' : 'secondary'; ?> small"></i>
                                            </div>
                                            <div class="tracking-content">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($track['status']); ?></h6>
                                                    <span class="tracking-date">
                                                        <?php echo date('d/m/Y H:i', strtotime($track['created_at'])); ?>
                                                    </span>
                                                </div>
                                                <?php if (!empty($track['location'])): ?>
                                                    <p class="mb-1"><small><i class="bi bi-geo"></i> <?php echo htmlspecialchars($track['location']); ?></small></p>
                                                <?php endif; ?>
                                                <?php if (!empty($track['notes'])): ?>
                                                    <p class="mb-1"><?php echo htmlspecialchars($track['notes']); ?></p>
                                                <?php endif; ?>
                                                <small class="text-muted">
                                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($track['nombre_usuario'] ?? 'Sistema'); ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- QR Code -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Código QR</h6>
                        </div>
                        <div class="card-body text-center">
                            <div class="qr-container mb-3">
                                <img src="<?php echo $qr_url; ?>" alt="QR Code" class="img-fluid">
                            </div>
                            <p class="mb-0">Escanea para seguimiento</p>
                            <small class="text-muted"><?php echo htmlspecialchars($envio['tracking_number']); ?></small>
                            
                            <div class="mt-3">
                                <a href="<?php echo $qr_url; ?>" download="tracking-<?php echo $envio['tracking_number']; ?>.png" 
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-download"></i> Descargar QR
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Acciones Adicionales -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Acciones Adicionales</h6>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <a href="#" class="list-group-item list-group-item-action" 
                                   onclick="window.print(); return false;">
                                    <i class="bi bi-printer"></i> Imprimir Detalles
                                </a>
                                
                                <?php if ($is_admin): ?>
                                    <?php if ($envio['estado_pago'] == 'pendiente'): ?>
                                    <a href="registrar_pago.php?envio_id=<?php echo $id; ?>" class="list-group-item list-group-item-action">
                                        <i class="bi bi-credit-card"></i> Registrar Pago
                                    </a>
                                    <?php endif; ?>
                                    
                                    <a href="enviar_notificacion.php?envio_id=<?php echo $id; ?>" class="list-group-item list-group-item-action">
                                        <i class="bi bi-envelope"></i> Enviar Notificación
                                    </a>
                                    
                                    <?php if ($envio['status'] == 'Entregado'): ?>
                                    <a href="generar_factura.php?envio_id=<?php echo $id; ?>" class="list-group-item list-group-item-action">
                                        <i class="bi bi-receipt"></i> Generar Factura
                                    </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if ($is_delivery && $envio['status'] == 'En tránsito'): ?>
                                <a href="../pwa/update_position.php?envio_id=<?php echo $id; ?>" class="list-group-item list-group-item-action">
                                    <i class="bi bi-geo-alt"></i> Actualizar Ubicación
                                </a>
                                <?php endif; ?>
                                
                                <?php if ($is_client): ?>
                                <a href="../php/contacto.php?referencia=<?php echo $envio['tracking_number']; ?>" class="list-group-item list-group-item-action">
                                    <i class="bi bi-question-circle"></i> Solicitar Ayuda
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (($is_admin || $is_delivery) && !empty($envio['lat']) && !empty($envio['lng'])): ?>
                    <!-- Mapa de ubicación -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Ubicación Actual</h6>
                        </div>
                        <div class="card-body p-0">
                            <div id="map" style="height: 300px;"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($is_admin): ?>
    <!-- Modal para asignar repartidor -->
    <div class="modal fade" id="asignarRepartidorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Asignar Repartidor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="envios.php">
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="asignar_repartidor">
                        <input type="hidden" name="envio_id" value="<?php echo $id; ?>">

                        <div class="mb-3">
                            <label for="repartidor_id" class="form-label">Seleccionar Repartidor</label>
                            <select class="form-select" name="repartidor_id" required>
                                <option value="">-- Seleccione un repartidor --</option>
                                <?php foreach ($repartidores_activos as $repartidor): ?>
                                    <option value="<?php echo $repartidor['id']; ?>">
                                        <?php echo htmlspecialchars($repartidor['nombre_usuario']); ?> 
                                        (<?php echo htmlspecialchars($repartidor['vehiculo'] ?? 'Sin vehículo'); ?>
                                        <?php if (!empty($repartidor['capacidad_carga'])): ?>
                                         - <?php echo $repartidor['capacidad_carga']; ?> kg
                                        <?php endif; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Asignar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($is_delivery): ?>
    <!-- Modal para actualizar estado con notas (para repartidores) -->
    <div class="modal fade" id="actualizarEstadoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Actualizar Estado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="../pwa/actualizar_estado.php">
                    <div class="modal-body">
                        <input type="hidden" name="envio_id" value="<?php echo $id; ?>">
                        <input type="hidden" name="nuevo_estado" id="modal_nuevo_estado">
                        <input type="hidden" name="redirect" value="detalle_envio.php?id=<?php echo $id; ?>">
                        
                        <div class="mb-3">
                            <label for="ubicacion" class="form-label">Ubicación</label>
                            <input type="text" class="form-control" id="ubicacion" name="ubicacion" placeholder="Descripción de ubicación actual">
                        </div>
                        
                        <div class="mb-3">
                            <label for="notas" class="form-label">Notas / Comentarios</label>
                            <textarea class="form-control" id="notas" name="notas" rows="3" placeholder="Información adicional sobre el envío..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Formulario oculto para cambiar estado (admin) -->
    <form id="estadoForm" method="POST" action="<?php echo $is_admin ? 'envios.php' : '../pwa/actualizar_estado.php'; ?>" style="display: none;">
        <input type="hidden" name="accion" value="cambiar_estado">
        <input type="hidden" name="envio_id" id="estado_envio_id">
        <input type="hidden" name="nuevo_estado" id="nuevo_estado">
        <?php if (!$is_admin): ?>
        <input type="hidden" name="redirect" value="detalle_envio.php?id=<?php echo $id; ?>">
        <?php endif; ?>
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (($is_admin || $is_delivery) && !empty($envio['lat']) && !empty($envio['lng'])): ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo $_ENV['LOCATIONIQ_API_KEY'] ?? ''; ?>&callback=initMap" async defer></script>
    <script>
        function initMap() {
            const position = {lat: <?php echo $envio['lat']; ?>, lng: <?php echo $envio['lng']; ?>};
            const map = new google.maps.Map(document.getElementById("map"), {
                zoom: 15,
                center: position,
            });
            const marker = new google.maps.Marker({
                position: position,
                map: map,
                title: "Ubicación del envío",
            });
        }
    </script>
    <?php endif; ?>
    
    <script>
        function cambiarEstado(envioId, nuevoEstado) {
            <?php if ($is_delivery): ?>
            // Para repartidores, mostrar modal para notas
            document.getElementById('modal_nuevo_estado').value = nuevoEstado;
            new bootstrap.Modal(document.getElementById('actualizarEstadoModal')).show();
            <?php else: ?>
            // Para administradores, confirmación simple
            if (confirm(`¿Está seguro que desea cambiar el estado a "${nuevoEstado}"?`)) {
                document.getElementById('estado_envio_id').value = envioId;
                document.getElementById('nuevo_estado').value = nuevoEstado;
                document.getElementById('estadoForm').submit();
            }
            <?php endif; ?>
        }
    </script>
</body>
</html>