<?php
session_start();
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Solicitud no válida. Token CSRF faltante o incorrecto.";
        header("Location: ../forms.php");
        exit();
    }

    // Sanitizar entradas
    $name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone']));
    $office_phone = htmlspecialchars(trim($_POST['office-phone']));
    $origin = htmlspecialchars(trim($_POST['origin']));
    $destination = htmlspecialchars(trim($_POST['destination']));
    $description = htmlspecialchars(trim($_POST['description']));
    $value = filter_var(trim($_POST['value']), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    // Validar campos requeridos
    if (empty($name) || empty($email) || empty($phone) || empty($origin) || empty($destination)) {
        $_SESSION['error'] = "Por favor, completa todos los campos obligatorios.";
        header("Location: ../forms.php");
        exit();
    }

    // Validar formato de email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "El formato del email no es válido.";
        header("Location: ../forms.php");
        exit();
    }

    // Validar valor numérico
    if (!is_numeric($value) || $value < 0) {
        $_SESSION['error'] = "El valor de la mercancía debe ser un número positivo.";
        header("Location: ../forms.php");
        exit();
    }

    // Insertar datos en la base de datos (ejemplo)
    $stmt = $conn->prepare("INSERT INTO envios (name, email, phone, office_phone, origin, destination, description, value) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssd", $name, $email, $phone, $office_phone, $origin, $destination, $description, $value);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Formulario enviado exitosamente.";
        header("Location: ../forms.php");
        exit();
    } else {
        $_SESSION['error'] = "Error al guardar los datos: " . $stmt->error;
        header("Location: ../forms.php");
        exit();
    }
}
