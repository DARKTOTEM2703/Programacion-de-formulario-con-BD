<?php
session_start();
require_once '../components/db_connection.php';

// Verificar si el usuario está autenticado como administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../php/login.php");
    exit();
}

// Procesamiento de acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $repartidor_id = $_POST['repartidor_id'] ?? 0;

    switch ($accion) {
        case 'aprobar':
            $stmt = $conn->prepare("UPDATE repartidores SET status = 'activo' WHERE usuario_id = ?");
            $stmt->bind_param("i", $repartidor_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Repartidor aprobado correctamente";
            } else {
                $_SESSION['error'] = "Error al aprobar el repartidor";
            }
            break;
            
        case 'suspender':
            $stmt = $conn->prepare("UPDATE repartidores SET status = 'suspendido' WHERE usuario_id = ?");
            $stmt->bind_param("i", $repartidor_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Repartidor suspendido correctamente";
            } else {
                $_SESSION['error'] = "Error al suspender el repartidor";
            }
            break;
            
        case 'eliminar':
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("DELETE FROM repartidores WHERE usuario_id = ?");
                $stmt->bind_param("i", $repartidor_id);
                $stmt->execute();
                
                $stmt = $conn->prepare("UPDATE usuarios SET status = 'inactivo' WHERE id = ?");
                $stmt->bind_param("i", $repartidor_id);
                $stmt->execute();
                
                $conn->commit();
                $_SESSION['success'] = "Repartidor eliminado correctamente";
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error'] = "Error al eliminar el repartidor";
            }
            break;
    }
}

// Obtener estadísticas de repartidores
$stats = [
    'total_repartidores' => 0,
    'repartidores_activos' => 0,
    'repartidores_pendientes' => 0,
    'repartidores_suspendidos' => 0
];

$result = $conn->query("SELECT COUNT(*) as total FROM repartidores");
$stats['total_repartidores'] = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT COUNT(*) as total FROM repartidores WHERE status = 'activo'");
$stats['repartidores_activos'] = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT COUNT(*) as total FROM repartidores WHERE status = 'pendiente'");
$stats['repartidores_pendientes'] = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT COUNT(*) as total FROM repartidores WHERE status = 'suspendido'");
$stats['repartidores_suspendidos'] = $result->fetch_assoc()['total'] ?? 0;

// Obtener lista de repartidores
$filtro_status = $_GET['status'] ?? '';
$busqueda = $_GET['buscar'] ?? '';

$where_conditions = ["r.id IS NOT NULL"];
$params = [];
$types = "";

if ($filtro_status) {
    $where_conditions[] = "r.status = ?";
    $params[] = $filtro_status;
    $types .= "s";
}

if ($busqueda) {
    $where_conditions[] = "(u.nombre_usuario LIKE ? OR u.email LIKE ? OR r.telefono LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $types .= "sss";
}

$where_clause = implode(" AND ", $where_conditions);

$query = "
    SELECT u.id, u.nombre_usuario, u.email, u.created_at,
           r.telefono, r.vehiculo, r.placa, r.status, r.capacidad_carga, 
           r.tipo_licencia, r.anos_experiencia,
           COUNT(re.envio_id) as envios_asignados
    FROM usuarios u
    INNER JOIN repartidores r ON u.id = r.usuario_id
    LEFT JOIN repartidores_envios re ON u.id = re.usuario_id
    WHERE $where_clause
    GROUP BY u.id, u.nombre_usuario, u.email, u.created_at, r.telefono, r.vehiculo, r.placa, r.status
    ORDER BY r.status DESC, u.created_at DESC
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$repartidores = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Repartidores - MENDEZ</title>
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
            <h1 class="mb-4"><i class="bi bi-truck"></i> Gestión de Repartidores</h1>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Repartidores</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['total_repartidores']; ?></div>
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
                                        Activos</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['repartidores_activos']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-check-circle fa-2x text-gray-300"></i>
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
                                        Pendientes</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['repartidores_pendientes']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-clock fa-2x text-gray-300"></i>
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
                                        Suspendidos</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['repartidores_suspendidos']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-x-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mensajes -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Lista de repartidores -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Lista de Repartidores</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="buscar" class="form-label">Buscar Repartidor</label>
                            <input type="text" class="form-control" id="buscar" name="buscar" 
                                   value="<?php echo htmlspecialchars($busqueda); ?>" 
                                   placeholder="Nombre, email o teléfono...">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Estado</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Todos</option>
                                <option value="activo" <?php echo $filtro_status == 'activo' ? 'selected' : ''; ?>>Activos</option>
                                <option value="pendiente" <?php echo $filtro_status == 'pendiente' ? 'selected' : ''; ?>>Pendientes</option>
                                <option value="suspendido" <?php echo $filtro_status == 'suspendido' ? 'selected' : ''; ?>>Suspendidos</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                            <a href="repartidores.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise"></i> Limpiar
                            </a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Repartidor</th>
                                    <th>Contacto</th>
                                    <th>Vehículo</th>
                                    <th>Licencia</th>
                                    <th>Estado</th>
                                    <th>Envíos Asignados</th>
                                    <th>Fecha Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($repartidores)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <i class="bi bi-inbox fs-1 text-muted"></i>
                                            <p class="mt-2">No se encontraron repartidores</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($repartidores as $repartidor): ?>
                                        <tr>
                                            <td><?php echo $repartidor['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($repartidor['nombre_usuario']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($repartidor['email']); ?></small>
                                            </td>
                                            <td>
                                                <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($repartidor['telefono'] ?? 'No disponible'); ?>
                                            </td>
                                            <td>
                                                <?php if ($repartidor['vehiculo']): ?>
                                                    <strong><?php echo htmlspecialchars($repartidor['vehiculo']); ?></strong><br>
                                                    <small>Placa: <?php echo htmlspecialchars($repartidor['placa'] ?? 'N/A'); ?></small><br>
                                                    <small>Carga: <?php echo htmlspecialchars($repartidor['capacidad_carga'] ?? 'N/A'); ?> kg</small>
                                                <?php else: ?>
                                                    <span class="text-muted">No especificado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($repartidor['tipo_licencia'] ?? 'No especificada'); ?><br>
                                                <small><?php echo $repartidor['anos_experiencia'] ? $repartidor['anos_experiencia'] . ' años exp.' : ''; ?></small>
                                            </td>
                                            <td>
                                                <span class="badge <?php 
                                                    echo match($repartidor['status']) {
                                                        'activo' => 'bg-success',
                                                        'pendiente' => 'bg-warning',
                                                        'suspendido' => 'bg-danger',
                                                        default => 'bg-secondary'
                                                    };
                                                ?>">
                                                    <?php echo ucfirst($repartidor['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $repartidor['envios_asignados']; ?></span>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($repartidor['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if ($repartidor['status'] == 'pendiente'): ?>
                                                        <button class="btn btn-outline-success" onclick="aprobarRepartidor(<?php echo $repartidor['id']; ?>)">
                                                            <i class="bi bi-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($repartidor['status'] == 'activo'): ?>
                                                        <button class="btn btn-outline-warning" onclick="suspenderRepartidor(<?php echo $repartidor['id']; ?>)">
                                                            <i class="bi bi-pause"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <button class="btn btn-outline-danger" onclick="eliminarRepartidor(<?php echo $repartidor['id']; ?>)">
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

    <!-- Formulario oculto para acciones -->
    <form id="actionForm" method="POST" style="display: none;">
        <input type="hidden" name="accion" id="accion">
        <input type="hidden" name="repartidor_id" id="repartidor_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function aprobarRepartidor(repartidorId) {
            if (confirm('¿Está seguro que desea aprobar este repartidor?')) {
                document.getElementById('accion').value = 'aprobar';
                document.getElementById('repartidor_id').value = repartidorId;
                document.getElementById('actionForm').submit();
            }
        }

        function suspenderRepartidor(repartidorId) {
            if (confirm('¿Está seguro que desea suspender este repartidor?')) {
                document.getElementById('accion').value = 'suspender';
                document.getElementById('repartidor_id').value = repartidorId;
                document.getElementById('actionForm').submit();
            }
        }

        function eliminarRepartidor(repartidorId) {
            if (confirm('¿Está seguro que desea eliminar este repartidor? Esta acción no se puede deshacer.')) {
                document.getElementById('accion').value = 'eliminar';
                document.getElementById('repartidor_id').value = repartidorId;
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