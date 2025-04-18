<?php
session_start();
require_once '../components/db_connection.php';

// Verificar autenticación
if (!isset($_SESSION['repartidor_id'])) {
    header("Location: login.php");
    exit();
}

$repartidor_id = $_SESSION['repartidor_id'];
$mensaje = '';
$error = '';

// Obtener datos del repartidor
$stmt = $conn->prepare("
    SELECT u.*, r.telefono, r.status as estado_cuenta, r.calificacion
    FROM usuarios u
    LEFT JOIN repartidores r ON u.id = r.usuario_id
    WHERE u.id = ?
");
$stmt->bind_param("i", $repartidor_id);
$stmt->execute();
$result = $stmt->get_result();
$repartidor = $result->fetch_assoc();

// Obtener estadísticas
$stmt = $conn->prepare("
    SELECT 
        COUNT(re.envio_id) as total_asignados,
        COUNT(CASE WHEN e.status = 'Entregado' THEN 1 END) as total_entregados,
        COUNT(CASE WHEN e.status = 'En tránsito' THEN 1 END) as total_en_transito,
        AVG(CASE WHEN e.status = 'Entregado' THEN 
            TIMESTAMPDIFF(HOUR, re.assigned_at, th.created_at) 
        END) as tiempo_promedio
    FROM repartidores_envios re
    JOIN envios e ON re.envio_id = e.id
    LEFT JOIN (
        SELECT envio_id, MIN(created_at) as created_at 
        FROM tracking_history 
        WHERE status = 'Entregado'
        GROUP BY envio_id
    ) th ON e.id = th.envio_id
    WHERE re.repartidor_id = ?
");
$stmt->bind_param("i", $repartidor_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil'])) {
    $telefono = $_POST['telefono'];
    $password_actual = $_POST['password_actual'] ?? '';
    $password_nuevo = $_POST['password_nuevo'] ?? '';
    $password_confirmar = $_POST['password_confirmar'] ?? '';

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Actualizar teléfono
        $stmt = $conn->prepare("UPDATE repartidores SET telefono = ? WHERE usuario_id = ?");
        $stmt->bind_param("si", $telefono, $repartidor_id);
        $stmt->execute();

        // Cambiar contraseña si se proporcionó
        if (!empty($password_actual) && !empty($password_nuevo)) {
            // Verificar contraseña actual
            if (!password_verify($password_actual, $repartidor['password'])) {
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
            $stmt->bind_param("si", $hashed_password, $repartidor_id);
            $stmt->execute();
        }

        $conn->commit();
        $mensaje = "Perfil actualizado correctamente";

        // Actualizar datos en la sesión
        $repartidor['telefono'] = $telefono;
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
    <title>Mi Perfil</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="manifest" href="manifest.json">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <span class="navbar-brand">Mi Perfil</span>
        </div>
    </nav>

    <div class="container mt-3 mb-5 pb-5">
        <?php if ($mensaje): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo htmlspecialchars($mensaje); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Perfil del repartidor -->
        <div class="card mb-4">
            <div class="card-body text-center">
                <div class="mb-3">
                    <img src="assets/img/profile-default.png" class="rounded-circle img-thumbnail"
                        style="width: 120px; height: 120px;">
                </div>
                <h5 class="card-title"><?php echo htmlspecialchars($repartidor['nombre']); ?></h5>
                <p class="text-muted"><?php echo htmlspecialchars($repartidor['email']); ?></p>

                <div class="d-flex justify-content-center mb-3">
                    <div class="badge bg-<?php
                                            echo $repartidor['estado_cuenta'] === 'Activo' ? 'success' : ($repartidor['estado_cuenta'] === 'Pendiente' ? 'warning' : 'danger');
                                            ?> px-3 py-2">
                        <?php echo htmlspecialchars($repartidor['estado_cuenta']); ?>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="rating">
                        <?php
                        $calificacion = round($repartidor['calificacion']);
                        for ($i = 1; $i <= 5; $i++) {
                            echo '<i class="bi ' . ($i <= $calificacion ? 'bi-star-fill text-warning' : 'bi-star') . '"></i>';
                        }
                        ?>
                        <span class="ms-2"><?php echo number_format($repartidor['calificacion'], 1); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-6">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <div class="display-5 fw-bold text-primary"><?php echo $stats['total_entregados'] ?? 0; ?></div>
                        <div class="text-muted">Entregas</div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <div class="display-5 fw-bold text-primary">
                            <?php echo $stats['tiempo_promedio'] ? round($stats['tiempo_promedio']) : '-'; ?>
                        </div>
                        <div class="text-muted">Tiempo promedio (h)</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de edición -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Editar Perfil</h5>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre"
                            value="<?php echo htmlspecialchars($repartidor['nombre']); ?>" disabled>
                        <div class="form-text">Para cambiar tu nombre, contacta a soporte.</div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email"
                            value="<?php echo htmlspecialchars($repartidor['email']); ?>" disabled>
                        <div class="form-text">Para cambiar tu email, contacta a soporte.</div>
                    </div>

                    <div class="mb-3">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" id="telefono" name="telefono"
                            value="<?php echo htmlspecialchars($repartidor['telefono']); ?>" required>
                    </div>

                    <hr class="my-4">
                    <h6 class="mb-3">Cambiar Contraseña</h6>

                    <div class="mb-3">
                        <label for="password_actual" class="form-label">Contraseña Actual</label>
                        <input type="password" class="form-control" id="password_actual" name="password_actual">
                    </div>

                    <div class="mb-3">
                        <label for="password_nuevo" class="form-label">Nueva Contraseña</label>
                        <input type="password" class="form-control" id="password_nuevo" name="password_nuevo">
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmar" class="form-label">Confirmar Contraseña</label>
                        <input type="password" class="form-control" id="password_confirmar" name="password_confirmar">
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="actualizar_perfil" class="btn btn-primary">Guardar Cambios</button
                            // filepath: c:\xampp\htdocs\Programacion-de-formulario-con-BD\pwa\perfil.php <?php
                                                                                                            session_start();
                                                                                                            require_once '../components/db_connection.php';

                                                                                                            // Verificar autenticación
                                                                                                            if (!isset($_SESSION['repartidor_id'])) {
                                                                                                                header("Location: login.php");
                                                                                                                exit();
                                                                                                            }

                                                                                                            $repartidor_id = $_SESSION['repartidor_id'];
                                                                                                            $mensaje = '';
                                                                                                            $error = '';

                                                                                                            // Obtener datos del repartidor
                                                                                                            $stmt = $conn->prepare("
    SELECT u.*, r.telefono, r.status as estado_cuenta, r.calificacion
    FROM usuarios u
    LEFT JOIN repartidores r ON u.id = r.usuario_id
    WHERE u.id = ?
");
                                                                                                            $stmt->bind_param("i", $repartidor_id);
                                                                                                            $stmt->execute();
                                                                                                            $result = $stmt->get_result();
                                                                                                            $repartidor = $result->fetch_assoc();

                                                                                                            // Obtener estadísticas
                                                                                                            $stmt = $conn->prepare("
    SELECT 
        COUNT(re.envio_id) as total_asignados,
        COUNT(CASE WHEN e.status = 'Entregado' THEN 1 END) as total_entregados,
        COUNT(CASE WHEN e.status = 'En tránsito' THEN 1 END) as total_en_transito,
        AVG(CASE WHEN e.status = 'Entregado' THEN 
            TIMESTAMPDIFF(HOUR, re.assigned_at, th.created_at) 
        END) as tiempo_promedio
    FROM repartidores_envios re
    JOIN envios e ON re.envio_id = e.id
    LEFT JOIN (
        SELECT envio_id, MIN(created_at) as created_at 
        FROM tracking_history 
        WHERE status = 'Entregado'
        GROUP BY envio_id
    ) th ON e.id = th.envio_id
    WHERE re.repartidor_id = ?
");
                                                                                                            $stmt->bind_param("i", $repartidor_id);
                                                                                                            $stmt->execute();
                                                                                                            $stats = $stmt->get_result()->fetch_assoc();

                                                                                                            // Procesar actualización de perfil
                                                                                                            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil'])) {
                                                                                                                $telefono = $_POST['telefono'];
                                                                                                                $password_actual = $_POST['password_actual'] ?? '';
                                                                                                                $password_nuevo = $_POST['password_nuevo'] ?? '';
                                                                                                                $password_confirmar = $_POST['password_confirmar'] ?? '';

                                                                                                                // Iniciar transacción
                                                                                                                $conn->begin_transaction();

                                                                                                                try {
                                                                                                                    // Actualizar teléfono
                                                                                                                    $stmt = $conn->prepare("UPDATE repartidores SET telefono = ? WHERE usuario_id = ?");
                                                                                                                    $stmt->bind_param("si", $telefono, $repartidor_id);
                                                                                                                    $stmt->execute();

                                                                                                                    // Cambiar contraseña si se proporcionó
                                                                                                                    if (!empty($password_actual) && !empty($password_nuevo)) {
                                                                                                                        // Verificar contraseña actual
                                                                                                                        if (!password_verify($password_actual, $repartidor['password'])) {
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
                                                                                                                        $stmt->bind_param("si", $hashed_password, $repartidor_id);
                                                                                                                        $stmt->execute();
                                                                                                                    }

                                                                                                                    $conn->commit();
                                                                                                                    $mensaje = "Perfil actualizado correctamente";

                                                                                                                    // Actualizar datos en la sesión
                                                                                                                    $repartidor['telefono'] = $telefono;
                                                                                                                } catch (Exception $e) {
                                                                                                                    $conn->rollback();
                                                                                                                    $error = $e->getMessage();
                                                                                                                }
                                                                                                            }
                                                                                                            ?> <!DOCTYPE html>
                        <html lang="es">

                        <head>
                            <meta charset="UTF-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1.0">
                            <title>Mi Perfil</title>
                            <link rel="stylesheet"
                                href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
                            <link rel="stylesheet"
                                href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
                            <link rel="stylesheet" href="assets/css/mobile.css">
                            <link rel="manifest" href="manifest.json">
                        </head>

                        <body>
                            <!-- Navbar -->
                            <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
                                <div class="container">
                                    <span class="navbar-brand">Mi Perfil</span>
                                </div>
                            </nav>

                            <div class="container mt-3 mb-5 pb-5">
                                <?php if ($mensaje): ?>
                                    <div class="alert alert-success alert-dismissible fade show">
                                        <?php echo htmlspecialchars($mensaje); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <?php if ($error): ?>
                                    <div class="alert alert-danger alert-dismissible fade show">
                                        <?php echo htmlspecialchars($error); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <!-- Perfil del repartidor -->
                                <div class="card mb-4">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <img src="assets/img/profile-default.png"
                                                class="rounded-circle img-thumbnail"
                                                style="width: 120px; height: 120px;">
                                        </div>
                                        <h5 class="card-title"><?php echo htmlspecialchars($repartidor['nombre']); ?>
                                        </h5>
                                        <p class="text-muted"><?php echo htmlspecialchars($repartidor['email']); ?></p>

                                        <div class="d-flex justify-content-center mb-3">
                                            <div class="badge bg-<?php
                                                                    echo $repartidor['estado_cuenta'] === 'Activo' ? 'success' : ($repartidor['estado_cuenta'] === 'Pendiente' ? 'warning' : 'danger');
                                                                    ?> px-3 py-2">
                                                <?php echo htmlspecialchars($repartidor['estado_cuenta']); ?>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="rating">
                                                <?php
                                                $calificacion = round($repartidor['calificacion']);
                                                for ($i = 1; $i <= 5; $i++) {
                                                    echo '<i class="bi ' . ($i <= $calificacion ? 'bi-star-fill text-warning' : 'bi-star') . '"></i>';
                                                }
                                                ?>
                                                <span
                                                    class="ms-2"><?php echo number_format($repartidor['calificacion'], 1); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Estadísticas -->
                                <div class="row mb-4">
                                    <div class="col-6">
                                        <div class="card h-100">
                                            <div class="card-body text-center">
                                                <div class="display-5 fw-bold text-primary">
                                                    <?php echo $stats['total_entregados'] ?? 0; ?></div>
                                                <div class="text-muted">Entregas</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="card h-100">
                                            <div class="card-body text-center">
                                                <div class="display-5 fw-bold text-primary">
                                                    <?php echo $stats['tiempo_promedio'] ? round($stats['tiempo_promedio']) : '-'; ?>
                                                </div>
                                                <div class="text-muted">Tiempo promedio (h)</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Formulario de edición -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Editar Perfil</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="post" action="">
                                            <div class="mb-3">
                                                <label for="nombre" class="form-label">Nombre</label>
                                                <input type="text" class="form-control" id="nombre"
                                                    value="<?php echo htmlspecialchars($repartidor['nombre']); ?>"
                                                    disabled>
                                                <div class="form-text">Para cambiar tu nombre, contacta a soporte.</div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="email"
                                                    value="<?php echo htmlspecialchars($repartidor['email']); ?>"
                                                    disabled>
                                                <div class="form-text">Para cambiar tu email, contacta a soporte.</div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="telefono" class="form-label">Teléfono</label>
                                                <input type="tel" class="form-control" id="telefono" name="telefono"
                                                    value="<?php echo htmlspecialchars($repartidor['telefono']); ?>"
                                                    required>
                                            </div>

                                            <hr class="my-4">
                                            <h6 class="mb-3">Cambiar Contraseña</h6>

                                            <div class="mb-3">
                                                <label for="password_actual" class="form-label">Contraseña
                                                    Actual</label>
                                                <input type="password" class="form-control" id="password_actual"
                                                    name="password_actual">
                                            </div>

                                            <div class="mb-3">
                                                <label for="password_nuevo" class="form-label">Nueva Contraseña</label>
                                                <input type="password" class="form-control" id="password_nuevo"
                                                    name="password_nuevo">
                                            </div>

                                            <div class="mb-3">
                                                <label for="password_confirmar" class="form-label">Confirmar
                                                    Contraseña</label>
                                                <input type="password" class="form-control" id="password_confirmar"
                                                    name="password_confirmar">
                                            </div>

                                            <div class="d-grid">
                                                <button type="submit" name="actualizar_perfil"
                                                    class="btn btn-primary">Guardar Cambios</button