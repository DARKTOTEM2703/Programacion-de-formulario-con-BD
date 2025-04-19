<!-- filepath: c:\xampp\htdocs\Programacion de formulario con BD\components\login_handler.php -->
<?php
session_start();
include 'db_connection.php';
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Consulta para verificar el usuario
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ? AND status != 'suspendido'");
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
        // Verifica la contraseña
        if (password_verify($password, $user['password'])) {
            // Establece las variables de sesión
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nombre_usuario'] = $user['nombre_usuario'];
            $_SESSION['rol_id'] = $user['rol_id']; // Asegurar que el rol_id esté directamente en la sesión
            $_SESSION['user'] = [
                'id' => $user['id'],
                'nombre_usuario' => $user['nombre_usuario'],
                'email' => $user['email'],
                'rol_id' => $user['rol_id']
            ];

            // Mensaje de éxito 
            $_SESSION['success'] = "¡Inicio de sesión exitoso!";

            // Redirige según el rol
            if ($user['rol_id'] == 1) {
                // Es administrador
                header("Location: ../admin/index.php");
            } else if ($user['rol_id'] == 3 || $user['rol_id'] == 4) {
                // Es repartidor o rol dual
                header("Location: ../pwa/dashboard.php");
            } else {
                // Es cliente u otro rol
                header("Location: ../php/dashboard.php");
            }
            exit();
        } else {
            // Contraseña incorrecta
            $_SESSION['error'] = "Contraseña incorrecta.";
        }
    } else {
        // Usuario no encontrado o suspendido
        $_SESSION['error'] = "Usuario no encontrado o cuenta suspendida.";
    }

    $stmt->close();
    $conn->close();

    // Redirige de vuelta al login con un mensaje de error
    header("Location: ../php/login.php");
    exit();
}
?>