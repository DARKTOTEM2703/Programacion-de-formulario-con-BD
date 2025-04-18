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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Repartidor</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="manifest" href="manifest.json">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">App Repartidores</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">Hola, <?php echo htmlspecialchars($nombre); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Salir</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Role Switcher -->
        <?php if (isset($_SESSION['rol_dual']) && $_SESSION['rol_dual']): ?>
            <div class="role-switcher">
                <div class="alert alert-info">
                    <p><i class="fas fa-exchange-alt"></i> Tienes acceso a ambas interfaces:</p>
                    <div class="btn-group mt-2">
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
        <?php endif; ?>

        <!-- Resumen -->
        <div class="row mb-4">
            <div class="col-6">
                <div class="card bg-warning text-dark h-100">
                    <div class="card-body text-center">
                        <h3 class="card-title"><?php echo count($envios_pendientes); ?></h3>
                        <p class="card-text">Pendientes</p>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card bg-success text-white h-100">
                    <div class="card-body text-center">
                        <h3 class="card-title" id="entregasHoy">0</h3>
                        <p class="card-text">Entregas hoy</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Envíos pendientes -->
        <h5 class="mb-3"><i class="bi bi-clock-history"></i> Envíos pendientes</h5>
        <?php if (count($envios_pendientes) > 0): ?>
            <?php foreach ($envios_pendientes as $envio): ?>
                <div class="card mb-3 <?php echo $envio['urgent'] ? 'border-danger' : ''; ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title"><?php echo htmlspecialchars($envio['tracking_number']); ?></h6>
                            <?php if ($envio['urgent']): ?>
                                <span class="badge bg-danger">URGENTE</span>
                            <?php endif; ?>
                        </div>
                        <div class="mb-2">
                            <strong>Destino:</strong> <?php echo htmlspecialchars($envio['recipient_address']); ?>
                        </div>
                        <div class="mb-2">
                            <strong>Estado:</strong> <?php echo htmlspecialchars($envio['status']); ?>
                        </div>
                        <div class="d-grid gap-2">
                            <a href="detalle_envio.php?id=<?php echo $envio['id']; ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-box"></i> Ver detalles
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">No tienes envíos pendientes.</div>
        <?php endif; ?>

        <!-- Recientes -->
        <h5 class="mt-4 mb-3"><i class="bi bi-check-circle"></i> Entregas recientes</h5>
        <?php if (count($envios_entregados) > 0): ?>
            <?php foreach ($envios_entregados as $envio): ?>
                <div class="card mb-3 bg-light">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title"><?php echo htmlspecialchars($envio['tracking_number']); ?></h6>
                            <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($envio['updated_at'])); ?></small>
                        </div>
                        <div>
                            <strong>Destinatario:</strong> <?php echo htmlspecialchars($envio['recipient_name']); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">No tienes entregas registradas.</div>
        <?php endif; ?>
    </div>

    <!-- Barra de navegación inferior -->
    <nav class="navbar fixed-bottom navbar-dark bg-primary">
        <div class="container-fluid">
            <div class="row w-100">
                <div class="col text-center">
                    <a href="dashboard.php" class="nav-link active">
                        <i class="bi bi-house-door-fill"></i><br>
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
    <script src="assets/js/pwa-init.js"></script>
    <script>
        // Contador de entregas de hoy
        document.addEventListener('DOMContentLoaded', function() {
            fetch('api/envios.php?action=entregas_hoy')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('entregasHoy').textContent = data.count;
                    }
                })
                .catch(error => console.error('Error:', error));
        });
    </script>
</body>

</html>