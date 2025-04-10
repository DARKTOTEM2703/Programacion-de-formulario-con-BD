<!-- filepath: c:\xampp\htdocs\Programacion de formulario con BD\components\login_handler.php -->
<?php
session_start();
include 'db_connection.php';
include 'config.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Consulta para verificar el usuario
    $sql = "SELECT * FROM usuarios WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Verifica la contraseña
        if (password_verify($password, $user['password'])) {
            // Establece las variables de sesión
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nombre_usuario'] = $user['nombre_usuario'];

            // Redirige al index principal
            header("Location: ../dashboard.php");
            exit();
        } else {
            // Contraseña incorrecta
            $_SESSION['error'] = "Contraseña incorrecta.";
        }
    } else {
        // Usuario no encontrado
        $_SESSION['error'] = "Usuario no encontrado.";
    }

    $conn->close();
    // Redirige de vuelta al login con un mensaje de error
    header("Location: ../login.php");
    exit();
}
?>