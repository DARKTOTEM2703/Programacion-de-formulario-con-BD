<?php
session_start();
require_once '../components/db_connection.php';

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Obtener información del usuario y repartidor
$stmt = $conn->prepare("
    SELECT u.id, u.nombre_usuario, u.email, u.created_at,
           r.telefono, r.vehiculo, r.placa, r.status as repartidor_status, 
           r.capacidad_carga, r.tipo_licencia, r.anos_experiencia
    FROM usuarios u
    LEFT JOIN repartidores r ON u.id = r.usuario_id
    WHERE u.id = ?
");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

$mensaje = '';
$error = '';

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil'])) {
    $telefono = $_POST['telefono'];
    $password_actual = $_POST['password_actual'] ?? '';
    $password_nuevo = $_POST['password_nuevo'] ?? '';
    $password_confirmar = $_POST['password_confirmar'] ?? '';

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Actualizar teléfono en la tabla repartidores
        if ($telefono != $usuario['telefono']) {
            $stmt = $conn->prepare("UPDATE repartidores SET telefono = ? WHERE usuario_id = ?");
            $stmt->bind_param("si", $telefono, $usuario_id);
            $stmt->execute();

            if ($stmt->affected_rows === 0 && $stmt->errno === 0) {
                // Si no existe el registro en repartidores, lo creamos
                $stmt = $conn->prepare("INSERT INTO repartidores (usuario_id, telefono, status) VALUES (?, ?, 'pendiente')");
                $stmt->bind_param("is", $usuario_id, $telefono);
                $stmt->execute();
            }
        }

        // Cambiar contraseña si se proporcionó
        if (!empty($password_actual) && !empty($password_nuevo)) {
            // Obtener la contraseña actual del usuario
            $stmt = $conn->prepare("SELECT password FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $current_pass = $result->fetch_assoc();

            // Verificar contraseña actual
            if (!$current_pass || !password_verify($password_actual, $current_pass['password'])) {
                throw new Exception("La contraseña actual no es correcta");
            }

            // Verificar que las nuevas contraseñas coincidan
            if ($password_nuevo !== $password_confirmar) {
                throw new Exception("Las nuevas contraseñas no coinciden");
            }

            // Hash de la nueva contraseña
            $hashed_password = password_hash($password_nuevo, PASSWORD_DEFAULT);

            // Actualizar contraseña
            $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $usuario_id);
            $stmt->execute();
        }

        $conn->commit();
        $mensaje = "Perfil actualizado correctamente";

        // Actualizar datos en la sesión
        $usuario['telefono'] = $telefono;
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - MENDEZ Transportes</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="manifest" href="manifest.json">
    <style>
        :root {
            --primary-color: #0057B8;
            /* Color azul corporativo MENDEZ */
            --secondary-color: #FF9500;
            /* Color naranja/amarillo de acento MENDEZ */
            --accent-color: #FF9500;
            --success-color: #2E8B57;
            --danger-color: #D32F2F;
            --warning-color: #F9A826;
            --light-bg: #F8F9FA;
            --dark-text: #212529;
        }

        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 80px;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar-brand img {
            height: 36px;
            width: auto;
        }

        .nav-link {
            color: #fff !important;
            opacity: 0.8;
            transition: all 0.3s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            opacity: 1;
            transform: translateY(-2px);
        }

        .navbar.fixed-bottom {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
            overflow: hidden;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="assets/icons/logo.png" alt="MENDEZ Logo">
                <span>Mi Perfil</span>
            </a>
            <div class="dropdown">
                <button class="btn dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown"
                    style="color: white; border: none;">
                    <i class="bi bi-person-circle"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="dashboard.php"><i class="bi bi-house-door me-2"></i>Volver al
                            Inicio</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar
                            Sesión</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4 mb-5 pb-5">
        <!-- Logo centrado -->
        <div class="text-center mb-4">
            <img src="assets/icons/logo.png" alt="MENDEZ Transportes" class="company-logo" style="max-width: 150px;">
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2 fs-4"></i>
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Perfil del usuario -->
        <div class="card mb-4 shadow-sm border-0 rounded-4">
            <div class="card-body text-center">
                <div class="mb-3">
                    <img src="assets/img/profile-default.png" class="rounded-circle img-thumbnail shadow-sm"
                        style="width: 120px; height: 120px;">
                </div>
                <h5 class="card-title"><?php echo htmlspecialchars($usuario['nombre_usuario']); ?></h5>
                <p class="text-muted"><?php echo htmlspecialchars($usuario['email']); ?></p>

                <div class="mb-3">
                    <p class="text-muted">Teléfono:
                        <?php echo htmlspecialchars($usuario['telefono'] ?? 'No especificado'); ?></p>
                    <p class="text-muted">Fecha de registro:
                        <?php echo htmlspecialchars(date('d/m/Y', strtotime($usuario['created_at']))); ?></p>
                </div>

                <?php if (!empty($usuario['vehiculo']) || !empty($usuario['placa'])): ?>
                    <div class="mb-3">
                        <p class="text-muted">Vehículo asignado:
                            <?php echo htmlspecialchars($usuario['vehiculo'] ?? 'No especificado'); ?></p>
                        <p class="text-muted">Placa: <?php echo htmlspecialchars($usuario['placa'] ?? 'No especificada'); ?>
                        </p>
                        <p class="text-muted">Capacidad de carga:
                            <?php echo !empty($usuario['capacidad_carga']) ? htmlspecialchars($usuario['capacidad_carga']) . ' ton' : 'No especificada'; ?>
                        </p>
                        <?php if (!empty($usuario['anos_experiencia'])): ?>
                            <p class="text-muted">Experiencia: <?php echo htmlspecialchars($usuario['anos_experiencia']); ?>
                                años</p>
                        <?php endif; ?>
                        <?php if (!empty($usuario['tipo_licencia'])): ?>
                            <p class="text-muted">Tipo de licencia: <?php echo htmlspecialchars($usuario['tipo_licencia']); ?>
                            </p>
                        <?php endif; ?>
                        <p class="text-muted">Estado:
                            <span
                                class="badge <?php echo $usuario['repartidor_status'] == 'activo' ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo htmlspecialchars(ucfirst($usuario['repartidor_status'] ?? 'pendiente')); ?>
                            </span>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Formulario de edición -->
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header border-0"
                style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
                <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Editar Perfil</h5>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control rounded-3" id="nombre"
                            value="<?php echo htmlspecialchars($usuario['nombre_usuario']); ?>" disabled>
                        <div class="form-text">Para cambiar tu nombre, contacta a soporte.</div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control rounded-3" id="email"
                            value="<?php echo htmlspecialchars($usuario['email']); ?>" disabled>
                        <div class="form-text">Para cambiar tu email, contacta a soporte.</div>
                    </div>

                    <div class="mb-3">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="tel" class="form-control rounded-3" id="telefono" name="telefono"
                            value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>" required>
                    </div>

                    <hr class="my-4">
                    <h6 class="mb-3">Cambiar Contraseña</h6>

                    <div class="mb-3">
                        <label for="password_actual" class="form-label">Contraseña Actual</label>
                        <input type="password" class="form-control rounded-3" id="password_actual"
                            name="password_actual">
                    </div>

                    <div class="mb-3">
                        <label for="password_nuevo" class="form-label">Nueva Contraseña</label>
                        <input type="password" class="form-control rounded-3" id="password_nuevo" name="password_nuevo">
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmar" class="form-label">Confirmar Contraseña</label>
                        <input type="password" class="form-control rounded-3" id="password_confirmar"
                            name="password_confirmar">
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="actualizar_perfil" class="btn btn-primary rounded-3">
                            <i class="bi bi-save me-2"></i>Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Barra de navegación inferior -->
    <nav class="navbar fixed-bottom navbar-dark p-0 shadow">
        <div class="container-fluid p-0">
            <div class="d-flex flex-row justify-content-around w-100">
                <a href="dashboard.php" class="nav-link text-center py-3 flex-fill">
                    <div class="d-flex flex-column align-items-center">
                        <i class="bi bi-house-door-fill fs-4"></i>
                        <span class="small">Inicio</span>
                    </div>
                </a>
                <a href="escanear.php" class="nav-link text-center py-3 flex-fill">
                    <div class="d-flex flex-column align-items-center">
                        <i class="bi bi-qr-code-scan fs-4"></i>
                        <span class="small">Escanear</span>
                    </div>
                </a>
                <a href="mapa.php" class="nav-link text-center py-3 flex-fill">
                    <div class="d-flex flex-column align-items-center">
                        <i class="bi bi-geo-alt-fill fs-4"></i>
                        <span class="small">Rutas</span>
                    </div>
                </a>
                <a href="perfil.php" class="nav-link text-center py-3 flex-fill active">
                    <div class="d-flex flex-column align-items-center">
                        <i class="bi bi-person-fill fs-4"></i>
                        <span class="small">Perfil</span>
                    </div>
                </a>
            </div>
        </div>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>