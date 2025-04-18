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
    <title>Mapa de Rutas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2c82c9">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <style>
        #map {
            width: 100%;
            height: calc(100vh - 150px);
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .map-placeholder {
            width: 100%;
            height: calc(100vh - 150px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            color: var(--text-secondary);
            text-align: center;
            padding: 20px;
        }

        .map-placeholder i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .map-controls {
            position: absolute;
            bottom: 90px;
            right: 10px;
            z-index: 1000;
        }

        .map-control-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            background-color: var(--card-bg);
            color: var(--primary-color);
            box-shadow: var(--box-shadow);
            border: none;
            transition: var(--transition);
        }

        .map-control-btn:hover {
            transform: scale(1.1);
            background-color: var(--primary-light);
            color: white;
        }

        .route-info {
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 10px;
            z-index: 1000;
            color: var(--text-color);
            display: none;
        }

        .route-info.active {
            display: block;
        }

        /* Modo oscuro para Google Maps */
        @media (prefers-color-scheme: dark) {
            .gm-style {
                filter: brightness(0.8) contrast(1.2) !important;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-geo-alt me-2"></i>Mapa de Rutas
            </a>
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown">
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

    <!-- Barra de navegación inferior (solo móvil) -->
    <nav class="navbar fixed-bottom navbar-dark bg-primary p-0 shadow">
        <div class="container-fluid p-0">
            <div class="d-flex flex-row justify-content-around w-100">
                <a href="dashboard.php" class="nav-link text-center py-3 flex-fill">
                    <div class="d-flex flex-column align-items-center">
                        <i class="bi bi-house-door fs-4"></i>
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
                        <i class="bi bi-geo-alt fs-4"></i>
                        <span class="small">Mapa</span>
                    </div>
                </a>
                <a href="perfil.php" class="nav-link text-center py-3 flex-fill">
                    <div class="d-flex flex-column align-items-center">
                        <i class="bi bi-person fs-4"></i>
                        <span class="small">Perfil</span>
                    </div>
                </a>
            </div>
        </div>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- IMPORTANTE: Reemplaza API_KEY_AQUI con tu clave de API de Google Maps -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mapElement = document.getElementById('map');
            const mapPlaceholder = document.getElementById('mapPlaceholder');
            const retryLocationBtn = document.getElementById('retryLocationBtn');

            // Evento para solicitar permisos de ubicación nuevamente
            retryLocationBtn.addEventListener('click', function() {
                loadGoogleMapsScript();
            });

            // Cargar script de Google Maps
            loadGoogleMapsScript();

            // Función para cargar el script de Google Maps
            function loadGoogleMapsScript() {
                if (window.google && window.google.maps) {
                    // Google Maps ya está cargado
                    initMap();
                    return;
                }

                mapPlaceholder.querySelector('h4').textContent = 'Cargando mapa...';

                const script = document.createElement('script');
                script.src =
                    "https://maps.googleapis.com/maps/api/js?key=AIzaSyATiX0cBHJ0DTaSJ9orK0MdTKdzB-HcTsc&lib    raries=places&callback=initMap";
                script.async = true;
                script.defer = true;
                script.onerror = function() {
                    showMapError('No se pudo cargar Google Maps. Verifica tu conexión a internet.');
                };
                document.body.appendChild(script);
            }

            // Mostrar error del mapa
            window.showMapError = function(message) {
                mapPlaceholder.innerHTML = `
                    <i class="bi bi-exclamation-triangle text-warning"></i>
                    <h4>No se pudo cargar el mapa</h4>
                    <p>${message}</p>
                    <button id="retryMapBtn" class="btn btn-primary mt-3">
                        <i class="bi bi-arrow-repeat me-2"></i>Reintentar
                    </button>
                `;
                document.getElementById('retryMapBtn').addEventListener('click', loadGoogleMapsScript);
                mapPlaceholder.style.display = 'flex';
                mapElement.style.display = 'none';
            }
        });

        // Variables globales
        let map;
        let markers = [];
        let directionsService;
        let directionsRenderer;
        let trafficLayer;
        let isTrafficVisible = false;
        let currentPositionMarker;
        let watchId;

        // Envíos del servidor (PHP)
        const envios = <?php echo json_encode($envios); ?>;

        // Inicializar mapa
        async function initMap() {
            const mapElement = document.getElementById('map');
            const mapPlaceholder = document.getElementById('mapPlaceholder');

            // Inicializar mapa centrado en una ubicación predeterminada (CDMX)
            const defaultLocation = {
                lat: 19.4326,
                lng: -99.1332
            };

            // Crear mapa con opciones adaptadas para modo oscuro/claro
            const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

            map = new google.maps.Map(mapElement, {
                zoom: 12,
                center: defaultLocation,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false,
                zoomControl: true,
                zoomControlOptions: {
                    position: google.maps.ControlPosition.RIGHT_CENTER
                },
                styles: isDarkMode ? [{
                        elementType: "geometry",
                        stylers: [{
                            color: "#242f3e"
                        }]
                    },
                    {
                        elementType: "labels.text.stroke",
                        stylers: [{
                            color: "#242f3e"
                        }]
                    },
                    {
                        elementType: "labels.text.fill",
                        stylers: [{
                            color: "#746855"
                        }]
                    },
                    {
                        featureType: "administrative.locality",
                        elementType: "labels.text.fill",
                        stylers: [{
                            color: "#d59563"
                        }],
                    },
                    {
                        featureType: "poi",
                        elementType: "labels.text.fill",
                        stylers: [{
                            color: "#d59563"
                        }],
                    },
                    {
                        featureType: "poi.park",
                        elementType: "geometry",
                        stylers: [{
                            color: "#263c3f"
                        }],
                    },
                    {
                        featureType: "poi.park",
                        elementType: "labels.text.fill",
                        stylers: [{
                            color: "#6b9a76"
                        }],
                    },
                    {
                        featureType: "road",
                        elementType: "geometry",
                        stylers: [{
                            color: "#38414e"
                        }],
                    },
                    {
                        featureType: "road",
                        elementType: "geometry.stroke",
                        stylers: [{
                            color: "#212a37"
                        }],
                    },
                    {
                        featureType: "road",
                        elementType: "labels.text.fill",
                        stylers: [{
                            color: "#9ca5b3"
                        }],
                    },
                    {
                        featureType: "road.highway",
                        elementType: "geometry",
                        stylers: [{
                            color: "#746855"
                        }],
                    },
                    {
                        featureType: "road.highway",
                        elementType: "geometry.stroke",
                        stylers: [{
                            color: "#1f2835"
                        }],
                    },
                    {
                        featureType: "road.highway",
                        elementType: "labels.text.fill",
                        stylers: [{
                            color: "#f3d19c"
                        }],
                    },
                    {
                        featureType: "transit",
                        elementType: "geometry",
                        stylers: [{
                            color: "#2f3948"
                        }],
                    },
                    {
                        featureType: "transit.station",
                        elementType: "labels.text.fill",
                        stylers: [{
                            color: "#d59563"
                        }],
                    },
                    {
                        featureType: "water",
                        elementType: "geometry",
                        stylers: [{
                            color: "#17263c"
                        }],
                    },
                    {
                        featureType: "water",
                        elementType: "labels.text.fill",
                        stylers: [{
                            color: "#515c6d"
                        }],
                    },
                    {
                        featureType: "water",
                        elementType: "labels.text.stroke",
                        stylers: [{
                            color: "#17263c"
                        }],
                    },
                ] : [] // Estilo normal para modo claro
            });

            // Inicializar servicios de direcciones
            directionsService = new google.maps.DirectionsService();
            directionsRenderer = new google.maps.DirectionsRenderer({
                suppressMarkers: true,
                polylineOptions: {
                    strokeColor: isDarkMode ? "#4fc3f7" : "#4285F4",
                    strokeWeight: 5,
                    strokeOpacity: 0.8
                }
            });
            directionsRenderer.setMap(map);

            // Inicializar capa de tráfico
            trafficLayer = new google.maps.TrafficLayer();

            // Obtener la posición actual del usuario
            try {
                const position = await getCurrentPosition();
                map.setCenter(position);
                setCurrentPositionMarker(position);

                // Mostrar el mapa
                mapElement.style.display = 'block';
                mapPlaceholder.style.display = 'none';

                // Empezar a rastrear la posición
                startPositionTracking();

                // Colocar marcadores para los envíos
                await placeEnvioMarkers();

                // Calcular la ruta óptima
                if (envios.length > 0) {
                    calculateOptimalRoute(position);
                }

            } catch (error) {
                console.error("Error obteniendo ubicación:", error);
                showMapError(
                    'No se pudo acceder a tu ubicación. Por favor, permite el acceso a la ubicación en tu navegador.'
                );
            }

            // Configurar controles de mapa
            document.getElementById('center-map').addEventListener('click', function() {
                getCurrentPosition().then(position => {
                    map.setCenter(position);
                    map.setZoom(15);
                }).catch(error => {
                    showMapError('No se pudo obtener tu ubicación actual.');
                });
            });

            document.getElementById('toggle-traffic').addEventListener('click', function() {
                if (isTrafficVisible) {
                    trafficLayer.setMap(null);
                    isTrafficVisible = false;
                    this.classList.remove('active');
                } else {
                    trafficLayer.setMap(map);
                    isTrafficVisible = true;
                    this.classList.add('active');
                }
            });

            document.getElementById('toggle-info').addEventListener('click', function() {
                toggleRouteInfo();
            });

            // Hacer que los envíos sean clickeables para centrar en el mapa
            document.querySelectorAll('.envio-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    // No activar si se hace clic en el botón "Ver detalles"
                    if (e.target.tagName === 'A' || e.target.tagName === 'I' || e.target.closest('a')) {
                        return;
                    }

                    const envioId = this.dataset.id;
                    const envio = envios.find(e => e.id == envioId);
                    if (envio) {
                        centerMapOnAddress(envio.recipient_address);
                    }
                });
            });
        }

        // Centrar mapa en una dirección
        function centerMapOnAddress(address) {
            const geocoder = new google.maps.Geocoder();
            geocoder.geocode({
                address: address
            }, (results, status) => {
                if (status === "OK" && results && results.length > 0) {
                    map.setCenter(results[0].geometry.location);
                    map.setZoom(16);
                }
            });
        }

        // Mostrar/ocultar info de ruta
        function toggleRouteInfo() {
            document.getElementById('routeInfo').classList.toggle('active');
        }

        // Obtener la posición actual con promesas
        function getCurrentPosition() {
            return new Promise((resolve, reject) => {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        position => resolve({
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        }),
                        error => reject(error), {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 0
                        }
                    );
                } else {
                    reject(new Error("Geolocalización no soportada en este navegador"));
                }
            });
        }

        // Iniciar rastreo de posición
        function startPositionTracking() {
            if (navigator.geolocation) {
                watchId = navigator.geolocation.watchPosition(
                    position => {
                        const currentPos = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };

                        setCurrentPositionMarker(currentPos);
                    },
                    error => console.error("Error en rastreo:", error), {
                        enableHighAccuracy: true
                    }
                );
            }
        }

        // Detener rastreo de posición
        function stopPositionTracking() {
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }
        }

        // Colocar/actualizar marcador de posición actual
        function setCurrentPositionMarker(position) {
            const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

            if (currentPositionMarker) {
                currentPositionMarker.setPosition(position);
            } else {
                currentPositionMarker = new google.maps.Marker({
                    position: position,
                    map: map,
                    title: "Tu ubicación",
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 10,
                        fillColor: isDarkMode ? "#64b5f6" : "#4285F4",
                        fillOpacity: 1,
                        strokeColor: "#FFFFFF",
                        strokeWeight: 2
                    },
                    zIndex: 999
                });
            }
        }

        // Colocar marcadores de envíos en el mapa
        async function placeEnvioMarkers() {
            // Limpiar marcadores existentes
            markers.forEach(marker => marker.setMap(null));
            markers = [];

            // Si no hay envíos, salir
            if (envios.length === 0) {
                return;
            }

            // Promesas para geocodificación
            const geocodePromises = envios.map(envio =>
                geocodeAddress(envio.recipient_address)
                .then(location => ({
                    envio,
                    location
                }))
                .catch(error => {
                    console.error("Error geocodificando:", error);
                    return {
                        envio,
                        location: null
                    };
                })
            );

            // Esperar a que todas las promesas se resuelvan
            const results = await Promise.all(geocodePromises);

            // Crear marcadores para las ubicaciones exitosas
            results.forEach(result => {
                if (result.location) {
                    createEnvioMarker(result.envio, result.location);
                }
            });

            // Ajustar el mapa para mostrar todos los marcadores
            if (markers.length > 0) {
                const bounds = new google.maps.LatLngBounds();
                markers.forEach(marker => bounds.extend(marker.getPosition()));
                if (currentPositionMarker) {
                    bounds.extend(currentPositionMarker.getPosition());
                }
                map.fitBounds(bounds);
            }
        }

        // Geocodificar dirección
        function geocodeAddress(address) {
            return new Promise((resolve, reject) => {
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode({
                    address: address
                }, (results, status) => {
                    if (status === "OK" && results && results.length > 0) {
                        resolve(results[0].geometry.location);
                    } else {
                        reject(new Error(`Geocodificación fallida: ${status}`));
                    }
                });
            });
        }

        // Crear marcador para un envío
        function createEnvioMarker(envio, location) {
            const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

            const marker = new google.maps.Marker({
                position: location,
                map: map,
                title: envio.tracking_number,
                animation: google.maps.Animation.DROP,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 8,
                    fillColor: envio.urgent ? (isDarkMode ? "#e57373" : "#d32f2f") : (isDarkMode ? "#81c784" :
                        "#2e7d32"),
                    fillOpacity: 0.9,
                    strokeColor: "#FFFFFF",
                    strokeWeight: 2
                }
            });

            // Crear ventana de información
            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div style="width: 250px; padding: 5px;">
                        <h6 style="margin: 0 0 8px 0; color: ${envio.urgent ? '#d32f2f' : '#1976d2'}">${envio.tracking_number}</h6>
                        <p style="margin: 0 0 5px 0;"><strong>Destinatario:</strong> ${envio.recipient_name}</p>
                        <p style="margin: 0 0 5px 0;"><strong>Dirección:</strong> ${envio.recipient_address}</p>
                        <p style="margin: 0 0 10px 0;"><strong>Estado:</strong> 
                           <span style="background-color: ${getStatusColor(envio.status)}; color: white; padding: 2px 6px; border-radius: 4px; font-size: 12px;">${envio.status}</span>
                        </p>
                        <a href="detalle_envio.php?id=${envio.id}" class="btn btn-primary btn-sm" style="width: 100%;">Ver Detalles</a>
                    </div>
                `
            });

            // Evento de clic
            marker.addListener('click', () => {
                // Cerrar otras ventanas de información abiertas
                markers.forEach(m => {
                    if (m.infoWindow && m.infoWindow !== infoWindow) {
                        m.infoWindow.close();
                    }
                });

                infoWindow.open(map, marker);
            });

            marker.infoWindow = infoWindow;
            markers.push(marker);

            return marker;
        }

        // Obtener color para estado
        function getStatusColor(status) {
            switch (status) {
                case 'Procesando':
                    return '#6c757d';
                case 'En camino':
                    return '#1976d2';
                case 'En ruta':
                    return '#0288d1';
                case 'Entregado':
                    return '#2e7d32';
                case 'Cancelado':
                    return '#d32f2f';
                default:
                    return '#6c757d';
            }
        }

        // Calcular ruta óptima con el Algoritmo del Vendedor Viajero
        async function calculateOptimalRoute(startPosition) {
            if (markers.length === 0) return;

            // Waypoints para la ruta (direcciones de envíos)
            const waypoints = markers.map(marker => ({
                location: marker.getPosition(),
                stopover: true
            }));

            // Configurar la solicitud de ruta
            directionsService.route({
                origin: startPosition,
                destination: startPosition, // Volver al punto de inicio
                waypoints: waypoints,
                optimizeWaypoints: true, // Optimizar el orden
                travelMode: google.maps.TravelMode.DRIVING
            }, (response, status) => {
                if (status === "OK" && response) {
                    directionsRenderer.setDirections(response);

                    // Mostrar distancia y tiempo estimado
                    const route = response.routes[0];
                    let totalDistance = 0;
                    let totalDuration = 0;

                    route.legs.forEach(leg => {
                        totalDistance += leg.distance.value;
                        totalDuration += leg.duration.value;
                    });

                    // Convertir a formato legible
                    const distanceKm = (totalDistance / 1000).toFixed(1);
                    const durationHours = Math.floor(totalDuration / 3600);
                    const durationMinutes = Math.floor((totalDuration % 3600) / 60);

                    // Mostrar información
                    document.getElementById('routeDistance').textContent = `${distanceKm} km`;
                    document.getElementById('routeDuration').textContent = durationHours > 0 ?
                        `${durationHours}h ${durationMinutes}m` :
                        `${durationMinutes} min`;

                    // Mostrar información de ruta
                    document.getElementById('routeInfo').classList.add('active');
                    setTimeout(() => {
                        document.getElementById('routeInfo').classList.remove('active');
                    }, 5000);
                } else {
                    console.error("Error al calcular la ruta:", status);
                    if (status === "ZERO_RESULTS") {
                        showMapError("No se pudo encontrar una ruta válida entre las ubicaciones");
                    }
                }
            });
        }
    </script>

    <script src="assets/js/pwa-init.js"></script>
</body>

</html>