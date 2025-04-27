<?php
session_start();
require_once '../components/db_connection.php';

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Obtener envíos asignados al repartidor
$stmt = $conn->prepare("
    SELECT e.id, e.tracking_number, e.name AS recipient_name, e.destination AS recipient_address, e.status, e.urgent 
    FROM envios e 
    JOIN repartidores_envios re ON e.id = re.envio_id 
    WHERE re.usuario_id = ? AND e.status NOT IN ('Entregado', 'Cancelado')
");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$envios = [];
while ($row = $result->fetch_assoc()) {
    $envios[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Rutas de Entrega - MENDEZ Transportes</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0057B8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <!-- Añadir Leaflet CSS y JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <!-- Añadir Leaflet Routing Machine para rutas -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

    <style>
        :root {
            --primary-color: #0057B8;
            /* Color azul corporativo MENDEZ */
            --secondary-color: #FF9500;
            /* Color naranja/amarillo de acento MENDEZ */
            --accent-color: #FF9500;
            --success-color: #2E8B57;
            --danger-color: #D32F2F;
            --warning-color: #F9A826;
            --light-bg: #F8F9FA;
            --dark-text: #212529;
        }

        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 80px;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar-brand img {
            height: 36px;
            width: auto;
        }

        .company-logo {
            width: 120px;
            height: auto;
            margin-bottom: 20px;
        }

        .dashboard-container {
            padding: 20px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0 15px;
        }

        .section-header h2 {
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
            color: var(--primary-color);
        }

        .section-content {
            margin-bottom: 25px;
        }

        #map {
            width: 100%;
            height: 400px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            z-index: 1;
        }

        .map-placeholder {
            height: 400px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-color: #f8f9fa;
            border-radius: 15px;
            text-align: center;
            padding: 20px;
        }

        .map-placeholder i {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }

        .map-controls {
            position: absolute;
            top: 460px;
            right: 30px;
            z-index: 500;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .map-control-btn {
            width: 40px;
            height: 40px;
            background-color: white;
            border: none;
            border-radius: 50%;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.2rem;
            color: var(--primary-color);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .map-control-btn:hover {
            background-color: #f0f0f0;
            transform: scale(1.05);
        }

        .route-info {
            position: fixed;
            bottom: 90px;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 400px;
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .route-info.active {
            opacity: 1;
            pointer-events: all;
            bottom: 100px;
        }

        .envio-card {
            background-color: white;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .envio-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .envio-card.urgent {
            border-left: 4px solid var(--danger-color);
        }

        .info-alert {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--light-bg);
            padding: 15px;
            border-radius: 10px;
            color: #6c757d;
            font-style: italic;
        }

        .info-alert i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        .error-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            height: 100%;
            width: 100%;
        }

        .nav-link {
            color: #fff !important;
            opacity: 0.8;
            transition: all 0.3s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            opacity: 1;
            transform: translateY(-2px);
        }

        .navbar.fixed-bottom {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
            overflow: hidden;
        }

        /* Para marcadores personalizados en el mapa */
        .envio-marker {
            display: block;
        }

        .user-marker {
            display: block;
        }

        /* Para las instrucciones de ruta (ocultas) */
        .leaflet-routing-container {
            display: none !important;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="assets/icons/logo.png" alt="MENDEZ Logo">
                <span>Rutas de Entrega</span>
            </a>
            <div class="dropdown">
                <button class="btn dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown"
                    style="color: white; border: none;">
                    <i class="bi bi-person-circle"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="dashboard.php"><i class="bi bi-house-door me-2"></i>Volver al
                            Inicio</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="perfil.php"><i class="bi bi-person me-2"></i>Mi Perfil</a></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar
                            Sesión</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="dashboard-container position-relative">
        <!-- Logo centrado -->
        <div class="text-center mb-3">
            <img src="assets/icons/logo.png" alt="MENDEZ Transportes" class="company-logo">
        </div>

        <!-- Info de Ruta -->
        <div id="routeInfo" class="route-info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong id="routeDistance">0 km</strong> · <span id="routeDuration">0 min</span>
                </div>
                <button type="button" class="btn-close" aria-label="Close" onclick="toggleRouteInfo()"></button>
            </div>
        </div>

        <!-- Mapa o Placeholder -->
        <div id="mapContainer">
            <div id="map" style="display:none;"></div>
            <div id="mapPlaceholder" class="map-placeholder">
                <i class="bi bi-map"></i>
                <h4>Cargando mapa...</h4>
                <p>Por favor permite el acceso a tu ubicación para ver las rutas de entrega.</p>
                <button id="retryLocationBtn" class="btn btn-primary mt-3">
                    <i class="bi bi-geo-alt me-2"></i>Permitir ubicación
                </button>
            </div>
        </div>

        <!-- Controles de mapa -->
        <div class="map-controls">
            <button class="map-control-btn" id="center-map" title="Centrar mapa">
                <i class="bi bi-geo-fill"></i>
            </button>
            <button class="map-control-btn" id="toggle-traffic" title="Mostrar tráfico">
                <i class="bi bi-signpost-2"></i>
            </button>
            <button class="map-control-btn" id="toggle-info" title="Información de ruta">
                <i class="bi bi-info-circle"></i>
            </button>
        </div>

        <!-- Lista de entregas pendientes -->
        <div class="section-header mt-4">
            <h2><i class="bi bi-list-check"></i> Entregas pendientes</h2>
        </div>
        <div class="section-content">
            <?php if (count($envios) > 0): ?>
                <?php foreach ($envios as $envio): ?>
                    <div class="envio-card <?php echo $envio['urgent'] ? 'urgent' : ''; ?>"
                        data-id="<?php echo $envio['id']; ?>">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="card-title fw-bold mb-0">
                                <i class="bi bi-box me-2"></i>
                                <?php echo htmlspecialchars($envio['tracking_number']); ?>
                            </h6>
                            <?php if ($envio['urgent']): ?>
                                <span class="badge bg-danger">URGENTE</span>
                            <?php endif; ?>
                        </div>
                        <div class="mb-2">
                            <i class="bi bi-geo-alt text-primary me-1"></i>
                            <?php echo htmlspecialchars($envio['recipient_address']); ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-<?php
                                                    echo match ($envio['status']) {
                                                        'Procesando' => 'secondary',
                                                        'En camino' => 'primary',
                                                        'En ruta' => 'info',
                                                        'Entregado' => 'success',
                                                        'Cancelado' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                    ?>"><?php echo htmlspecialchars($envio['status']); ?></span>
                            <a href="detalle_envio.php?id=<?php echo $envio['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-eye-fill"></i> Ver detalles
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="info-alert">
                    <i class="bi bi-info-circle"></i>
                    <span>No tienes envíos pendientes.</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Barra de navegación inferior -->
    <nav class="navbar fixed-bottom navbar-dark p-0 shadow">
        <div class="container-fluid p-0">
            <div class="d-flex flex-row justify-content-around w-100">
                <a href="dashboard.php" class="nav-link text-center py-3 flex-fill">
                    <div class="d-flex flex-column align-items-center">
                        <i class="bi bi-house-door-fill fs-4"></i>
                        <span class="small">Inicio</span>
                    </div>
                </a>
                <a href="escanear.php" class="nav-link text-center py-3 flex-fill">
                    <div class="d-flex flex-column align-items-center">
                        <i class="bi bi-qr-code-scan fs-4"></i>
                        <span class="small">Escanear</span>
                    </div>
                </a>
                <a href="mapa.php" class="nav-link text-center py-3 flex-fill active">
                    <div class="d-flex flex-column align-items-center">
                        <i class="bi bi-geo-alt-fill fs-4"></i>
                        <span class="small">Rutas</span>
                    </div>
                </a>
                <a href="perfil.php" class="nav-link text-center py-3 flex-fill">
                    <div class="d-flex flex-column align-items-center">
                        <i class="bi bi-person-fill fs-4"></i>
                        <span class="small">Perfil</span>
                    </div>
                </a>
            </div>
        </div>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="pwa-init.js"></script>
    <script>
        const envios = <?php echo json_encode($envios); ?>;
        let map = L.map('map').setView([20.967370, -89.592586], 12); // Mérida por defecto

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // Mostrar marcadores de envíos
        envios.forEach(envio => {
            if (envio.lat && envio.lng) {
                L.marker([envio.lat, envio.lng])
                    .addTo(map)
                    .bindPopup(
                        `<b>#${envio.tracking_number}</b><br>
                Destino: ${envio.recipient_address}<br>
                Estado: ${envio.status}<br>
                <a href="detalle_envio.php?id=${envio.id}">Ver detalle</a>`
                    );
            }
        });

        // Mostrar ubicación actual del repartidor en tiempo real
        let userMarker = null;
        if (navigator.geolocation) {
            navigator.geolocation.watchPosition(function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                if (userMarker) {
                    userMarker.setLatLng([lat, lng]);
                } else {
                    userMarker = L.marker([lat, lng], {
                        icon: L.icon({
                            iconUrl: 'assets/icons/user-marker.png',
                            iconSize: [32, 32]
                        })
                    }).addTo(map).bindPopup('Tu ubicación actual');
                    map.setView([lat, lng], 13);
                    document.getElementById('map').style.display = 'block';
                    document.getElementById('mapPlaceholder').style.display = 'none';
                }
            }, function() {
                document.getElementById('mapPlaceholder').innerHTML =
                    '<i class="bi bi-exclamation-triangle"></i><h4>No se pudo obtener tu ubicación</h4>';
            }, {
                enableHighAccuracy: true,
                maximumAge: 0,
                timeout: 10000
            });
        }
    </script>
</body>

</html>