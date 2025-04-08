<!-- filepath: c:\xampp\htdocs\Programacion de formulario con BD\components\register_handler.php -->
<?php
session_start();
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_usuario = $_POST['nombre_usuario'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Verificar si las contraseñas coinciden
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Las contraseñas no coinciden.";
        header("Location: ../register.php");
        exit();
    }

    // Verificar si el email ya está registrado
    $sql = "SELECT * FROM usuarios WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "El email ya está registrado.";
        header("Location: ../register.php");
        exit();
    }

    // Encriptar la contraseña
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insertar el nuevo usuario en la base de datos
    $sql = "INSERT INTO usuarios (nombre_usuario, email, password) VALUES ('$nombre_usuario', '$email', '$hashed_password')";

    if ($conn->query($sql) === TRUE) {
        $_SESSION['success'] = "Usuario registrado exitosamente. Ahora puedes iniciar sesión.";
        header("Location: ../login.php");
        exit();
    } else {
        $_SESSION['error'] = "Error al registrar el usuario: " . $conn->error;
        header("Location: ../register.php");
        exit();
    }
}
?>