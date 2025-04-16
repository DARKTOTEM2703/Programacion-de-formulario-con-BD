<?php
session_start();
include 'db_connection.php';
include 'email_confirmacion.php'; // Añade esta línea

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $csrf_token = $_POST['csrf_token'];
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $_SESSION['error'] = "Solicitud no válida.";
        header("Location: ../php/forms.php");
        exit();
    }

    // Obtener el usuario_id desde el formulario
    $usuario_id = isset($_POST['usuario_id']) ? $_POST['usuario_id'] : null;

    // Datos básicos del formulario
    $name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone']));
    $office_phone = htmlspecialchars(trim($_POST['office_phone'] ?? ''));
    $origin = htmlspecialchars(trim($_POST['origin']));
    $destination = htmlspecialchars(trim($_POST['destination']));
    $description = htmlspecialchars(trim($_POST['description']));
    $value = filter_var(trim($_POST['value'] ?? 0), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    // Nuevos campos
    $delivery_date = isset($_POST['delivery_date']) ? htmlspecialchars(trim($_POST['delivery_date'])) : null;
    $package_type = isset($_POST['package_type']) ? htmlspecialchars(trim($_POST['package_type'])) : null;
    $weight = isset($_POST['weight']) ? filter_var(trim($_POST['weight']), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : null;
    $insurance = isset($_POST['insurance']) ? 1 : 0;
    $urgent = isset($_POST['urgent']) ? 1 : 0;
    $additional_notes = isset($_POST['additional_notes']) ? htmlspecialchars(trim($_POST['additional_notes'])) : null;

    // Validar campos requeridos
    if (empty($name) || empty($email) || empty($phone) || empty($origin) || empty($destination) || empty($description) || empty($package_type) || empty($weight)) {
        $_SESSION['error'] = "Por favor, completa todos los campos obligatorios.";
        header("Location: ../php/forms.php");
        exit();
    }

    // Manejo de imágenes
    $image_path = ''; // Inicializar con cadena vacía en lugar de null
    if (isset($_FILES['package_image']) && $_FILES['package_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($_FILES['package_image']['type'], $allowed_types)) {
            $_SESSION['error'] = "El formato de la imagen no es válido. Se permiten: JPG, PNG y GIF.";
            header("Location: ../php/forms.php");
            exit();
        }

        if ($_FILES['package_image']['size'] > $max_size) {
            $_SESSION['error'] = "La imagen es demasiado grande. El tamaño máximo permitido es 2MB.";
            header("Location: ../php/forms.php");
            exit();
        }

        $upload_dir = '../uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $filename = uniqid() . '_' . basename($_FILES['package_image']['name']);
        $upload_path = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['package_image']['tmp_name'], $upload_path)) {
            $image_path = 'uploads/' . $filename;
        } else {
            $_SESSION['error'] = "Error al subir la imagen. Por favor, intenta nuevamente.";
            header("Location: ../php/forms.php");
            exit();
        }
    }

    // Generar número de seguimiento único
    $tracking_number = 'MENDEZ-' . strtoupper(substr(md5(uniqid()), 0, 8));

    // Calcular costo estimado (ejemplo simple)
    $base_cost = 100; // Costo base en pesos
    $weight_cost = $weight * 10; // 10 pesos por kg
    $urgent_cost = $urgent ? 200 : 0; // Cargo adicional por urgente
    $insurance_cost = $insurance ? ($value * 0.05) : 0; // 5% del valor declarado
    $estimated_cost = $base_cost + $weight_cost + $urgent_cost + $insurance_cost;

    // Asegurarse que ninguna variable sea NULL
    $usuario_id = (int)($usuario_id ?? 0);
    $name = $name ?? '';
    $email = $email ?? '';
    $phone = $phone ?? '';
    $office_phone = $office_phone ?? '';
    $origin = $origin ?? '';
    $destination = $destination ?? '';
    $description = $description ?? '';
    $value = (float)($value ?? 0);
    $tracking_number = $tracking_number ?? '';
    $delivery_date = $delivery_date ?? '';
    $package_type = $package_type ?? '';
    $weight = (float)($weight ?? 0);
    $insurance = (int)($insurance ?? 0);
    $urgent = (int)($urgent ?? 0);
    $additional_notes = $additional_notes ?? '';
    $image_path = $image_path ?? '';
    $estimated_cost = (float)($estimated_cost ?? 0);

    // Asegurarse que los valores sean del tipo correcto para bind_param
    $usuario_id = (int)$usuario_id;  // Convertir a entero
    $value = (float)$value;  // Convertir a float
    $weight = (float)$weight;  // Convertir a float
    $insurance = (int)$insurance;  // Asegurar que sea entero
    $urgent = (int)$urgent;  // Asegurar que sea entero
    $estimated_cost = (float)$estimated_cost;  // Convertir a float

    // Si $delivery_date es NULL, convertirlo a cadena vacía
    $delivery_date = $delivery_date ?? '';
    $additional_notes = $additional_notes ?? '';

    // Insertar datos en la base de datos con campos actualizados
    $stmt = $conn->prepare(
        "INSERT INTO envios (
            usuario_id, name, email, phone, office_phone, origin, destination, 
            description, value, tracking_number, delivery_date, package_type, 
            weight, insurance, urgent, additional_notes, package_image, estimated_cost
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "isssssssdsssdiissd", // Corregido el último carácter (d en lugar de s)
        $usuario_id,
        $name,
        $email,
        $phone,
        $office_phone,
        $origin,
        $destination,
        $description,
        $value,
        $tracking_number,
        $delivery_date,
        $package_type,
        $weight,
        $insurance,
        $urgent,
        $additional_notes,
        $image_path,
        $estimated_cost   // Este es double (d), no string (s)
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = "Envío registrado exitosamente. Tu número de seguimiento es: $tracking_number";

        // Enviar correo con detalles del envío
        if (function_exists('enviarCorreoConfirmacion')) {
            enviarCorreoConfirmacion($email, $name, $tracking_number, $estimated_cost);
        }

        header("Location: ../php/forms.php");
        exit();
    } else {
        $_SESSION['error'] = "Error al guardar el envío: " . $stmt->error;
        header("Location: ../php/forms.php");
        exit();
    }
}
