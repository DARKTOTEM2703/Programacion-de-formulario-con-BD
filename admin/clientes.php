<?php
session_start();
require_once '../components/db_connection.php';

// Verificar si el usuario está autenticado como administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../php/login.php");
    exit();
}

// Procesamiento de acciones (activar/desactivar/eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $cliente_id = $_POST['cliente_id'] ?? 0;

    switch ($accion) {
        case 'toggle_status':
            $nuevo_status = $_POST['nuevo_status'] ?? 'activo';
            $stmt = $conn->prepare("UPDATE usuarios SET status = ? WHERE id = ? AND rol_id = 2");
            $stmt->bind_param("si", $nuevo_status, $cliente_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Estado del cliente actualizado correctamente";
            } else {
                $_SESSION['error'] = "Error al actualizar el estado del cliente";
            }
            break;

        case 'eliminar':
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ? AND rol_id = 2");
            $stmt->bind_param("i", $cliente_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Cliente eliminado correctamente";
            } else {
                $_SESSION['error'] = "Error al eliminar el cliente";
            }
            break;
    }
}

// Obtener estadísticas de clientes
$stats = [
    'total_clientes' => 0,
    'clientes_activos' => 0,
    'clientes_inactivos' => 0,
    'nuevos_este_mes' => 0
];

$result = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol_id = 2");
$stats['total_clientes'] = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol_id = 2 AND status = 'activo'");
$stats['clientes_activos'] = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol_id = 2 AND status = 'inactivo'");
$stats['clientes_inactivos'] = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol_id = 2 AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$stats['nuevos_este_mes'] = $result->fetch_assoc()['total'] ?? 0;

// Obtener lista de clientes con información adicional
$filtro_status = $_GET['status'] ?? '';
$busqueda = $_GET['buscar'] ?? '';

$where_conditions = ["u.rol_id = 2"];
$params = [];
$types = "";

if ($filtro_status) {
    $where_conditions[] = "u.status = ?";
    $params[] = $filtro_status;
    $types .= "s";
}

if ($busqueda) {
    $where_conditions[] = "(u.nombre_usuario LIKE ? OR u.email LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $types .= "ss";
}

$where_clause = implode(" AND ", $where_conditions);

$query = "
    SELECT u.id, u.nombre_usuario, u.email, u.status, u.created_at,
           COUNT(e.id) as total_envios,
           COALESCE(SUM(e.estimated_cost), 0) as total_gastado,
           MAX(e.created_at) as ultimo_envio
    FROM usuarios u
    LEFT JOIN envios e ON u.id = e.usuario_id
    WHERE $where_clause
    GROUP BY u.id, u.nombre_usuario, u.email, u.status, u.created_at
    ORDER BY u.created_at DESC
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$clientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes - MENDEZ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/admin.css">
</head>

<body>
    <button class="toggle-sidebar" id="toggleSidebar">
        <i class="bi bi-list"></i>
    </button>

    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid py-4">
            <h1 class="mb-4"><i class="bi bi-people"></i> Gestión de Clientes</h1>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Clientes</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['total_clientes']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-people fa-2x text-gray-300"></i>
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
                                        Clientes Activos</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['clientes_activos']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-person-check fa-2x text-gray-300"></i>
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
                                        Clientes Inactivos</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['clientes_inactivos']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-person-x fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Nuevos (Este Mes)</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['nuevos_este_mes']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-person-plus fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mensajes de éxito/error -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success'];
                    unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error'];
                    unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filtros y búsqueda -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Lista de Clientes</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="buscar" class="form-label">Buscar Cliente</label>
                            <input type="text" class="form-control" id="buscar" name="buscar"
                                value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Nombre o email...">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Estado</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Todos</option>
                                <option value="activo" <?php echo $filtro_status == 'activo' ? 'selected' : ''; ?>>
                                    Activos</option>
                                <option value="inactivo" <?php echo $filtro_status == 'inactivo' ? 'selected' : ''; ?>>
                                    Inactivos</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                            <a href="clientes.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise"></i> Limpiar
                            </a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Email</th>
                                    <th>Estado</th>
                                    <th>Total Envíos</th>
                                    <th>Total Gastado</th>
                                    <th>Último Envío</th>
                                    <th>Fecha Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($clientes)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <i class="bi bi-inbox fs-1 text-muted"></i>
                                            <p class="mt-2">No se encontraron clientes</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <tr>
                                            <td><?php echo $cliente['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($cliente['nombre_usuario']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                                            <td>
                                                <span
                                                    class="badge <?php echo $cliente['status'] == 'activo' ? 'bg-success' : 'bg-warning'; ?>">
                                                    <?php echo ucfirst($cliente['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $cliente['total_envios']; ?></span>
                                            </td>
                                            <td>$<?php echo number_format($cliente['total_gastado'], 2); ?></td>
                                            <td>
                                                <?php echo $cliente['ultimo_envio'] ? date('d/m/Y', strtotime($cliente['ultimo_envio'])) : 'Nunca'; ?>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($cliente['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary"
                                                        onclick="verDetalles(<?php echo $cliente['id']; ?>)">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button
                                                        class="btn <?php echo $cliente['status'] == 'activo' ? 'btn-outline-warning' : 'btn-outline-success'; ?>"
                                                        onclick="toggleStatus(<?php echo $cliente['id']; ?>, '<?php echo $cliente['status'] == 'activo' ? 'inactivo' : 'activo'; ?>')">
                                                        <i
                                                            class="bi <?php echo $cliente['status'] == 'activo' ? 'bi-pause' : 'bi-play'; ?>"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger"
                                                        onclick="eliminarCliente(<?php echo $cliente['id']; ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formularios ocultos para acciones -->
    <form id="actionForm" method="POST" style="display: none;">
        <input type="hidden" name="accion" id="accion">
        <input type="hidden" name="cliente_id" id="cliente_id">
        <input type="hidden" name="nuevo_status" id="nuevo_status">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function verDetalles(clienteId) {
            // Redirigir a página de detalles del cliente (por implementar)
            window.location.href = `detalle_cliente.php?id=${clienteId}`;
        }

        function toggleStatus(clienteId, nuevoStatus) {
            if (confirm(`¿Está seguro que desea ${nuevoStatus == 'activo' ? 'activar' : 'desactivar'} este cliente?`)) {
                document.getElementById('accion').value = 'toggle_status';
                document.getElementById('cliente_id').value = clienteId;
                document.getElementById('nuevo_status').value = nuevoStatus;
                document.getElementById('actionForm').submit();
            }
        }

        function eliminarCliente(clienteId) {
            if (confirm('¿Está seguro que desea eliminar este cliente? Esta acción no se puede deshacer.')) {
                document.getElementById('accion').value = 'eliminar';
                document.getElementById('cliente_id').value = clienteId;
                document.getElementById('actionForm').submit();
            }
        }

        // Toggle sidebar
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });
    </script>
</body>

</html>