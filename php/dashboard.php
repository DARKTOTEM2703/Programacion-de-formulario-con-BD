<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../components/db_connection.php';
$usuario_id = $_SESSION['usuario_id'];

// Obtener información del usuario
$query = "SELECT nombre_usuario FROM usuarios WHERE id = $usuario_id";
$result = $conn->query($query);
$nombre_usuario = "Usuario";
if ($result && $result->num_rows > 0) {
    $nombre_usuario = $result->fetch_assoc()['nombre_usuario'];
}

// Obtener estadísticas del usuario
$total_envios = 0;
$envios_pendientes = 0;
$envios_entregados = 0;
$gasto_total = 0;

// Total de envíos del usuario
$query = "SELECT COUNT(*) as total FROM envios WHERE usuario_id = $usuario_id";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    $total_envios = $result->fetch_assoc()['total'];
}

// Envíos pendientes (en Procesando)
$query = "SELECT COUNT(*) as total FROM envios WHERE usuario_id = $usuario_id AND status = 'Procesando'";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    $envios_pendientes = $result->fetch_assoc()['total'];
}

// Envíos entregados
$query = "SELECT COUNT(*) as total FROM envios WHERE usuario_id = $usuario_id AND status = 'Entregado'";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    $envios_entregados = $result->fetch_assoc()['total'];
}

// Gasto total en envíos
$query = "SELECT SUM(estimated_cost) as total FROM envios WHERE usuario_id = $usuario_id";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $gasto_total = $row['total'] ? $row['total'] : 0;
}

// Obtener datos para el gráfico - envíos por mes (últimos 6 meses)
$datos_meses = [];
$datos_envios = [];

for ($i = 5; $i >= 0; $i--) {
    $mes = date('m', strtotime("-$i month"));
    $anio = date('Y', strtotime("-$i month"));

    $meses_es = [
        '01' => 'Enero',
        '02' => 'Febrero',
        '03' => 'Marzo',
        '04' => 'Abril',
        '05' => 'Mayo',
        '06' => 'Junio',
        '07' => 'Julio',
        '08' => 'Agosto',
        '09' => 'Septiembre',
        '10' => 'Octubre',
        '11' => 'Noviembre',
        '12' => 'Diciembre'
    ];

    $datos_meses[] = $meses_es[$mes];

    $query = "SELECT COUNT(*) as total FROM envios 
              WHERE usuario_id = $usuario_id 
              AND MONTH(created_at) = $mes 
              AND YEAR(created_at) = $anio";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $datos_envios[] = $result->fetch_assoc()['total'];
    } else {
        $datos_envios[] = 0;
    }
}

// Obtener envíos activos del usuario
$envios_activos = [];
$query = "SELECT id, destination, tracking_number, created_at, status, estimated_cost FROM envios 
          WHERE usuario_id = $usuario_id AND status = 'Procesando'
          ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $envios_activos[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Cliente - MÉNDEZ Transportes</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/dashboard.css">

</head>

<body>
    <div class="app-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="#" class="sidebar-logo">
                    <img src="../img/logo.png" alt="MÉNDEZ Transportes" class="img-fluid">
                </a>
            </div>

            <div class="user-profile">
                <div class="user-avatar">
                    <i class="bi bi-person-circle avatar-icon"></i>
                </div>
                <h4 class="user-name"><?php echo htmlspecialchars($nombre_usuario); ?></h4>
                <div class="user-role">Cliente</div>
            </div>

            <ul class="sidebar-menu">
                <li><a href="#" class="active"><i class="bi bi-house-door"></i> Inicio</a></li>
                <li><a href="forms.php"><i class="bi bi-plus-circle"></i> Nuevo Envío</a></li>
                <li><a href="tracking.php"><i class="bi bi-search"></i> Rastrear</a></li>
                <li><a href="WatchData.php"><i class="bi bi-clock-history"></i> Historial</a></li>
                <li><a href="#"><i class="bi bi-calculator"></i> Cotizar</a></li>
                <li><a href="#"><i class="bi bi-headset"></i> Soporte</a></li>
            </ul>

            <div class="sidebar-footer">
                <button onclick="window.location.href='logout.php'" class="btn btn-logout">
                    <i class="bi bi-box-arrow-right"></i> Cerrar sesión
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content" id="main-content">
            <!-- Header -->
            <div class="main-header">
                <div class="d-flex align-items-center">
                    <button id="toggleSidebar" class="toggle-sidebar">
                        <i class="bi bi-list"></i>
                    </button>
                    <h1 class="page-title">Panel de Cliente</h1>
                </div>
                <div class="header-controls">
                    <button>
                        <i class="bi bi-bell"></i>
                        <span class="notification-badge">3</span>
                    </button>
                    <button>
                        <i class="bi bi-gear"></i>
                    </button>
                </div>
            </div>

            <!-- Welcome section -->
            <div class="welcome-section">
                <h2 class="welcome-title">¡Bienvenido de vuelta, <?php echo htmlspecialchars($nombre_usuario); ?>!</h2>
                <p class="welcome-subtitle">Servicio especializado en mudanzas y carga en general a todo México</p>
            </div>

            <!-- Statistics -->
            <div class="stats-container">
                <!-- Total Envíos -->
                <div class="stat-card stat-primary">
                    <div class="card-content">
                        <div class="stat-info">
                            <div class="stat-title">TOTAL ENVÍOS</div>
                            <div class="stat-value"><?php echo $total_envios; ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-box-seam"></i>
                        </div>
                    </div>
                </div>

                <!-- Envíos Pendientes -->
                <div class="stat-card stat-warning">
                    <div class="card-content">
                        <div class="stat-info">
                            <div class="stat-title">ENVÍOS PENDIENTES</div>
                            <div class="stat-value"><?php echo $envios_pendientes; ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>
                </div>

                <!-- Envíos Entregados -->
                <div class="stat-card stat-success">
                    <div class="card-content">
                        <div class="stat-info">
                            <div class="stat-title">ENVÍOS ENTREGADOS</div>
                            <div class="stat-value"><?php echo $envios_entregados; ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>

                <!-- Gasto Total -->
                <div class="stat-card stat-danger">
                    <div class="card-content">
                        <div class="stat-info">
                            <div class="stat-title">GASTO TOTAL</div>
                            <div class="stat-value">$<?php echo number_format($gasto_total, 2); ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart -->
            <div class="chart-container">
                <div class="panel-header">
                    <h3 class="panel-title"><i class="bi bi-bar-chart-line"></i> Historial de Envíos</h3>
                </div>
                <div class="chart-body">
                    <canvas id="enviosChart"></canvas>
                </div>
            </div>

            <!-- Main content layout -->
            <div class="dashboard-layout">
                <!-- Envíos activos -->
                <div class="shipments-panel">
                    <div class="panel-header">
                        <h3 class="panel-title"><i class="bi bi-boxes"></i> Mis Envíos Activos</h3>
                        <a href="WatchData.php" class="view-all-link">Ver todos</a>
                    </div>

                    <?php if (empty($envios_activos)): ?>
                        <p class="text-muted">No tienes envíos activos en este momento.</p>
                    <?php else: ?>
                        <?php foreach ($envios_activos as $envio): ?>
                            <div class="shipment-item">
                                <div class="shipment-title">
                                    <?php echo '#MÉNDEZ-' . substr($envio['tracking_number'] ?? bin2hex(random_bytes(4)), 0, 8); ?>
                                </div>
                                <div class="shipment-details">
                                    <span><?php echo htmlspecialchars($envio['destination']); ?></span>
                                    <span class="shipment-status status-processing">Procesando</span>
                                </div>
                                <span class="shipment-tracking">
                                    <?php echo date('d/m/Y', strtotime($envio['created_at'])); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Support Form -->
                <div class="support-panel">
                    <div class="panel-header">
                        <h3 class="panel-title"><i class="bi bi-headset"></i> Soporte Técnico</h3>
                    </div>

                    <form class="support-form" action="#" method="post">
                        <div class="form-group">
                            <label for="subject">Asunto</label>
                            <input type="text" id="subject" class="form-control"
                                placeholder="Describe brevemente tu consulta">
                        </div>

                        <div class="form-group">
                            <label for="message">Mensaje</label>
                            <textarea id="message" class="form-control"
                                placeholder="¿En qué podemos ayudarte?"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="operator">Operador (opcional)</label>
                            <select id="operator" class="form-control">
                                <option value="">Asignar automáticamente</option>
                                <option value="1">Atención al cliente</option>
                                <option value="2">Soporte técnico</option>
                                <option value="3">Facturación</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-submit">Enviar Consulta</button>
                    </form>

                    <div class="direct-contact">
                        <h4 class="contact-title">Contáctanos directamente</h4>
                        <ul class="contact-methods">
                            <li><i class="bi bi-telephone"></i> +52 999 123 4567</li>
                            <li><i class="bi bi-envelope"></i> soporte@mendez.com</li>
                            <li><i class="bi bi-whatsapp"></i> WhatsApp: 999 123 4567</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Datos para el gráfico -->
    <div id="datosEnvios" data-meses='<?php echo json_encode($datos_meses); ?>'
        data-envios='<?php echo json_encode($datos_envios); ?>' style="display: none;">
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../js/dashboard.js"></script>
</body>

</html>