<?php
session_start();
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $csrf_token = $_POST['csrf_token'];
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $_SESSION['error'] = "Solicitud no válida.";
        header("Location: ../forms.php");
        exit();
    }

    // Obtener el usuario_id desde el formulario
    // isset() se utiliza para verificar si la variable está definida y no es null
    // En este caso, se espera que el formulario envíe un campo 'usuario_id'
    $usuario_id = isset($_POST['usuario_id']) ? $_POST['usuario_id'] : null;

    // Otros datos del formulario

    //Seguridad del lado del servidor

    /* htmlspecialchars() se utiliza para evitar ataques XSS al escapar caracteres especiales
     trim() se utiliza para eliminar espacios en blanco al inicio y al final de la cadena
     filter_var() se utiliza para validar y filtrar datos, en este caso, se utiliza para sanitizar el email y el valor*/
    $name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone']));
    $office_phone = htmlspecialchars(trim($_POST['office_phone']));
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

    // Insertar datos en la base de datos
    $stmt = $conn->prepare("INSERT INTO envios (usuario_id, name, email, phone, office_phone, origin, destination, description, value) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssssd", $usuario_id, $name, $email, $phone, $office_phone, $origin, $destination, $description, $value);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Envío registrado exitosamente.";
        header("Location: ../forms.php");
        exit();
    } else {
        $_SESSION['error'] = "Error al guardar el envío: " . $stmt->error;
        header("Location: ../forms.php");
        exit();
    }
}
