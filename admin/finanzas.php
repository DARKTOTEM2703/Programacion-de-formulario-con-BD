<?php
session_start();
require_once '../components/db_connection.php';

// Verificar si el usuario está autenticado como administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../php/login.php");
    exit();
}

// Verificar si existe la tabla movimientos_contables
$result = $conn->query("SHOW TABLES LIKE 'movimientos_contables'");
if ($result->num_rows == 0) {
    $_SESSION['error'] = "La tabla de movimientos contables no está disponible";
    header("Location: dashboard.php");
    exit();
}

// Periodo de análisis
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'mensual';
$fecha_inicio = date('Y-m-01'); // Primer día del mes actual
$fecha_fin = date('Y-m-t'); // Último día del mes actual

switch ($periodo) {
    case 'trimestral':
        $fecha_inicio = date('Y-m-d', strtotime('-3 months'));
        break;
    case 'anual':
        $fecha_inicio = date('Y-01-01'); // Primer día del año
        $fecha_fin = date('Y-12-31'); // Último día del año
        break;
    case 'personalizado':
        $fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : $fecha_inicio;
        $fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : $fecha_fin;
        break;
}

// Obtener resumen financiero
$stmt = $conn->prepare("
    SELECT SUM(monto) as total_ingresos
    FROM facturas
    WHERE status = 'pagado'
    AND fecha_pago BETWEEN ? AND ?
");
$stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt->execute();
$result = $stmt->get_result();
$total_ingresos = $result->fetch_assoc()['total_ingresos'] ?? 0;

$stmt = $conn->prepare("
    SELECT SUM(monto) as total_gastos
    FROM movimientos_contables
    WHERE tipo = 'egreso'
    AND fecha_movimiento BETWEEN ? AND ?
");
$stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt->execute();
$result = $stmt->get_result();
$total_gastos = $result->fetch_assoc()['total_gastos'] ?? 0;

$balance = $total_ingresos - $total_gastos;

// Obtener desglose de ingresos por categoría
$stmt = $conn->prepare("
    SELECT categoria, SUM(monto) as total
    FROM movimientos_contables
    WHERE tipo = 'ingreso'
    AND fecha_movimiento BETWEEN ? AND ?
    GROUP BY categoria
    ORDER BY total DESC
");
$stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt->execute();
$ingresos_por_categoria = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener desglose de gastos por categoría
$stmt = $conn->prepare("
    SELECT categoria, SUM(monto) as total
    FROM movimientos_contables
    WHERE tipo = 'egreso'
    AND fecha_movimiento BETWEEN ? AND ?
    GROUP BY categoria
    ORDER BY total DESC
");
$stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt->execute();
$gastos_por_categoria = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener pagos pendientes
$stmt = $conn->prepare("
    SELECT f.*, e.tracking_number, e.name AS cliente_nombre
    FROM facturas f
    JOIN envios e ON f.envio_id = e.id
    WHERE f.status = 'pendiente'
    ORDER BY f.fecha_vencimiento ASC
    LIMIT 5
");
$stmt->execute();
$pagos_pendientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Panel de Administración - MENDEZ'; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/admin.css">
</head>

<body>
    <!-- Botón hamburguesa para dispositivos móviles -->
    <button class="toggle-sidebar" id="toggleSidebar">
        <i class="bi bi-list"></i>
    </button>

    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid py-4">
            <h1 class="mb-4"><i class="bi bi-graph-up-arrow"></i> Panel Financiero</h1>

            <!-- Filtro de periodo -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="periodo" class="form-label">Periodo</label>
                            <select name="periodo" id="periodo" class="form-select"
                                onchange="toggleFechasPersonalizadas()">
                                <option value="mensual" <?php echo $periodo == 'mensual' ? 'selected' : ''; ?>>Este mes
                                </option>
                                <option value="trimestral" <?php echo $periodo == 'trimestral' ? 'selected' : ''; ?>>
                                    Últimos 3 meses</option>
                                <option value="anual" <?php echo $periodo == 'anual' ? 'selected' : ''; ?>>Este año
                                </option>
                                <option value="personalizado"
                                    <?php echo $periodo == 'personalizado' ? 'selected' : ''; ?>>Personalizado</option>
                            </select>
                        </div>
                        <div class="col-md-3 fechas-personalizadas"
                            style="display: <?php echo $periodo == 'personalizado' ? 'block' : 'none'; ?>">
                            <label for="fecha_inicio" class="form-label">Fecha inicio</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio"
                                value="<?php echo $fecha_inicio; ?>">
                        </div>
                        <div class="col-md-3 fechas-personalizadas"
                            style="display: <?php echo $periodo == 'personalizado' ? 'block' : 'none'; ?>">
                            <label for="fecha_fin" class="form-label">Fecha fin</label>
                            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                                value="<?php echo $fecha_fin; ?>">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Aplicar</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Resumen financiero -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Total Ingresos</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        $<?php echo number_format($total_ingresos, 2); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-arrow-up-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        Total Gastos</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        $<?php echo number_format($total_gastos, 2); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-arrow-down-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Balance Neto</div>
                                    <div
                                        class="h5 mb-0 font-weight-bold <?php echo $balance >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        $<?php echo number_format($balance, 2); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-calculator fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos y tablas -->
            <div class="row">
                <!-- Gráfico de ingresos vs gastos -->
                <div class="col-xl-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Ingresos vs Gastos</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-area">
                                <canvas id="ingresoGastoChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de pagos pendientes -->
                <div class="col-xl-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Pagos Pendientes</h6>
                            <a href="facturas.php?status=pendiente" class="btn btn-sm btn-primary">Ver todos</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Factura</th>
                                            <th>Cliente</th>
                                            <th>Monto</th>
                                            <th>Vencimiento</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($pagos_pendientes) > 0): ?>
                                            <?php foreach ($pagos_pendientes as $factura): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($factura['numero_factura']); ?></td>
                                                    <td><?php echo htmlspecialchars($factura['cliente_nombre']); ?></td>
                                                    <td>$<?php echo number_format($factura['monto'], 2); ?></td>
                                                    <td>
                                                        <?php
                                                        $vencimiento = new DateTime($factura['fecha_vencimiento']);
                                                        $hoy = new DateTime();
                                                        $diff = $hoy->diff($vencimiento);

                                                        echo date('d/m/Y', strtotime($factura['fecha_vencimiento']));

                                                        if ($vencimiento < $hoy) {
                                                            echo ' <span class="badge bg-danger">Vencido</span>';
                                                        } elseif ($diff->days <= 7) {
                                                            echo ' <span class="badge bg-warning">Próximo</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-3">No hay pagos pendientes</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Desglose de categorías -->
                <div class="col-xl-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Ingresos por Categoría</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-pie mb-4">
                                <canvas id="ingresosChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Gastos por Categoría</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-pie mb-4">
                                <canvas id="gastosChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function toggleFechasPersonalizadas() {
            const periodo = document.getElementById('periodo').value;
            const fechasPersonalizadas = document.querySelectorAll('.fechas-personalizadas');

            fechasPersonalizadas.forEach(element => {
                element.style.display = periodo === 'personalizado' ? 'block' : 'none';
            });
        }

        // Gráfico de ingresos vs gastos
        const ingresoGastoCtx = document.getElementById('ingresoGastoChart').getContext('2d');
        new Chart(ingresoGastoCtx, {
            type: 'bar',
            data: {
                labels: ['Ingresos', 'Gastos', 'Balance'],
                datasets: [{
                    data: [
                        <?php echo $total_ingresos; ?>,
                        <?php echo $total_gastos; ?>,
                        <?php echo $balance; ?>
                    ],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.7)',
                        'rgba(220, 53, 69, 0.7)',
                        <?php echo $balance >= 0 ? 'rgba(23, 162, 184, 0.7)' : 'rgba(255, 193, 7, 0.7)'; ?>
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(220, 53, 69, 1)',
                        <?php echo $balance >= 0 ? 'rgba(23, 162, 184, 1)' : 'rgba(255, 193, 7, 1)'; ?>
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Gráficos de categorías
        const ingresosCtx = document.getElementById('ingresosChart').getContext('2d');
        new Chart(ingresosCtx, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php foreach ($ingresos_por_categoria as $categoria): ?> '<?php echo ucfirst($categoria['categoria']); ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    data: [
                        <?php foreach ($ingresos_por_categoria as $categoria): ?>
                            <?php echo $categoria['total']; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 159, 64, 0.7)',
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(201, 203, 207, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        const gastosCtx = document.getElementById('gastosChart').getContext('2d');
        new Chart(gastosCtx, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php foreach ($gastos_por_categoria as $categoria): ?> '<?php echo ucfirst($categoria['categoria']); ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    data: [
                        <?php foreach ($gastos_por_categoria as $categoria): ?>
                            <?php echo $categoria['total']; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(255, 159, 64, 0.7)',
                        'rgba(255, 205, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(153, 102, 255, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
    <script>
        // Asegúrate de que este script esté al final de tu archivo o incluido en footer.php
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle sidebar en móvil
            const toggleButton = document.getElementById('toggleSidebar');
            const sidebar = document.querySelector('.sidebar');

            toggleButton.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                // Añadir animación sutil al botón
                this.classList.add('clicked');
                setTimeout(() => {
                    this.classList.remove('clicked');
                }, 300);
            });

            // Cerrar sidebar al hacer clic en el contenido principal en móvil
            document.querySelector('.main-content').addEventListener('click', function() {
                if (window.innerWidth < 992 && sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                }
            });
        });
    </script>
</body>

</html>