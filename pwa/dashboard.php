<?php
session_start();
require_once '../components/db_connection.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Verificar si el usuario necesita completar su perfil
if (isset($_SESSION['repartidor_pendiente'])) {
    header("Location: register_step2.php");
    exit();
}

// Verificar si el repartidor está autenticado
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'repartidor') {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$nombre = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Repartidor';

// Obtener los envíos asignados al repartidor
$stmt = $conn->prepare("
    SELECT e.* FROM envios e 
    JOIN repartidores_envios re ON e.id = re.envio_id 
    WHERE re.usuario_id = ? AND e.status NOT IN ('Entregado', 'Cancelado')
    ORDER BY e.urgent DESC, e.created_at ASC
");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$envios_pendientes = [];
while ($row = $result->fetch_assoc()) {
    $envios_pendientes[] = $row;
}

// Obtener los últimos envíos entregados
$stmt = $conn->prepare("
    SELECT e.* FROM envios e 
    JOIN repartidores_envios re ON e.id = re.envio_id 
    WHERE re.usuario_id = ? AND e.status = 'Entregado'
    ORDER BY e.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$envios_entregados = [];
while ($row = $result->fetch_assoc()) {
    $envios_entregados[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard Repartidor</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2c82c9">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-truck me-2"></i>App Repartidores
            </a>
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text">Hola, <?php echo htmlspecialchars($nombre); ?></span></li>
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

    <div class="dashboard-container">
        <!-- Bienvenida -->
        <h1 class="welcome-header">¡Bienvenido, <?php echo htmlspecialchars($nombre); ?>!</h1>

        <!-- Role Switcher -->
        <?php if (isset($_SESSION['rol_dual']) && $_SESSION['rol_dual']): ?>
            <div class="mb-4">
                <div class="alert alert-info py-3">
                    <div class="d-flex flex-column align-items-center text-center">
                        <div class="mb-2">
                            <i class="bi bi-people-fill"></i> Acceso dual:
                        </div>
                        <div class="btn-group">
                            <a href="../cliente/dashboard.php"
                                class="btn <?php echo $_SESSION['rol'] == 'cliente' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                Cliente
                            </a>
                            <a href="../pwa/dashboard.php"
                                class="btn <?php echo $_SESSION['rol'] == 'repartidor' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                Repartidor
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Resumen de estadísticas -->
        <div class="stat-row">
            <div class="stat-card bg-warning text-dark">
                <h2><?php echo count($envios_pendientes); ?></h2>
                <p>Envíos Pendientes</p>
            </div>

            <div class="stat-card bg-success text-white">
                <h2 id="entregasHoy">0</h2>
                <p>Entregas hoy</p>
            </div>
        </div>

        <!-- Envíos pendientes -->
        <div class="section-header">
            <h2><i class="bi bi-clock-history"></i> Envíos pendientes</h2>
            <button class="btn btn-sm btn-outline-primary rounded-circle" id="refreshBtn">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>

        <div class="section-content" id="enviosPendientesContainer">
            <?php if (count($envios_pendientes) > 0): ?>
                <?php foreach ($envios_pendientes as $envio): ?>
                    <div class="envio-card <?php echo $envio['urgent'] ? 'urgent' : ''; ?>">
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
                            <?php echo htmlspecialchars($envio['destination']); ?>
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

        <!-- Entregas recientes -->
        <div class="section-header">
            <h2><i class="bi bi-check-circle"></i> Entregas recientes</h2>
        </div>

        <div class="section-content" id="enviosEntregadosContainer">
            <?php if (count($envios_entregados) > 0): ?>
                <?php foreach ($envios_entregados as $envio): ?>
                    <div class="envio-card bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($envio['tracking_number']); ?></h6>
                            <small class="text-muted"><?php echo date('d/m/Y', strtotime($envio['updated_at'])); ?></small>
                        </div>
                        <div class="small text-muted">
                            <i class="bi bi-person me-1"></i>
                            <?php echo htmlspecialchars($envio['name']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="info-alert">
                    <i class="bi bi-info-circle"></i>
                    <span>No tienes entregas registradas.</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Barra de navegación inferior (solo móvil) -->
    <nav class="navbar fixed-bottom navbar-expand-sm navbar-dark bg-primary p-0 shadow">
        <div class="container-fluid p-0">
            <div class="d-flex flex-row justify-content-around w-100">
                <a href="dashboard.php" class="nav-link text-center py-3 flex-fill active">
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
                <a href="mapa.php" class="nav-link text-center py-3 flex-fill">
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
    <script src="assets/js/pwa-init.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Cargar entregas de hoy
            cargarEntregasHoy();

            // Funcionalidad de actualización
            document.getElementById('refreshBtn').addEventListener('click', function() {
                window.location.reload();
            });
        });

        // Función para cargar entregas de hoy
        function cargarEntregasHoy() {
            fetch('api/envios.php?action=entregas_hoy')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('entregasHoy').textContent = data.count;
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    </script>
</body>

</html>