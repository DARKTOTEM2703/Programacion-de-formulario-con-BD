<!-- filepath: c:\xampp\htdocs\Programacion de formulario con BD\components\register_handler.php -->
<?php
session_start();
include 'db_connection.php';
include 'email_service.php';
include 'config.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_usuario = htmlspecialchars(trim($_POST['nombre_usuario']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Verificar si las contraseñas coinciden
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Las contraseñas no coinciden.";
        header("Location: ../register.php");
        exit();
    }

    // Verificar si el email ya está registrado
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "El email ya está registrado.";
        header("Location: ../register.php");
        exit();
    }

    // Encriptar la contraseña
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insertar el nuevo usuario en la base de datos
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre_usuario, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nombre_usuario, $email, $hashed_password);

    $resultadoCorreo = enviarCorreo($email, $nombre_usuario);


    if ($resultadoCorreo === true) {
        $_SESSION['success'] = "Usuario registrado exitosamente. Se ha enviado un correo de confirmación.";
    } else {
        $_SESSION['error'] = "Usuario registrado, pero no se pudo enviar el correo: $resultadoCorreo";
    }

    header("Location: ../register.php");
    exit();
} else {
    $_SESSION['error'] = "Error al registrar el usuario: " . $stmt->error;
    header("Location: ../register.php");
    exit();
}
