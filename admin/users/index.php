<?php
require_once '../includes/auth_check.php';
require_once dirname(__DIR__, 2) . '/components/db_connection.php';

// Procesar acciones de gestión
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Error de verificación de seguridad. Por favor, inténtalo de nuevo.";
        header("Location: index.php");
        exit();
    }

    // Procesar acción según valor del botón
    if (isset($_POST['action'])) {
        $usuario_id = filter_var($_POST['usuario_id'], FILTER_VALIDATE_INT);

        if (!$usuario_id) {
            $_SESSION['error'] = "ID de usuario inválido";
            header("Location: index.php");
            exit();
        }

        switch ($_POST['action']) {
            case 'activar':
                $stmt = $conn->prepare("UPDATE usuarios SET status = 'activo' WHERE id = ?");
                $stmt->bind_param("i", $usuario_id);
                $stmt->execute();
                $_SESSION['success'] = "Usuario activado correctamente";
                break;

            case 'desactivar':
                $stmt = $conn->prepare("UPDATE usuarios SET status = 'suspendido' WHERE id = ?");
                $stmt->bind_param("i", $usuario_id);
                $stmt->execute();
                $_SESSION['success'] = "Usuario suspendido correctamente";
                break;

            case 'eliminar':
                // No eliminar físicamente, solo marcar como eliminado
                $stmt = $conn->prepare("UPDATE usuarios SET status = 'eliminado' WHERE id = ?");
                $stmt->bind_param("i", $usuario_id);
                $stmt->execute();
                $_SESSION['success'] = "Usuario eliminado correctamente";
                break;

            default:
                $_SESSION['error'] = "Acción no reconocida";
        }

        header("Location: index.php");
        exit();
    }
}

// Paginación
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filtros
$rol_filter = isset($_GET['rol']) ? intval($_GET['rol']) : 0;
$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Construir consulta SQL
$where_clauses = [];
$params = [];
$types = '';

if ($rol_filter > 0) {
    $where_clauses[] = "u.rol_id = ?";
    $params[] = $rol_filter;
    $types .= 'i';
}

if (!empty($status_filter)) {
    $where_clauses[] = "u.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($search)) {
    $where_clauses[] = "(u.nombre_usuario LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Obtener total de registros para paginación
$count_sql = "SELECT COUNT(*) FROM usuarios u $where_sql";
$stmt = $conn->prepare($count_sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$total_users = $stmt->get_result()->fetch_row()[0];
$total_pages = ceil($total_users / $limit);

// Obtener usuarios
$sql = "SELECT u.id, u.nombre_usuario, u.email, u.created_at, u.status, 
               r.nombre as rol_nombre, r.id as rol_id
        FROM usuarios u
        JOIN roles r ON u.rol_id = r.id
        $where_sql
        ORDER BY u.created_at DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

// Añadir parámetros de paginación
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt->bind_param($types, ...$params);
$stmt->execute();
$usuarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener lista de roles para filtro
$roles = $conn->query("SELECT id, nombre FROM roles")->fetch_all(MYSQLI_ASSOC);

// Regenerar token CSRF para formularios
$csrf_token = regenerateCSRFToken();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - MENDEZ Transportes</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin-style.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1>Gestión de Usuarios</h1>
                    <a href="add.php" class="btn btn-primary">
                        <i class="bi bi-person-plus"></i> Añadir Usuario
                    </a>
                </div>

                <!-- Mensajes de sesión -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="get" class="row g-3">
                            <div class="col-md-3">
                                <label for="rol" class="form-label">Rol</label>
                                <select class="form-select" id="rol" name="rol">
                                    <option value="0">Todos</option>
                                    <?php foreach ($roles as $rol): ?>
                                        <option value="<?php echo $rol['id']; ?>"
                                            <?php echo $rol_filter == $rol['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($rol['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="status" class="form-label">Estado</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Todos</option>
                                    <option value="activo" <?php echo $status_filter === 'activo' ? 'selected' : ''; ?>>
                                        Activo</option>
                                    <option value="suspendido"
                                        <?php echo $status_filter === 'suspendido' ? 'selected' : ''; ?>>Suspendido
                                    </option>
                                    <option value="pendiente"
                                        <?php echo $status_filter === 'pendiente' ? 'selected' : ''; ?>>Pendiente
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="search" class="form-label">Buscar</label>
                                <input type="text" class="form-control" id="search" name="search"
                                    value="<?php echo htmlspecialchars($search); ?>" placeholder="Nombre o email">
                            </div>

                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-filter"></i> Filtrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabla de usuarios -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Creado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($usuarios) > 0): ?>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <tr>
                                        <td><?php echo $usuario['id']; ?></td>
                                        <td><?php echo htmlspecialchars($usuario['nombre_usuario']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                        <td><span
                                                class="badge bg-<?php echo getBadgeColorForRole($usuario['rol_id']); ?>"><?php echo htmlspecialchars($usuario['rol_nombre']); ?></span>
                                        </td>
                                        <td><span
                                                class="badge bg-<?php echo getBadgeColorForStatus($usuario['status']); ?>"><?php echo htmlspecialchars($usuario['status']); ?></span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($usuario['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="edit.php?id=<?php echo $usuario['id']; ?>"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>

                                                <!-- Botones de acción con confirmación -->
                                                <?php if ($usuario['status'] === 'activo'): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-warning"
                                                        data-bs-toggle="modal" data-bs-target="#actionModal"
                                                        data-action="desactivar" data-id="<?php echo $usuario['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($usuario['nombre_usuario']); ?>">
                                                        <i class="bi bi-pause-circle"></i>
                                                    </button>
                                                <?php elseif ($usuario['status'] === 'suspendido'): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-success"
                                                        data-bs-toggle="modal" data-bs-target="#actionModal" data-action="activar"
                                                        data-id="<?php echo $usuario['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($usuario['nombre_usuario']); ?>">
                                                        <i class="bi bi-play-circle"></i>
                                                    </button>
                                                <?php endif; ?>

                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal" data-bs-target="#actionModal" data-action="eliminar"
                                                    data-id="<?php echo $usuario['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($usuario['nombre_usuario']); ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No hay usuarios que coincidan con los filtros
                                        aplicados</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Navegación de páginas">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link"
                                    href="?page=<?php echo $page - 1; ?>&rol=<?php echo $rol_filter; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>">Anterior</a>
                            </li>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link"
                                        href="?page=<?php echo $i; ?>&rol=<?php echo $rol_filter; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link"
                                    href="?page=<?php echo $page + 1; ?>&rol=<?php echo $rol_filter; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>">Siguiente</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Modal para confirmación de acciones -->
    <div class="modal fade" id="actionModal" tabindex="-1" aria-labelledby="actionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="actionModalLabel">Confirmar acción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="modalMessage">¿Estás seguro de que deseas realizar esta acción?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="usuario_id" id="usuarioId">
                        <input type="hidden" name="action" id="actionType">
                        <button type="submit" class="btn btn-danger">Confirmar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script nonce="<?php echo $_SESSION['admin_csrf_token']; ?>">
        // Función para asignar datos al modal
        document.addEventListener('DOMContentLoaded', function() {
            const actionModal = document.getElementById('actionModal');
            if (actionModal) {
                actionModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const action = button.getAttribute('data-action');
                    const id = button.getAttribute('data-id');
                    const name = button.getAttribute('data-name');

                    let actionText = '';
                    let buttonClass = '';

                    switch (action) {
                        case 'activar':
                            actionText = 'activar';
                            buttonClass = 'btn-success';
                            break;
                        case 'desactivar':
                            actionText = 'suspender';
                            buttonClass = 'btn-warning';
                            break;
                        case 'eliminar':
                            actionText = 'eliminar';
                            buttonClass = 'btn-danger';
                            break;
                    }

                    document.getElementById('modalMessage').textContent =
                        `¿Estás seguro de que deseas ${actionText} al usuario "${name}"?`;
                    document.getElementById('usuarioId').value = id;
                    document.getElementById('actionType').value = action;

                    // Cambiar clase del botón según la acción
                    const submitButton = this.querySelector('button[type="submit"]');
                    submitButton.className = `btn ${buttonClass}`;
                    submitButton.textContent = `Confirmar`;
                });
            }
        });
    </script>
</body>

</html>

<?php
// Funciones helper para formato de UI
function getBadgeColorForRole($role_id)
{
    switch ($role_id) {
        case 1:
            return 'danger';    // Admin
        case 2:
            return 'primary';   // Cliente
        case 3:
            return 'success';   // Repartidor
        case 4:
            return 'info';      // Dual
        default:
            return 'secondary';
    }
}

function getBadgeColorForStatus($status)
{
    switch ($status) {
        case 'activo':
            return 'success';
        case 'pendiente':
            return 'warning';
        case 'suspendido':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>