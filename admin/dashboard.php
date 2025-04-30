<?php
session_start();
require_once '../components/db_connection.php';

// Verificar si el usuario está autenticado como administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../php/login.php");
    exit();
}

// Obtener estadísticas para el dashboard
$stats = [
    'envios_total' => 0,
    'facturas_pendientes' => 0,
    'facturas_pagadas' => 0,
    'ingresos_mes' => 0,
    'gastos_mes' => 0
];

// Total de envíos
$result = $conn->query("SELECT COUNT(*) as total FROM envios");
$stats['envios_total'] = $result->fetch_assoc()['total'];

// Facturas pendientes
$result = $conn->query("SELECT COUNT(*) as total FROM facturas WHERE status='pendiente'");
if ($result) {
    $stats['facturas_pendientes'] = $result->fetch_assoc()['total'] ?? 0;
} else {
    // Si la tabla facturas no existe aún
    $stats['facturas_pendientes'] = 0;
}

// Facturas pagadas
$result = $conn->query("SELECT COUNT(*) as total FROM facturas WHERE status='pagado'");
if ($result) {
    $stats['facturas_pagadas'] = $result->fetch_assoc()['total'] ?? 0;
} else {
    $stats['facturas_pagadas'] = 0;
}

// Ingresos del mes
$result = $conn->query("SELECT SUM(monto) as total FROM facturas 
                       WHERE status='pagado' 
                       AND MONTH(fecha_pago) = MONTH(CURRENT_DATE()) 
                       AND YEAR(fecha_pago) = YEAR(CURRENT_DATE())");
if ($result) {
    $stats['ingresos_mes'] = $result->fetch_assoc()['total'] ?? 0;
} else {
    $stats['ingresos_mes'] = 0;
}

// Gastos del mes (esto requerirá una tabla de movimientos_contables)
$result = $conn->query("SHOW TABLES LIKE 'movimientos_contables'");
if ($result->num_rows > 0) {
    $result = $conn->query("SELECT SUM(monto) as total FROM movimientos_contables 
                          WHERE tipo='egreso' 
                          AND MONTH(fecha_movimiento) = MONTH(CURRENT_DATE()) 
                          AND YEAR(fecha_movimiento) = YEAR(CURRENT_DATE())");
    $stats['gastos_mes'] = $result->fetch_assoc()['total'] ?? 0;
} else {
    $stats['gastos_mes'] = 0;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - MENDEZ</title>
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
            <h1 class="mb-4"><i class="bi bi-speedometer2"></i> Dashboard</h1>

            <div class="row">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Envíos</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['envios_total']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-box-seam fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Facturas Pendientes</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['facturas_pendientes']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-receipt fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Ingresos (Mensual)</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        $<?php echo number_format($stats['ingresos_mes'], 2); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-currency-dollar fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        Gastos (Mensual)</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        $<?php echo number_format($stats['gastos_mes'], 2); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-credit-card fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-xl-8 col-lg-7">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Ingresos y Gastos</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-area">
                                <canvas id="earningsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-lg-5">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Estado de Facturas</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-pie pt-4 pb-2">
                                <canvas id="invoiceStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Facturas Recientes</h6>
                            <a href="facturas.php" class="btn btn-sm btn-primary">Ver todas</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nº Factura</th>
                                            <th>Cliente</th>
                                            <th>Monto</th>
                                            <th>Fecha</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $result = $conn->query("SHOW TABLES LIKE 'facturas'");
                                        if ($result->num_rows > 0) {
                                            $result = $conn->query("
                                                SELECT f.*, e.name AS cliente_nombre 
                                                FROM facturas f
                                                JOIN envios e ON f.envio_id = e.id
                                                ORDER BY f.fecha_emision DESC LIMIT 5
                                            ");

                                            if ($result && $result->num_rows > 0) {
                                                while ($factura = $result->fetch_assoc()) {
                                                    echo '<tr>';
                                                    echo '<td>' . htmlspecialchars($factura['numero_factura']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($factura['cliente_nombre']) . '</td>';
                                                    echo '<td>$' . number_format($factura['monto'], 2) . '</td>';
                                                    echo '<td>' . date('d/m/Y', strtotime($factura['fecha_emision'])) . '</td>';
                                                    echo '<td><span class="badge ' .
                                                        ($factura['status'] == 'pagado' ? 'bg-success' : ($factura['status'] == 'pendiente' ? 'bg-warning' : ($factura['status'] == 'vencido' ? 'bg-danger' : 'bg-secondary'))) .
                                                        '">' . ucfirst($factura['status']) . '</span></td>';
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="5" class="text-center">No hay facturas recientes</td></tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="5" class="text-center">Sistema de facturación no inicializado</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Agregar script para manejar el sidebar -->
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

    <script>
        // Gráfico de ingresos y gastos
        const ctx = document.getElementById('earningsChart').getContext('2d');
        const earningsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio'],
                datasets: [{
                    label: 'Ingresos',
                    data: [1500, 2000, 1700, 2200, 2400, 2800],
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 2,
                    tension: 0.1
                }, {
                    label: 'Gastos',
                    data: [1000, 1200, 1300, 1100, 1500, 1600],
                    backgroundColor: 'rgba(220, 53, 69, 0.2)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 2,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Gráfico de estado de facturas
        const pieCtx = document.getElementById('invoiceStatusChart').getContext('2d');
        const invoiceChart = new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pagadas', 'Pendientes', 'Vencidas'],
                datasets: [{
                    data: [
                        <?php echo $stats['facturas_pagadas']; ?>,
                        <?php echo $stats['facturas_pendientes']; ?>,
                        0
                    ],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(220, 53, 69, 0.7)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(220, 53, 69, 1)'
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
</body>

</html>