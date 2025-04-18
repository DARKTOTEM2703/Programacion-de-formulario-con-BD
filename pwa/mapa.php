<?php
session_start();
require_once '../components/db_connection.php';

// Verificar autenticación
if (!isset($_SESSION['repartidor_id'])) {
    header("Location: login.php");
    exit();
}

$repartidor_id = $_SESSION['repartidor_id'];

// Obtener envíos asignados al repartidor
$stmt = $conn->prepare("
    SELECT e.id, e.tracking_number, e.name AS recipient_name, e.destination AS recipient_address, e.status, e.urgent 
    FROM envios e 
    JOIN repartidores_envios re ON e.id = re.envio_id 
    WHERE re.usuario_id = ? AND e.status NOT IN ('Entregado', 'Cancelado')
");
$stmt->bind_param("i", $repartidor_id);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa de Rutas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="manifest" href="manifest.json">
    <style>
        #map {
            width: 100%;
            height: calc(100vh - 150px);
            margin-bottom: 20px;
        }

        .map-controls {
            position: absolute;
            bottom: 80px;
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
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <span class="navbar-brand">Mapa de Rutas</span>
        </div>
    </nav>

    <div class="position-relative">
        <!-- Mapa -->
        <div id="map"></div>

        <!-- Controles de mapa -->
        <div class="map-controls">
            <button class="btn btn-light map-control-btn" id="center-map">
                <i class="bi bi-geo-fill"></i>
            </button>
            <button class="btn btn-light map-control-btn" id="toggle-traffic">
                <i class="bi bi-signpost-2"></i>
            </button>
        </div>
    </div>

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
                    <a href="mapa.php" class="nav-link active">
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
    <!-- Reemplaza TU_API_KEY con tu clave de API de Google Maps -->
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=TU_API_KEY&libraries=places&callback=initMap">
    </script>
    <script>
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

        async function initMap() {
            // Inicializar mapa centrado en una ubicación predeterminada (CDMX)
            const defaultLocation = {
                lat: 19.4326,
                lng: -99.1332
            };

            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 12,
                center: defaultLocation,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false,
                zoomControl: true,
                zoomControlOptions: {
                    position: google.maps.ControlPosition.RIGHT_CENTER
                }
            });

            // Inicializar servicios de direcciones
            directionsService = new google.maps.DirectionsService();
            directionsRenderer = new google.maps.DirectionsRenderer({
                suppressMarkers: true,
                polylineOptions: {
                    strokeColor: "#4285F4",
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

                // Empezar a rastrear la posición
                startPositionTracking();

                // Colocar marcadores para los envíos
                await placeEnvioMarkers();

                // Calcular la ruta óptima
                calculateOptimalRoute(position);

            } catch (error) {
                console.error("Error obteniendo ubicación:", error);
                alert("No se pudo obtener tu ubicación. Usando ubicación predeterminada.");

                // Colocar marcadores para los envíos de todas formas
                await placeEnvioMarkers();
            }

            // Configurar controles de mapa
            document.getElementById('center-map').addEventListener('click', function() {
                getCurrentPosition().then(position => {
                    map.setCenter(position);
                    map.setZoom(15);
                }).catch(error => {
                    alert("No se pudo obtener tu ubicación actual.");
                });
            });

            document.getElementById('toggle-traffic').addEventListener('click', function() {
                if (isTrafficVisible) {
                    trafficLayer.setMap(null);
                    isTrafficVisible = false;
                } else {
                    trafficLayer.setMap(map);
                    isTrafficVisible = true;
                }
            });
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
                            enableHighAccuracy: true
                        }
                    );
                } else {
                    reject(new Error("Geolocalización no soportada"));
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
                        fillColor: "#4285F4",
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
            const marker = new google.maps.Marker({
                position: location,
                map: map,
                title: envio.tracking_number,
                animation: google.maps.Animation.DROP,
                icon: envio.urgent ? {
                    url: 'assets/icons/marker-urgent.png',
                    scaledSize: new google.maps.Size(35, 35)
                } : {
                    url: 'assets/icons/marker-normal.png',
                    scaledSize: new google.maps.Size(30, 30)
                }
            });

            // Crear ventana de información
            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div>
                        <h6>${envio.tracking_number}</h6>
                        <p><strong>Destinatario:</strong> ${envio.recipient_name}</p>
                        <p><strong>Dirección:</strong> ${envio.recipient_address}</p>
                        <p><strong>Estado:</strong> ${envio.status}</p>
                        <a href="detalle_envio.php?id=${envio.id}" class="btn btn-primary btn-sm">Ver Detalles</a>
                    </div>
                `
            });

            // Evento de clic
            marker.addListener('click', () => {
                infoWindow.open(map, marker);
            });

            markers.push(marker);
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

                    // Mostrar información en algún elemento de la interfaz
                    // (Podrías crear un div para esto)
                    console.log(`Distancia total: ${distanceKm} km`);
                    console.log(`Tiempo estimado: ${durationHours}h ${durationMinutes}m`);
                } else {
                    console.error("Error al calcular la ruta:", status);
                }
            });
        }
    </script>

    <script src="assets/js/pwa-init.js"></script>
</body>

</html>