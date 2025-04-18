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

    // Verificar si todos los campos están llenos
    if (empty($nombre_usuario) || empty($email) || empty($password)) {
        $_SESSION['error'] = "Todos los campos son obligatorios.";
        header("Location: ../php/register.php");
        exit();
    }

    // Verificar si las contraseñas coinciden
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Las contraseñas no coinciden.";
        header("Location: ../php/register.php");
        exit();
    }

    // Verificar si el email ya está registrado
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    if (!$stmt) {
        $_SESSION['error'] = "Error en la consulta: " . $conn->error;
        header("Location: ../php/register.php");
        exit();
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "El email ya está registrado.";
        header("Location: ../php/register.php");
        exit();
    }

    // Insertar el usuario en la base de datos
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre_usuario, email, password) VALUES (?, ?, ?)");
    if (!$stmt) {
        $_SESSION['error'] = "Error en la consulta: " . $conn->error;
        header("Location: ../php/register.php");
        exit();
    }
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt->bind_param("sss", $nombre_usuario, $email, $hashed_password);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Usuario registrado exitosamente.";

        // Enviar correo de confirmación con la contraseña original
        $resultadoCorreo = enviarCorreo($email, $nombre_usuario, $password);

        if ($resultadoCorreo === true) {
            $_SESSION['success'] .= " Se ha enviado un correo de confirmación.";
        } else {
            $_SESSION['error'] = "Error al enviar el correo: " . $resultadoCorreo;
        }

        // Redirigir
        header("Location: ../php/login.php");
        exit();
    } else {
        $_SESSION['error'] = "Error al registrar el usuario: " . $stmt->error;
        header("Location: ../php/register.php");
        exit();
    }
}
