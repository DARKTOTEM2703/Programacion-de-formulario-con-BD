<?php
// Verificar autenticación
require_once 'includes/auth_check.php';

// Obtener estadísticas del sistema
require_once dirname(__DIR__) . '/components/db_connection.php';

// Obtener total de envíos
$stmt = $conn->prepare("SELECT 
    COUNT(*) as total_envios,
    SUM(CASE WHEN status = 'Entregado' THEN 1 ELSE 0 END) as envios_entregados,
    SUM(CASE WHEN status = 'En tránsito' THEN 1 ELSE 0 END) as envios_en_transito,
    SUM(CASE WHEN status = 'Pendiente' THEN 1 ELSE 0 END) as envios_pendientes,
    SUM(CASE WHEN urgent = 1 THEN 1 ELSE 0 END) as envios_urgentes
FROM envios");
$stmt->execute();
$envios_stats = $stmt->get_result()->fetch_assoc();

// Obtener usuarios activos por rol
$stmt = $conn->prepare("SELECT 
    COUNT(CASE WHEN rol_id = 1 THEN 1 END) as num_admins,
    COUNT(CASE WHEN rol_id = 2 THEN 1 END) as num_clientes,
    COUNT(CASE WHEN rol_id = 3 THEN 1 END) as num_repartidores,
    COUNT(CASE WHEN rol_id = 4 THEN 1 END) as num_duales
FROM usuarios WHERE status = 'activo'");
$stmt->execute();
$usuarios_stats = $stmt->get_result()->fetch_assoc();

// Obtener repartidores pendientes de aprobación
$stmt = $conn->prepare("SELECT COUNT(*) as repartidores_pendientes FROM repartidores WHERE status = 'pendiente'");
$stmt->execute();
$pendientes = $stmt->get_result()->fetch_assoc()['repartidores_pendientes'];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo - MENDEZ Transportes</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <!-- Estilos propios -->
    <link rel="stylesheet" href="assets/css/admin-style.css">

    <!-- Prevención XSS en CSS -->
    <style nonce="<?php echo $_SESSION['admin_csrf_token']; ?>">
        /* Estilos adicionales si son necesarios */
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar (incluido en header.php) -->

            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1>Panel de Control</h1>

                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-download"></i> Exportar
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" id="export-csv">CSV</a></li>
                                <li><a class="dropdown-item" href="#" id="export-pdf">PDF</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Alertas -->
                <?php if ($pendientes > 0): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Atención:</strong>
                        Hay <?php echo $pendientes; ?> repartidores pendientes de aprobación.
                        <a href="drivers/pending.php" class="btn btn-sm btn-outline-dark ms-3">Revisar</a>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Tarjetas de estadísticas -->
                <div class="row">
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total de Envíos</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $envios_stats['total_envios']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-box-seam fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Más tarjetas de estadísticas... -->
                </div>

                <!-- Secciones adicionales... -->
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Script propio con nonce para CSP -->
    <script nonce="<?php echo $_SESSION['admin_csrf_token']; ?>">
        // Scripts para dashboard
    </script>
</body>

</html>