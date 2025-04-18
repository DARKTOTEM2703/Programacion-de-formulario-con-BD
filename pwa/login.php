<?php
session_start();
require_once '../components/db_connection.php';

$error = '';
$success = '';

// Procesar inicio de sesión
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT u.id, u.nombre_usuario, u.password, u.rol_id, u.status, 
                           r.id as repartidor_id, r.status as repartidor_status 
                           FROM usuarios u 
                           LEFT JOIN repartidores r ON u.id = r.usuario_id 
                           WHERE u.email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Verificar el rol del usuario
            if ($user['rol_id'] == 3 || $user['rol_id'] == 4) {
                // Es repartidor o tiene rol dual

                // Verificar el estado del repartidor
                if ($user['repartidor_status'] === 'activo') {
                    // Establecer sesión como repartidor
                    $_SESSION['usuario_id'] = $user['id'];
                    $_SESSION['repartidor_id'] = $user['repartidor_id'];
                    $_SESSION['nombre'] = $user['nombre_usuario'];
                    $_SESSION['rol'] = 'repartidor';

                    if ($user['rol_id'] == 4) {
                        $_SESSION['rol_dual'] = true;
                    }

                    // Actualizar último login
                    $stmt = $conn->prepare("UPDATE repartidores SET ultimo_login = NOW() WHERE id = ?");
                    $stmt->bind_param("i", $user['repartidor_id']);
                    $stmt->execute();

                    header("Location: dashboard.php");
                    exit();
                } else {
                    // Verificar si el repartidor ya completó el paso 2 (verificando si tiene datos como licencia)
                    $stmt = $conn->prepare("SELECT tipo_licencia FROM repartidores WHERE usuario_id = ?");
                    $stmt->bind_param("i", $user['id']);
                    $stmt->execute();
                    $result_perfil = $stmt->get_result();
                    $perfil_data = $result_perfil->fetch_assoc();

                    if ($perfil_data && !empty($perfil_data['tipo_licencia'])) {
                        // Ya completó el paso 2, mostrar página de espera
                        $_SESSION['usuario_id'] = $user['id'];
                        $_SESSION['nombre'] = $user['nombre_usuario'];
                        $_SESSION['perfil_completado'] = true;
                        header("Location: pending_approval.php");
                        exit();
                    } else {
                        // No ha completado el paso 2, enviarlo a completar su perfil
                        $_SESSION['usuario_id'] = $user['id'];
                        $_SESSION['nombre'] = $user['nombre_usuario'];
                        $_SESSION['repartidor_pendiente'] = true;
                        header("Location: register_step2.php");
                        exit();
                    }
                }
            } else if ($user['rol_id'] == 2) {
                // Es un cliente
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['nombre'] = $user['nombre_usuario'];
                $_SESSION['rol'] = 'cliente';

                header("Location: ../php/dashboard.php");
                exit();
            } else if ($user['rol_id'] == 1) {
                // Es un administrador
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['nombre'] = $user['nombre_usuario'];
                $_SESSION['rol'] = 'admin';

                header("Location: ../admin/dashboard.php");
                exit();
            }
        } else {
            $error = "Contraseña incorrecta";
        }
    } else {
        $error = "Usuario no encontrado";
    }
}

// Procesar registro de nuevo repartidor
if (isset($_POST['register'])) {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validar que las contraseñas coincidan
    if ($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden";
    } else {
        // Verificar si el email ya está registrado
        $stmt = $conn->prepare("SELECT id, rol_id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $usuario_existente = $result->fetch_assoc();

            // Verificar si ya es repartidor
            if ($usuario_existente['rol_id'] == 3) {
                $error = "Ya estás registrado como repartidor con este email";
            } else {
                // Es un cliente y quiere ser también repartidor
                $usuario_id = $usuario_existente['id'];

                // Iniciar transacción
                $conn->begin_transaction();

                try {
                    // Actualizar el rol a 4 (rol dual: cliente y repartidor)
                    $stmt = $conn->prepare("UPDATE usuarios SET rol_id = 4 WHERE id = ?");
                    $stmt->bind_param("i", $usuario_id);
                    $stmt->execute();

                    // Insertar nuevo registro de repartidor
                    $stmt = $conn->prepare("INSERT INTO repartidores (usuario_id, telefono, status) VALUES (?, ?, 'pendiente')");
                    $stmt->bind_param("is", $usuario_id, $telefono);
                    $stmt->execute();

                    // Confirmar transacción
                    $conn->commit();

                    // Establecer sesión
                    $_SESSION['usuario_id'] = $usuario_id;
                    $_SESSION['repartidor_pendiente'] = true;
                    $_SESSION['nombre'] = $nombre;

                    $success = "Has sido registrado como repartidor con tu cuenta existente.";
                    header("refresh:3;url=register_step2.php");
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Error al actualizar tu cuenta: " . $e->getMessage();
                }
            }
        } else {
            // Registrar nuevo usuario como repartidor
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // ID del rol para repartidores (3)
            $rol_id = 3;
            $status = 'activo'; // El usuario está activo
            $repartidor_status = 'pendiente'; // Pero el perfil de repartidor está pendiente

            // Iniciar transacción
            $conn->begin_transaction();

            try {
                // Insertar nuevo usuario
                $stmt = $conn->prepare("INSERT INTO usuarios (nombre_usuario, email, password, rol_id, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssis", $nombre, $email, $hashed_password, $rol_id, $status);
                $stmt->execute();
                $usuario_id = $conn->insert_id;

                // Insertar en tabla de repartidores
                $stmt = $conn->prepare("INSERT INTO repartidores (usuario_id, telefono, status) VALUES (?, ?, 'pendiente')");
                $stmt->bind_param("is", $usuario_id, $telefono);
                $stmt->execute();

                // Confirmar transacción
                $conn->commit();

                // Establecer la sesión para indicar que es un repartidor nuevo que necesita completar su perfil
                $_SESSION['usuario_id'] = $usuario_id;
                $_SESSION['repartidor_pendiente'] = true;
                $_SESSION['nombre'] = $nombre;

                $success = "Registro exitoso. Ahora completa tu perfil de repartidor.";
                // Redireccionar al segundo paso
                header("refresh:3;url=register_step2.php");
            } catch (Exception $e) {
                // Revertir cambios si hay error
                $conn->rollback();
                $error = "Error al registrar: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Repartidores</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="manifest" href="manifest.json">
</head>

<body>
    <div class="login-container">
        <div class="card-content">
            <div class="logo-container">
                <img src="assets/icons/logo.png" alt="MENDEZ Logo">
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="tabs">
                <div class="tab active" onclick="showTab('login')">Iniciar Sesión</div>
                <div class="tab" onclick="showTab('register')">Registrarse</div>
            </div>

            <!-- Pestaña de login -->
            <div id="login-tab" class="tab-content active">
                <form method="post" action="">
                    <div class="form-group">
                        <label for="login-email">Email</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="login-email" name="email" placeholder="tu@email.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="login-password">Contraseña</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="login-password" name="password" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div class="forgot-password">
                        <a href="#">¿Olvidaste tu contraseña?</a>
                    </div>

                    <button type="submit" name="login">Iniciar Sesión</button>
                </form>
            </div>

            <!-- Pestaña de registro -->
            <div id="register-tab" class="tab-content">
                <form method="post" action="">
                    <div class="form-group">
                        <label for="reg-nombre">Nombre completo</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" id="reg-nombre" name="nombre" placeholder="Tu nombre completo" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reg-email">Email</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="reg-email" name="email" placeholder="tu@email.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reg-telefono">Teléfono</label>
                        <div class="input-with-icon">
                            <i class="fas fa-phone"></i>
                            <input type="tel" id="reg-telefono" name="telefono" placeholder="Tu número de teléfono"
                                required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reg-password">Contraseña</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="reg-password" name="password" placeholder="Contraseña segura"
                                required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reg-confirm-password">Confirmar contraseña</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="reg-confirm-password" name="confirm_password"
                                placeholder="Confirmar contraseña" required>
                        </div>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" id="terms" required>
                        <label for="terms">Acepto los términos y condiciones</label>
                    </div>

                    <button type="submit" name="register">Registrarse</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Ocultar todos los contenidos de pestañas
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Desactivar todas las pestañas
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Mostrar la pestaña seleccionada
            document.getElementById(tabName + '-tab').classList.add('active');

            // Activar el botón de la pestaña
            document.querySelectorAll('.tab').forEach(tab => {
                if (tab.innerText.toLowerCase().includes(tabName === 'login' ? 'iniciar' : 'registr')) {
                    tab.classList.add('active');
                }
            });
        }
    </script>
</body>

</html>