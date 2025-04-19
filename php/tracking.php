<?php
require_once '../components/db_connection.php';
$tracking_number = isset($_GET['tracking']) ? $_GET['tracking'] : '';
$shipment = null;
$history = [];
$latest_location = null;

if (!empty($tracking_number)) {
    // Obtener información del envío
    $stmt = $conn->prepare("SELECT * FROM envios WHERE tracking_number = ?");
    $stmt->bind_param("s", $tracking_number);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $shipment = $result->fetch_assoc();

        // Obtener historial de seguimiento
        $stmt = $conn->prepare("SELECT th.*, u.nombre_usuario 
                               FROM tracking_history th 
                               LEFT JOIN usuarios u ON th.created_by = u.id 
                               WHERE th.envio_id = ? 
                               ORDER BY th.created_at DESC");
        $stmt->bind_param("i", $shipment['id']);
        $stmt->execute();
        $history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Obtener la última ubicación conocida (para el mapa)
        if (!empty($history)) {
            foreach ($history as $entry) {
                if (strpos($entry['location'], 'Lat:') !== false) {
                    $location_str = $entry['location'];
                    preg_match('/Lat: ([\d.-]+), Lng: ([\d.-]+)/', $location_str, $matches);
                    if (count($matches) == 3) {
                        $latest_location = [
                            'lat' => floatval($matches[1]),
                            'lng' => floatval($matches[2]),
                            'timestamp' => $entry['created_at'],
                            'status' => $entry['status']
                        ];
                        break;
                    }
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
    <title>Rastrear Envío - MENDEZ Transportes</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/tracking.css">
    <!-- Leaflet CSS y JS (para el mapa) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>

<body>
    <?php include '../components/header.php'; ?>

    <div class="tracking-container">
        <h1 class="mb-4">Rastrear Envío</h1>

        <form method="GET" action="" class="tracking-form mb-4">
            <div class="input-group">
                <input type="text" name="tracking" class="form-control" placeholder="Ingresa tu número de seguimiento"
                    value="<?php echo htmlspecialchars($tracking_number); ?>">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-search me-1"></i> Rastrear
                </button>
            </div>
        </form>

        <?php if ($shipment): ?>
            <div class="shipment-info">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h3>Envío #<?php echo htmlspecialchars($shipment['tracking_number']); ?></h3>
                    <?php if ($shipment['urgent']): ?>
                        <span class="badge bg-danger">URGENTE</span>
                    <?php endif; ?>
                </div>

                <div class="status-badge bg-<?php
                                            switch ($shipment['status']) {
                                                case 'Entregado':
                                                    echo 'success';
                                                    break;
                                                case 'En tránsito':
                                                case 'En camino':
                                                case 'En ruta':
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
                                            ?>">
                    <i class="bi bi-<?php
                                    switch ($shipment['status']) {
                                        case 'Entregado':
                                            echo 'check-circle-fill';
                                            break;
                                        case 'En tránsito':
                                        case 'En camino':
                                        case 'En ruta':
                                            echo 'truck';
                                            break;
                                        case 'Procesando':
                                            echo 'box-seam';
                                            break;
                                        case 'Cancelado':
                                            echo 'x-circle-fill';
                                            break;
                                        default:
                                            echo 'info-circle';
                                    }
                                    ?> me-1"></i>
                    <?php echo htmlspecialchars($shipment['status']); ?>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Origen:</strong> <?php echo htmlspecialchars($shipment['origin']); ?></p>
                        <p><strong>Destino:</strong> <?php echo htmlspecialchars($shipment['destination']); ?></p>
                        <p><strong>Destinatario:</strong> <?php echo htmlspecialchars($shipment['name']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Fecha de registro:</strong>
                            <?php echo date('d/m/Y', strtotime($shipment['created_at'])); ?></p>
                        <?php if (!empty($shipment['delivery_date'])): ?>
                            <p><strong>Entrega estimada:</strong>
                                <?php echo date('d/m/Y', strtotime($shipment['delivery_date'])); ?></p>
                        <?php endif; ?>
                        <p><strong>Peso:</strong> <?php echo htmlspecialchars($shipment['weight']); ?> kg</p>
                    </div>
                </div>
            </div>

            <!-- Sección de mapa reemplazada -->
            <?php if ($shipment['status'] == 'En tránsito' || $shipment['status'] == 'En camino' || $shipment['status'] == 'En ruta'): ?>
                <div class="map-container">
                    <!-- Mapa prominente -->
                    <?php if ($latest_location): ?>
                        <div id="map"></div>

                        <!-- Overlay con información del envío -->
                        <div class="map-overlay">
                            <div class="delivery-status">
                                <div class="status-icon">
                                    <i class="bi bi-truck"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Envío en tránsito</h6>
                                    <small class="text-muted">Última actualización:
                                        <?php echo date('H:i', strtotime($latest_location['timestamp'])); ?></small>
                                </div>
                            </div>

                            <div class="delivery-info">
                                <div><strong>Origen:</strong>
                                    <?php echo htmlspecialchars($shipment['origin'] ?? 'Centro de distribución'); ?></div>
                                <div><strong>Destino:</strong> <?php echo htmlspecialchars($shipment['destination']); ?></div>
                                <div class="mt-2"><strong>Destinatario:</strong> <?php echo htmlspecialchars($shipment['name']); ?>
                                </div>
                                <?php if (!empty($shipment['delivery_date'])): ?>
                                    <div class="eta-badge mt-2">
                                        <i class="bi bi-clock"></i> Entrega estimada:
                                        <?php echo date('d/m/Y', strtotime($shipment['delivery_date'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php
                            // Intentar obtener información del repartidor
                            $driver_info = null;
                            $stmt = $conn->prepare("SELECT u.nombre_usuario, u.apellido_usuario 
                                                   FROM repartidores_envios re 
                                                   JOIN usuarios u ON re.usuario_id = u.id 
                                                   WHERE re.envio_id = ?");
                            if ($stmt) {
                                $stmt->bind_param("i", $shipment['id']);
                                $stmt->execute();
                                $driver_result = $stmt->get_result();
                                if ($driver_row = $driver_result->fetch_assoc()) {
                                    $driver_info = $driver_row;
                                }
                            }
                            ?>

                            <?php if ($driver_info): ?>
                                <div class="driver-info">
                                    <div class="driver-avatar">
                                        <i class="bi bi-person"></i>
                                    </div>
                                    <div>
                                        <div class="driver-name">
                                            <?php echo htmlspecialchars($driver_info['nombre_usuario'] . ' ' . $driver_info['apellido_usuario']); ?>
                                        </div>
                                        <small class="text-muted">Repartidor asignado</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Controles del mapa -->
                        <div class="map-controls">
                            <button id="btnCenter" title="Centrar mapa">
                                <i class="bi bi-fullscreen"></i>
                            </button>
                            <button id="btnRefresh" title="Actualizar ubicación">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>

                        <!-- Indicador de actualización -->
                        <div class="update-indicator">
                            <i class="bi bi-clock-history"></i> Actualizando cada 60 segundos
                        </div>
                    <?php else: ?>
                        <div class="no-location">
                            <i class="bi bi-map"></i>
                            <h5>No hay ubicación disponible en este momento</h5>
                            <p class="text-muted">El repartidor aún no ha actualizado su ubicación. Por favor, vuelve a consultar
                                más tarde.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($shipment['status'] == 'Entregado'): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    El envío ha sido entregado correctamente el
                    <?php echo date('d/m/Y H:i', strtotime($shipment['delivery_date'] ?? $shipment['updated_at'] ?? $shipment['created_at'])); ?>.
                </div>
            <?php elseif ($shipment['status'] == 'Cancelado'): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-x-circle-fill me-2"></i>
                    Este envío ha sido cancelado.
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    El envío está siendo procesado. La ubicación en tiempo real estará disponible cuando esté en camino.
                </div>
            <?php endif; ?>

            <!-- Historial de seguimiento -->
            <h4 class="mb-3"><i class="bi bi-clock-history me-2"></i>Historial de seguimiento</h4>

            <?php if (count($history) > 0): ?>
                <div class="timeline">
                    <?php foreach ($history as $track): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between mb-2">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($track['status']); ?></h5>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y H:i', strtotime($track['created_at'])); ?>
                                    </small>
                                </div>

                                <?php if (!empty($track['location']) && $track['location'] != 'NULL'): ?>
                                    <p class="mb-1">
                                        <i class="bi bi-geo-alt me-1"></i>
                                        <?php
                                        // Mostrar ubicación de manera legible
                                        if (strpos($track['location'], 'Lat:') !== false) {
                                            echo "Ubicación actualizada por el transportista";
                                        } else {
                                            echo htmlspecialchars($track['location']);
                                        }
                                        ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($track['notes'])): ?>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($track['notes'])); ?></p>
                                <?php endif; ?>

                                <?php if (!empty($track['nombre_usuario'])): ?>
                                    <small class="text-muted">
                                        <i class="bi bi-person me-1"></i>
                                        Actualizado por: <?php echo htmlspecialchars($track['nombre_usuario']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No hay actualizaciones disponibles para este envío.
                </div>
            <?php endif; ?>

        <?php elseif (!empty($tracking_number)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                No se encontró el envío con el número de seguimiento proporcionado.
            </div>
            <div class="text-center mt-4">
                <p>Verifica que has ingresado correctamente el número de seguimiento.</p>
                <p>Si el problema persiste, contacta con nuestro servicio de atención al cliente:</p>
                <p><strong><i class="bi bi-telephone me-1"></i> (55) 1234-5678</strong></p>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle-fill me-2"></i>
                Ingresa tu número de seguimiento para rastrear tu envío.
            </div>
            <div class="text-center mt-4">
                <img src="../img/tracking-illustration.png" alt="Rastreo de envíos" class="img-fluid mb-3"
                    style="max-width: 300px;">
                <p>Con MENDEZ Transportes puedes rastrear tu envío en tiempo real.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <?php if ($latest_location): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Detectar preferencia de modo oscuro
                const prefersDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
                let mapTiles, mapLayer;

                // Variables globales
                let map, marker, updateInterval;
                const updateFrequency = 30000; // 30 segundos
                let lastUpdate = new Date();
                const updateIndicator = document.querySelector('.update-indicator');

                // Función para inicializar el mapa
                function initMap() {
                    // Crear el mapa
                    map = L.map('map').setView([<?php echo $latest_location['lat']; ?>,
                        <?php echo $latest_location['lng']; ?>
                    ], 14);

                    // Aplicar tiles según el modo
                    if (prefersDarkMode) {
                        mapLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                            className: 'dark-tiles' // Aplicar clase CSS para oscurecer el mapa
                        }).addTo(map);
                    } else {
                        mapLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                        }).addTo(map);
                    }

                    // Configurar iconos según el modo
                    const iconColor = prefersDarkMode ? '#0d6efd' : '#0057B8';
                    const destColor = prefersDarkMode ? '#ff9800' : '#ff9500';
                    const shadowIntensity = prefersDarkMode ? '0.5' : '0.3';

                    // Icono del vehículo adaptado al modo
                    const truckIcon = L.divIcon({
                        html: `<div style="background-color: ${iconColor}; color: white; border-radius: 50%; width: 36px; height: 36px; 
                               display: flex; justify-content: center; align-items: center; 
                               box-shadow: 0 2px 5px rgba(0,0,${shadowIntensity});">
                               <i class="bi bi-truck" style="font-size: 22px;"></i></div>`,
                        className: '',
                        iconSize: [36, 36],
                        iconAnchor: [18, 18]
                    });

                    // Añadir marcador del vehículo
                    marker = L.marker([<?php echo $latest_location['lat']; ?>,
                        <?php echo $latest_location['lng']; ?>
                    ], {
                        icon: truckIcon
                    }).addTo(map);

                    // Escuchar cambios en la preferencia del modo oscuro
                    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                        // Eliminar capa actual
                        map.removeLayer(mapLayer);

                        // Aplicar nueva capa según modo
                        if (e.matches) {
                            // Cambiar a modo oscuro
                            mapLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                                className: 'dark-tiles'
                            }).addTo(map);
                        } else {
                            // Cambiar a modo claro
                            mapLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                            }).addTo(map);
                        }
                    });

                    // [Resto del código para inicializar marcadores, popups, etc.]

                    // Funciones de control del mapa
                    document.getElementById('btnCenter').addEventListener('click', function() {
                        fitMapToMarkers();
                    });

                    document.getElementById('btnRefresh').addEventListener('click', function() {
                        updateLocation(true);
                        this.disabled = true;
                        setTimeout(() => {
                            this.disabled = false;
                        }, 2000); // Prevenir múltiples clics
                    });

                    // Añadir popup con información
                    marker.bindPopup(`
                    <div class="popup-content">
                        <h6 class="mb-1">Envío #<?php echo htmlspecialchars($shipment['tracking_number']); ?></h6>
                        <div><strong>Estado:</strong> <?php echo htmlspecialchars($latest_location['status']); ?></div>
                        <div><strong>Última actualización:</strong><br/><?php echo date('d/m/Y H:i', strtotime($latest_location['timestamp'])); ?></div>
                    </div>
                    `);

                    // Agregar marcador de destino si hay coordenadas
                    <?php if (!empty($shipment['destination_lat']) && !empty($shipment['destination_lng'])): ?>
                        const destIcon = L.divIcon({
                            html: '<div style="background-color: #FF9500; color: white; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); width: 36px; height: 36px; display: flex; justify-content: center; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"><i class="bi bi-geo-alt-fill" style="font-size: 22px; transform: rotate(45deg);"></i></div>',
                            className: 'dest-marker-icon',
                            iconSize: [36, 36],
                            iconAnchor: [18, 36]
                        });

                        const destMarker = L.marker([<?php echo $shipment['destination_lat']; ?>,
                            <?php echo $shipment['destination_lng']; ?>
                        ], {
                            icon: destIcon
                        }).addTo(map);

                        destMarker.bindPopup(`
                    <div class="popup-content">
                        <h6 class="mb-1">Destino</h6>
                        <div><?php echo htmlspecialchars($shipment['destination']); ?></div>
                        <div>Destinatario: <?php echo htmlspecialchars($shipment['name']); ?></div>
                    </div>
                `);

                        // Añadir una línea de ruta entre la ubicación actual y el destino
                        const routeLine = L.polyline([
                            [<?php echo $latest_location['lat']; ?>, <?php echo $latest_location['lng']; ?>],
                            [<?php echo $shipment['destination_lat']; ?>,
                                <?php echo $shipment['destination_lng']; ?>
                            ]
                        ], {
                            color: '#0057B8',
                            weight: 4,
                            opacity: 0.7,
                            dashArray: '10, 10'
                        }).addTo(map);

                        // Ajustar la vista para mostrar ambos puntos
                        fitMapToMarkers();
                    <?php else: ?>
                        // Si no hay destino, centrar en el marcador del repartidor
                        map.setView([<?php echo $latest_location['lat']; ?>, <?php echo $latest_location['lng']; ?>], 14);
                    <?php endif; ?>

                    // Iniciar actualizaciones periódicas
                    startPeriodicUpdates();
                }

                // Función para ajustar la vista del mapa
                function fitMapToMarkers() {
                    <?php if (!empty($shipment['destination_lat']) && !empty($shipment['destination_lng'])): ?>
                        const bounds = L.latLngBounds([
                            [<?php echo $latest_location['lat']; ?>, <?php echo $latest_location['lng']; ?>],
                            [<?php echo $shipment['destination_lat']; ?>,
                                <?php echo $shipment['destination_lng']; ?>
                            ]
                        ]);
                        map.fitBounds(bounds.pad(0.3));
                    <?php else: ?>
                        map.setView(marker.getLatLng(), 14);
                    <?php endif; ?>
                }

                // Función para actualizar la ubicación
                function updateLocation(userTriggered = false) {
                    // Mostrar indicador de actualización
                    updateIndicator.innerHTML = '<i class="bi bi-arrow-repeat"></i> Actualizando ubicación...';
                    updateIndicator.classList.add('updating');

                    fetch(`../api/tracking.php?tracking_number=<?php echo $tracking_number; ?>`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.location) {
                                // Actualizar la posición del marcador con animación
                                const newLatLng = [data.location.lat, data.location.lng];
                                marker.setLatLng(newLatLng);

                                // Actualizar la línea de ruta si existe
                                <?php if (!empty($shipment['destination_lat']) && !empty($shipment['destination_lng'])): ?>
                                    const routeLine = document.querySelector('.leaflet-overlay-pane path');
                                    if (routeLine) {
                                        const updatedRoute = [
                                            newLatLng,
                                            [<?php echo $shipment['destination_lat']; ?>,
                                                <?php echo $shipment['destination_lng']; ?>
                                            ]
                                        ];
                                        map.removeLayer(routeLine);
                                        L.polyline(updatedRoute, {
                                            color: '#0057B8',
                                            weight: 4,
                                            opacity: 0.7,
                                            dashArray: '10, 10'
                                        }).addTo(map);
                                    }
                                <?php endif; ?>

                                // Centrar el mapa en el marcador si el usuario solicitó la actualización
                                if (userTriggered) {
                                    map.panTo(newLatLng);
                                }

                                // Actualizar el popup con nueva información
                                marker.getPopup().setContent(`
                            <div class="popup-content">
                                <h6 class="mb-1">Envío #<?php echo htmlspecialchars($shipment['tracking_number']); ?></h6>
                                <div><strong>Estado:</strong> ${data.status}</div>
                                <div><strong>Última actualización:</strong><br/>${new Date(data.timestamp).toLocaleString()}</div>
                            </div>
                        `);

                                // Actualizar texto de última actualización
                                const statusUpdate = document.querySelector('.delivery-status small');
                                if (statusUpdate) {
                                    statusUpdate.textContent =
                                        `Última actualización: ${new Date(data.timestamp).toLocaleTimeString()}`;
                                }

                                // Actualizar tiempo de última actualización
                                lastUpdate = new Date();
                            }
                        })
                        .catch(error => {
                            console.error('Error al actualizar ubicación:', error);
                        })
                        .finally(() => {
                            // Restaurar indicador de actualización
                            updateIndicator.innerHTML =
                                `<i class="bi bi-clock-history"></i> Actualizado: ${lastUpdate.toLocaleTimeString()}`;
                            updateIndicator.classList.remove('updating');
                        });
                }

                // Función para iniciar actualizaciones periódicas
                function startPeriodicUpdates() {
                    // Actualizar inmediatamente por primera vez
                    updateLocation();

                    // Configurar intervalo de actualización
                    updateInterval = setInterval(updateLocation, updateFrequency);

                    // Limpiar intervalo cuando la página se cierra
                    window.addEventListener('beforeunload', function() {
                        clearInterval(updateInterval);
                    });
                }

                // Inicializar el mapa
                initMap();
            });
        </script>
    <?php endif; ?>
</body>

</html>