<?php
session_start();
require_once '../components/db_connection.php';

// Si ya está autenticado, redirigir según rol
if (isset($_SESSION['usuario_id']) && isset($_SESSION['rol_id'])) {
    $redirects = [
        1 => '../admin/dashboard.php',       // Admin
        2 => 'dashboard.php',                // Cliente  
        3 => '../pwa/dashboard.php',         // Repartidor
        4 => '../bodega/dashboard_bodega.php',    // Bodeguista
        5 => '../soporte/dashboard_soporte.php',       // Soporte
        6 => '../supervisor/dashboard_supervisor.php',       // Supervisor
        7 => '../contador/dashboard_contador.php',        // Contador
    ];
    
    $redirect_url = $redirects[$_SESSION['rol_id']] ?? 'dashboard.php';
    header("Location: " . $redirect_url);
    exit();
}

$error = '';
$success = '';

// Mostrar mensaje de error específico
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'sin_permisos':
            $error = 'No tienes permisos para acceder a esa sección.';
            break;
        case 'login_required':
            $error = 'Debes iniciar sesión para continuar.';
            break;
    }
}

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <title>Acceso - MENDEZ Transportes</title>
    <style>
        :root {
            --primary-color: #0057B8;
            --secondary-color: #FF9500;
        }
        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, #003d82 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
        }
        .role-selector {
            display: flex;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 5px;
            margin-bottom: 20px;
        }
        .role-tab {
            flex: 1;
            padding: 10px;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            background: transparent;
            color: #6c757d;
        }
        .role-tab.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 4px 10px rgba(0, 87, 184, 0.3);
        }
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-container img {
            max-width: 200px;
            height: auto;
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 87, 184, 0.25);
        }
        .btn-primary {
            background: var(--primary-color);
            border: none;
            border-radius: 10px;
            padding: 12px;
        }
        .btn-primary:hover {
            background: #003d82;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="login-card p-4">
                    <div class="logo-container">
                        <img src="../img/logo.png" alt="MENDEZ Transportes">
                    </div>

                    <!-- Selector de tipo de acceso -->
                    <div class="role-selector">
                        <button type="button" class="role-tab active" onclick="showLoginType('general')">
                            <i class="bi bi-person-circle me-1"></i>
                            <small>General</small>
                        </button>
                        <button type="button" class="role-tab" onclick="showLoginType('repartidor')">
                            <i class="bi bi-truck me-1"></i>
                            <small>Repartidor</small>
                        </button>
                        <button type="button" class="role-tab" onclick="showLoginType('bodega')">
                            <i class="bi bi-building me-1"></i>
                            <small>Bodega</small>
                        </button>
                    </div>

                    <!-- Mensajes -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Formulario de login -->
                    <form action="../components/login_handler.php" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="bi bi-envelope me-2"></i>Correo Electrónico
                            </label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">
                                <i class="bi bi-lock me-2"></i>Contraseña
                            </label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                            </button>
                        </div>
                    </form>

                    <!-- Información adicional según tipo -->
                    <div id="info-general" class="mt-3 text-center">
                        <small class="text-muted">
                            ¿No tienes cuenta? <a href="register.php">Regístrate aquí</a>
                        </small>
                    </div>

                    <div id="info-repartidor" class="mt-3 text-center" style="display: none;">
                        <small class="text-muted">
                            ¿Quieres ser repartidor? <a href="../pwa/login.php">Regístrate aquí</a>
                        </small>
                    </div>

                    <div id="info-bodega" class="mt-3 text-center" style="display: none;">
                        <small class="text-muted">
                            Acceso solo para personal autorizado de bodega
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showLoginType(type) {
            // Actualizar tabs
            document.querySelectorAll('.role-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');

            // Mostrar información correspondiente
            document.querySelectorAll('[id^="info-"]').forEach(info => {
                info.style.display = 'none';
            });
            document.getElementById('info-' + type).style.display = 'block';

            // Cambiar placeholder según tipo
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            
            switch(type) {
                case 'repartidor':
                    emailInput.placeholder = 'correo@repartidor.com';
                    passwordInput.placeholder = 'Tu contraseña de repartidor';
                    break;
                case 'bodega':
                    emailInput.placeholder = 'correo@bodega.mendez.com';
                    passwordInput.placeholder = 'Contraseña de bodega';
                    break;
                default:
                    emailInput.placeholder = 'tu@correo.com';
                    passwordInput.placeholder = 'Tu contraseña';
            }
        }
    </script>
</body>
</html>