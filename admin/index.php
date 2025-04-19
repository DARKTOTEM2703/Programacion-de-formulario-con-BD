<?php
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';
require_once 'includes/ui_functions.php';
require_once 'functions.php'; // Funciones específicas de usuarios

// En functions.php
if (!function_exists('validateId')) {
    function validateId($id)
    {
        return filter_var($id, FILTER_VALIDATE_INT) ? $id : false;
    }
}

if (!function_exists('updateUserStatus')) {
    function updateUserStatus($user_id, $status)
    {
        global $conn;
        $stmt = $conn->prepare("UPDATE usuarios SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $user_id);
        return $stmt->execute();
    }
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Error de verificación de seguridad";
        header("Location: index.php");
        exit();
    }

    // Procesar acción
    if (isset($_POST['action']) && isset($_POST['usuario_id'])) {
        $user_id = validateId($_POST['usuario_id']);

        if (!$user_id) {
            $_SESSION['error'] = "ID de usuario inválido";
            header("Location: index.php");
            exit();
        }

        // Usar la función del módulo
        $status = '';
        $message = '';

        switch ($_POST['action']) {
            case 'activar':
                $status = 'activo';
                $message = "Usuario activado correctamente";
                break;
            case 'desactivar':
                $status = 'suspendido';
                $message = "Usuario suspendido correctamente";
                break;
            case 'eliminar':
                $status = 'eliminado';
                $message = "Usuario eliminado correctamente";
                break;
            default:
                $_SESSION['error'] = "Acción no reconocida";
                header("Location: index.php");
                exit();
        }

        if (updateUserStatus($user_id, $status)) {
            $_SESSION['success'] = $message;
        } else {
            $_SESSION['error'] = "Error al actualizar el estado del usuario";
        }

        header("Location: index.php");
        exit();
    }
}

// Obtener parámetros
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$filters = [
    'rol_id' => isset($_GET['rol']) ? intval($_GET['rol']) : 0,
    'status' => isset($_GET['status']) ? $_GET['status'] : '',
    'search' => isset($_GET['search']) ? $_GET['search'] : ''
];

$result = getUsersList($filters, $page);
if (!$result || !isset($result['users'])) {
    $_SESSION['error'] = "Error al recuperar la lista de usuarios";
    $result = ['users' => [], 'pages' => 0];
}

$roles = getRoles();
if (!$roles) {
    $_SESSION['error'] = "Error al recuperar los roles";
    $roles = [];
}

// Regenerar token CSRF
$csrf_token = regenerateCSRFToken();

// Incluir vista
include '../includes/header.php';
?>

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
            <?php
            if (isset($_SESSION['success'])) {
                echo generateAlert($_SESSION['success'], 'success');
                unset($_SESSION['success']);
            }

            if (isset($_SESSION['error'])) {
                echo generateAlert($_SESSION['error'], 'danger');
                unset($_SESSION['error']);
            }
            ?>

            <!-- Filtros de búsqueda -->
            <?php
            $filter_fields = [
                [
                    'name' => 'rol',
                    'label' => 'Rol',
                    'type' => 'select',
                    'options' => array_merge([0 => 'Todos los roles'], array_column($roles, 'nombre', 'id')),
                    'col_class' => 'col-md-3'
                ],
                [
                    'name' => 'status',
                    'label' => 'Estado',
                    'type' => 'select',
                    'options' => [
                        '' => 'Todos',
                        'activo' => 'Activo',
                        'suspendido' => 'Suspendido',
                        'pendiente' => 'Pendiente'
                    ],
                    'col_class' => 'col-md-3'
                ],
                [
                    'name' => 'search',
                    'label' => 'Buscar',
                    'type' => 'text',
                    'placeholder' => 'Nombre o email',
                    'col_class' => 'col-md-4'
                ]
            ];

            echo generateFilterForm($filter_fields, $filters, 'index.php');
            ?>

            <!-- Tabla de usuarios -->
            <?php
            $columns = [
                'id' => 'ID',
                'nombre_usuario' => 'Nombre',
                'email' => 'Email',
                'rol_nombre' => 'Rol',
                'status' => 'Estado',
                'created_at' => 'Creado'
            ];

            $table_options = [
                'actions' => function ($user) use ($csrf_token) {
                    $actions = '<div class="btn-group">';

                    // Botón editar
                    $actions .= '<a href="edit.php?id=' . $user['id'] . '" class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>';

                    // Botón activar/desactivar
                    if ($user['status'] === 'activo') {
                        $actions .= '<button type="button" class="btn btn-sm btn-outline-warning" 
                                    data-bs-toggle="modal" data-bs-target="#actionModal" 
                                    data-action="desactivar" data-id="' . $user['id'] . '" 
                                    data-name="' . htmlspecialchars($user['nombre_usuario']) . '" title="Desactivar">
                                    <i class="bi bi-pause-circle"></i>
                                </button>';
                    } elseif ($user['status'] === 'suspendido') {
                        $actions .= '<button type="button" class="btn btn-sm btn-outline-success" 
                                    data-bs-toggle="modal" data-bs-target="#actionModal" 
                                    data-action="activar" data-id="' . $user['id'] . '" 
                                    data-name="' . htmlspecialchars($user['nombre_usuario']) . '" title="Activar">
                                    <i class="bi bi-play-circle"></i>
                                </button>';
                    }

                    // Botón eliminar
                    $actions .= '<button type="button" class="btn btn-sm btn-outline-danger" 
                                data-bs-toggle="modal" data-bs-target="#actionModal" 
                                data-action="eliminar" data-id="' . $user['id'] . '" 
                                data-name="' . htmlspecialchars($user['nombre_usuario']) . '" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>';

                    $actions .= '</div>';
                    return $actions;
                }
            ];

            // Formatear datos antes de mostrar
            foreach ($result['users'] as &$user) {
                // Formatear fecha
                $user['created_at'] = date('d/m/Y H:i', strtotime($user['created_at']));

                // Formatear estado
                $status_class = match ($user['status']) {
                    'activo' => 'success',
                    'suspendido' => 'danger',
                    'pendiente' => 'warning',
                    default => 'secondary'
                };
                $user['status'] = '<span class="badge bg-' . $status_class . '">' . $user['status'] . '</span>';

                // Formatear rol
                $rol_class = match ($user['rol_id']) {
                    1 => 'danger',    // Admin
                    2 => 'primary',   // Cliente
                    3 => 'success',   // Repartidor
                    4 => 'info',      // Dual
                    default => 'secondary'
                };
                $user['rol_nombre'] = '<span class="badge bg-' . $rol_class . '">' . $user['rol_nombre'] . '</span>';
            }

            echo generateDataTable($columns, $result['users'], $table_options);
            ?>

            <!-- Modal para confirmación de acciones -->
            <div class="modal fade" id="actionModal" tabindex="-1" aria-labelledby="actionModalLabel"
                aria-hidden="true">
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
                                <button type="submit" class="btn btn-danger" id="confirmActionBtn">Confirmar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <script nonce="<?php echo $csrf_token; ?>">
                // Función para asignar datos al modal
                document.addEventListener('DOMContentLoaded', function() {
                    const actionModal = document.getElementById('actionModal');
                    if (actionModal) {
                        actionModal.addEventListener('show.bs.modal', function(event) {
                            const button = event.relatedTarget;
                            const action = button.getAttribute('data-action');
                            const id = button.getAttribute('data-id');
                            const name = button.getAttribute('data-name');

                            // Actualizar valores de los campos ocultos
                            document.getElementById('usuarioId').value = id;
                            document.getElementById('actionType').value = action;

                            // Actualizar mensaje según acción
                            const mensaje = document.getElementById('modalMessage');
                            let texto = '¿Estás seguro que deseas ';

                            switch (action) {
                                case 'activar':
                                    texto += 'activar';
                                    break;
                                case 'desactivar':
                                    texto += 'desactivar';
                                    break;
                                case 'eliminar':
                                    texto += 'eliminar';
                                    break;
                            }

                            mensaje.textContent = texto + ' al usuario ' + name + '?';

                            // Cambiar el color del botón según la acción
                            const confirmBtn = document.getElementById('confirmActionBtn');
                            const btnClass = action === 'activar' ? 'btn-success' :
                                (action === 'desactivar' ? 'btn-warning' : 'btn-danger');
                            confirmBtn.className = `btn ${btnClass}`;
                        });
                    }
                });
            </script>

            <!-- Usar la función de paginación -->
            <?php
            if ($result['pages'] > 1) {
                echo generatePagination($page, $result['pages'], 'index.php', [
                    'rol' => $filters['rol_id'],
                    'status' => $filters['status'],
                    'search' => $filters['search']
                ]);
            }
            ?>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>