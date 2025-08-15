<?php
session_start();
header('Content-Type: text/html; charset=UTF-8'); // AÑADIDO: header UTF-8
require_once '../components/db_connection.php';

// Verificar autenticación de administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../php/login.php");
    exit();
}

// AÑADIDO: Función para mostrar texto seguro UTF-8
function e($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8', false);
}

// ================== ACCIONES (REFORMULADAS A STORED PROCEDURES) ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {                     // Solo procesa si es POST (evita ejecuciones accidentales por GET)
    $accion  = $_POST['accion']   ?? '';                        // Lee la acción enviada desde los formularios ocultos o modal
    $envio_id = (int)($_POST['envio_id'] ?? 0);                 // Convierte a entero (sanitiza)
    switch ($accion) {                                          // Despacha según acción
        case 'asignar_repartidor':                              // Asignar / reasignar repartidor
            $repartidor_id = (int)($_POST['repartidor_id'] ?? 0); // ID de usuario repartidor
            if ($envio_id > 0 && $repartidor_id > 0) {          // Validación mínima
                $stmt = $conn->prepare(                         // Prepara llamada a SP
                  "CALL sp_asignar_repartidor(?,?,?,@ok,@msg)"); // SP maneja asignación + transición a 'En tránsito' si procede
                $actor = $_SESSION['usuario_id'];               // Admin que ejecuta
                $stmt->bind_param("iii",$envio_id,$repartidor_id,$actor); // Vincula parámetros IN
                if ($stmt->execute()) {                         // Ejecuta SP
                    $r = $conn->query("SELECT @ok ok,@msg msg")->fetch_assoc(); // Recupera OUT
                    $_SESSION[$r['ok']?'success':'error'] =     // Setea mensaje según resultado
                        $r['ok'] ? "Repartidor asignado" : $r['msg'];
                } else {
                    $_SESSION['error']="Error al llamar SP asignar"; // Falla SQL/SP
                }
                $stmt->close();                                 // Libera statement
            }
            break;

        case 'cambiar_estado':                                  // Cambio de estado controlado
            $nuevo_estado = $_POST['nuevo_estado'] ?? '';       // Estado destino
            $lat = null; $lng = null;                           // No se capturan coords en panel (puedes añadir inputs si quieres)
            $notes = 'Cambio manual panel';                     // Nota para auditoría
            if ($envio_id > 0 && $nuevo_estado !== '') {        // Validación
                $stmt = $conn->prepare(                         // Prepara SP de transición
                  "CALL sp_cambiar_estado_envio(?,?,?,?,?,?,@ok,@msg)");
                $user = $_SESSION['usuario_id'];                // Actor
                $stmt->bind_param("isidds",
                    $envio_id,                                  // p_envio_id
                    $nuevo_estado,                              // p_nuevo_estado
                    $user,                                      // p_usuario_id
                    $lat,                                       // p_lat
                    $lng,                                       // p_lng
                    $notes                                      // p_notes
                );
                if ($stmt->execute()) {                         // Ejecuta SP
                    $r = $conn->query("SELECT @ok ok,@msg msg")->fetch_assoc(); // OUT
                    $_SESSION[$r['ok']?'success':'error'] =
                        $r['ok'] ? "Estado actualizado" : $r['msg']; // Mensaje según validez transición
                } else {
                    $_SESSION['error']="Error al llamar SP estado"; // Error ejecutando
                }
                $stmt->close();
            }
            break;

        default:                                                // Acción desconocida
            $_SESSION['error']="Acción no válida";
    }
    header("Location: envios.php");                             // PRG pattern (evita reenvío en refresh)
    exit();
}
// ================== FIN ACCIONES (SP) ==================

// Obtener estadísticas
$stats = [
    'total_envios' => 0,
    'envios_pendientes' => 0,
    'envios_proceso' => 0,
    'envios_entregados' => 0
];

$result = $conn->query("SELECT COUNT(*) as total FROM envios");
$stats['total_envios'] = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT COUNT(*) as total FROM envios WHERE status = 'Procesando'");
$stats['envios_pendientes'] = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT COUNT(*) as total FROM envios WHERE status = 'En tránsito'");
$stats['envios_proceso'] = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT COUNT(*) as total FROM envios WHERE status = 'Entregado'");
$stats['envios_entregados'] = $result->fetch_assoc()['total'] ?? 0;

// Obtener lista de envíos
$filtro_status = $_GET['status'] ?? '';
$busqueda = $_GET['buscar'] ?? '';

$where_conditions = ["1=1"];
$params = [];
$types = "";

if ($filtro_status) {
    $where_conditions[] = "e.status = ?";
    $params[] = $filtro_status;
    $types .= "s";
}

if ($busqueda) {
    $where_conditions[] = "(e.tracking_number LIKE ? OR e.name LIKE ? OR e.destination LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $types .= "sss";
}

$where_clause = implode(" AND ", $where_conditions);

// vista_envios_detallados
// $query = "
//     SELECT e.*, u.nombre_usuario as cliente,
//            ur.nombre_usuario as repartidor_nombre,
//            re.fecha_asignacion
//     FROM envios e
//     LEFT JOIN usuarios u ON e.usuario_id = u.id
//     LEFT JOIN repartidores_envios re ON e.id = re.envio_id
//     LEFT JOIN usuarios ur ON re.usuario_id = ur.id
//     WHERE $where_clause
//     ORDER BY e.created_at DESC
//     LIMIT 50
// ";

$query = "
    SELECT * from vista_envios_detallados
    WHERE $where_clause
    ORDER BY created_at DESC
    LIMIT 50
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$envios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Función helper para obtener clase de status
function getStatusClass($status)
{
    return match ($status) {
        'Procesando' => 'procesando',
        'En tránsito' => 'transito',
        'Entregado' => 'entregado',
        'Cancelado' => 'cancelado',
        default => 'procesando'
    };
}

// Obtener repartidores activos para asignación
$repartidores_activos = [];
$stmt = $conn->prepare("CALL sp_obtener_repartidores_activos()");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $repartidores_activos[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8"> <!-- IMPORTANTE: Especifica UTF-8 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Envíos - MENDEZ</title>
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
            <h1 class="mb-4"><i class="bi bi-box-seam"></i> Gestión de Envíos</h1>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Envíos</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['total_envios']; ?></div>
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
                                        Pendientes</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['envios_pendientes']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-clock fa-2x text-gray-300"></i>
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
                                        En Proceso</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['envios_proceso']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-truck fa-2x text-gray-300"></i>
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
                                        Entregados</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['envios_entregados']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-check-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mensajes -->
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

            <!-- Lista de envíos -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Lista de Envíos</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="buscar" class="form-label">Buscar Envío</label>
                            <input type="text" class="form-control" id="buscar" name="buscar"
                                value="<?php echo htmlspecialchars($busqueda); ?>"
                                placeholder="Tracking, cliente o destino...">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Estado</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Todos</option>
                                <option value="Procesando"
                                    <?php echo $filtro_status == 'Procesando' ? 'selected' : ''; ?>>Procesando</option>
                                <option value="En tránsito"
                                    <?php echo $filtro_status == 'En tránsito' ? 'selected' : ''; ?>>En tránsito
                                </option>
                                <option value="Entregado"
                                    <?php echo $filtro_status == 'Entregado' ? 'selected' : ''; ?>>Entregado</option>
                                <option value="Cancelado"
                                    <?php echo $filtro_status == 'Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                            <a href="envios.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise"></i> Limpiar
                            </a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Tracking</th>
                                    <th>Cliente</th>
                                    <th>Destino</th>
                                    <th>Estado</th>
                                    <th>Repartidor</th>
                                    <th>Costo</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($envios)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="bi bi-inbox fs-1 text-muted"></i>
                                            <p class="mt-2">No se encontraron envíos</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($envios as $envio): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($envio['tracking_number']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($envio['cliente'] ?? $envio['name']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($envio['destination'], 0, 30)) . '...'; ?></td>
                                            <td>
                                                <span class="badge <?php
                                                                    echo match ($envio['status']) {
                                                                        'Procesando' => 'bg-warning',
                                                                        'En tránsito' => 'bg-info',
                                                                        'Entregado' => 'bg-success',
                                                                        'Cancelado' => 'bg-danger',
                                                                        default => 'bg-secondary'
                                                                    };
                                                                    ?>">
                                                    <?php echo e($envio['status']); ?> <!-- CAMBIADO: Usar e() -->
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($envio['repartidor_nombre']): ?>
                                                    <small><?php echo htmlspecialchars($envio['repartidor_nombre']); ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">Sin asignar</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>$<?php echo number_format($envio['estimated_cost'] ?? 0, 2); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($envio['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="detalle_envio.php?id=<?php echo $envio['id']; ?>" class="btn btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    
                                                    <?php if ($envio['status'] == 'Procesando'): ?>
                                                        <button class="btn btn-outline-success"
                                                            onclick="asignarRepartidor(<?php echo $envio['id']; ?>)">
                                                            <i class="bi bi-person-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" 
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="bi bi-gear"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <?php if ($envio['status'] == 'Procesando'): ?>
                                                            <li><button class="dropdown-item" 
                                                                onclick="cambiarEstado(<?php echo $envio['id']; ?>, 'En tránsito')">
                                                                Marcar en tránsito</button>
                                                            </li>
                                                        <?php endif; ?>
                                                        <?php if ($envio['status'] == 'En tránsito'): ?>
                                                            <li><button class="dropdown-item" 
                                                                onclick="cambiarEstado(<?php echo $envio['id']; ?>, 'Entregado')">
                                                                Marcar entregado</button>
                                                            </li>
                                                        <?php endif; ?>
                                                        <li><button class="dropdown-item text-danger"
                                                            onclick="cambiarEstado(<?php echo $envio['id']; ?>, 'Cancelado')">
                                                            Cancelar</button>
                                                        </li>
                                                    </ul>
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

    <!-- Modal para asignar repartidor -->
    <div class="modal fade" id="asignarRepartidorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Asignar Repartidor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="asignarForm" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="asignar_repartidor">
                        <input type="hidden" name="envio_id" id="modal_envio_id">

                        <div class="mb-3">
                            <label for="repartidor_id" class="form-label">Seleccionar Repartidor</label>
                            <select class="form-select" name="repartidor_id" required>
                                <option value="">-- Seleccione un repartidor --</option>
                                <?php foreach ($repartidores_activos as $repartidor): ?>
                                    <option value="<?php echo $repartidor['id']; ?>">
                                        <?php echo htmlspecialchars($repartidor['nombre_usuario']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Asignar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Formularios ocultos -->
    <form id="estadoForm" method="POST" style="display: none;">
        <input type="hidden" name="accion" value="cambiar_estado">
        <input type="hidden" name="envio_id" id="estado_envio_id">
        <input type="hidden" name="nuevo_estado" id="nuevo_estado">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function verDetalle(envioId) {
            window.location.href = `detalle_envio.php?id=${envioId}`;
        }

        function asignarRepartidor(envioId) {
            document.getElementById('modal_envio_id').value = envioId;
            new bootstrap.Modal(document.getElementById('asignarRepartidorModal')).show();
        }

        function cambiarEstado(envioId, nuevoEstado) {
            if (confirm(`¿Está seguro que desea cambiar el estado a "${nuevoEstado}"?`)) {
                document.getElementById('estado_envio_id').value = envioId;
                document.getElementById('nuevo_estado').value = nuevoEstado;
                document.getElementById('estadoForm').submit();
            }
        }

        // Toggle sidebar
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });
    </script>
</body>

</html>