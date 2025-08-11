<!-- filepath: c:\xampp\htdocs\Programacion de formulario con BD\components\login_handler.php -->
<?php
session_start();
require_once 'db_connection.php';
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Consulta mejorada para obtener información completa del usuario
    $stmt = $conn->prepare("
        SELECT u.*, r.nombre as rol_nombre
        FROM usuarios u
        JOIN roles r ON u.rol_id = r.id  
        WHERE u.email = ? AND u.status = 'activo'
    ");
    
    if (!$stmt) {
        $_SESSION['error'] = "Error en la consulta: " . $conn->error;
        header("Location: ../php/login.php");
        exit();
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verificar contraseña
        if (password_verify($password, $user['password'])) {
            // Establecer sesión completa
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nombre_usuario'] = $user['nombre_usuario'];
            $_SESSION['rol_id'] = $user['rol_id'];
            $_SESSION['rol_nombre'] = $user['rol_nombre'];
            $_SESSION['email'] = $user['email'];

            // Log de acceso
            error_log("Login exitoso: Usuario {$user['email']} (Rol: {$user['rol_nombre']})");

            $login_mode = $_POST['login_mode'] ?? 'general';

            // Validaciones por modo visual
            if ($login_mode === 'bodega' && !in_array($user['rol_id'], [1,4,8])) {
                $_SESSION['error'] = "Acceso restringido a Bodega";
                header("Location: ../php/login.php"); exit();
            }
            if ($login_mode === 'general' && $user['rol_id'] == 3) {
                $_SESSION['error'] = "Usa el acceso de Repartidor";
                header("Location: ../pwa/login.php"); exit();
            }

            // Redireccionar según rol
            $redirects = [
                1 => '../admin/dashboard.php',
                2 => '../php/dashboard.php',
                3 => '../pwa/dashboard.php',
                4 => '../bodega/dashboard_bodega.php',
                5 => '../soporte/dashboard_soporte.php',
                6 => '../supervisor/dashboard_supervisor.php',
                7 => '../contador/dashboard_contador.php',
                8 => '../admin/dashboard.php'
            ];

            $redirect_url = $redirects[$user['rol_id']] ?? '../php/dashboard.php';
            
            $_SESSION['success'] = "Bienvenido, " . $user['nombre_usuario'];
            header("Location: " . $redirect_url);
            exit();
        } else {
            $_SESSION['error'] = "Contraseña incorrecta";
        }
    } else {
        $_SESSION['error'] = "Usuario no encontrado o cuenta inactiva";
    }

    $stmt->close();
    $conn->close();

    // Redirigir con error
    header("Location: ../php/login.php");
    exit();
}
?>