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

            // Redireccionar según rol
            $redirects = [
                1 => '../admin/dashboard.php',       // Admin
                2 => '../php/dashboard.php',         // Cliente  
                3 => '../pwa/dashboard.php',         // Repartidor
                4 => '../admin/bodega_panel.php',    // Bodeguista
                5 => '../admin/dashboard.php',       // Soporte
                6 => '../admin/dashboard.php',       // Supervisor
                7 => '../admin/facturas.php',        // Contador
                8 => '../admin/dashboard.php'        // Super Admin
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