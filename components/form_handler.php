<?php
session_start();
include 'db_connection.php';

// Cargar variables de entorno antes de incluir email_confirmacion.php
require_once __DIR__ . '/../config/load_env.php';
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

    // Construir la dirección de origen completa
    $origin = htmlspecialchars(trim($_POST['origin_street'])) . ' ' .
        htmlspecialchars(trim($_POST['origin_number'])) . ', ' .
        htmlspecialchars(trim($_POST['origin_colony'])) . ', ' .
        htmlspecialchars(trim($_POST['origin_postal_code'])) . ', ' .
        htmlspecialchars(trim($_POST['origin_city'])) . ', ' .
        htmlspecialchars(trim($_POST['origin_state'])) . ', ' .
        htmlspecialchars(trim($_POST['origin_country']));

    // Construir la dirección de destino completa
    $destination = htmlspecialchars(trim($_POST['destination_street'])) . ' ' .
        htmlspecialchars(trim($_POST['destination_number'])) . ', ' .
        htmlspecialchars(trim($_POST['destination_colony'] ?? '')) . ', ' .
        htmlspecialchars(trim($_POST['destination_postal_code'])) . ', ' .
        htmlspecialchars(trim($_POST['destination_city'])) . ', ' .
        htmlspecialchars(trim($_POST['destination_state'])) . ', ' .
        htmlspecialchars(trim($_POST['destination_country']));

    $description = htmlspecialchars(trim($_POST['description']));
    $value = filter_var(trim($_POST['value'] ?? 0), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    // Nuevos campos
    $delivery_date = isset($_POST['delivery_date']) ? htmlspecialchars(trim($_POST['delivery_date'])) : null;
    $package_type = isset($_POST['package_type']) ? htmlspecialchars(trim($_POST['package_type'])) : null;
    $weight = isset($_POST['weight']) ? filter_var(trim($_POST['weight']), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : null;
    $insurance = isset($_POST['insurance']) ? 1 : 0;
    $urgent = isset($_POST['urgent']) ? 1 : 0;
    $additional_notes = isset($_POST['additional_notes']) ? htmlspecialchars(trim($_POST['additional_notes'])) : null;

    // Obtener el costo calculado del formulario
    $estimated_cost = 0; // Valor predeterminado
    if (isset($_POST['hidden_calculated_cost']) && trim($_POST['hidden_calculated_cost']) !== '') {
        $estimated_cost = filter_var(
            trim($_POST['hidden_calculated_cost']),
            FILTER_SANITIZE_NUMBER_FLOAT,
            FILTER_FLAG_ALLOW_FRACTION
        );
    }

    // Si después del filtrado sigue sin ser numérico, asegurar que sea 0
    if (!is_numeric($estimated_cost)) {
        $estimated_cost = 0;
    }

    // Generar número de seguimiento único
    $tracking_number = 'MENDEZ-' . strtoupper(substr(md5(uniqid()), 0, 8));

    // Estado de pago inicial
    $estado_pago = 'pendiente';

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

    // Insertar datos en la base de datos con campos actualizados
    $stmt = $conn->prepare(
        "INSERT INTO envios (
            usuario_id, name, email, phone, office_phone, origin, destination, 
            description, value, tracking_number, delivery_date, package_type, 
            weight, insurance, urgent, additional_notes, package_image, estimated_cost, estado_pago
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "isssssssdsssdiissds",
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
        $estimated_cost,
        $estado_pago
    );

    if ($stmt->execute()) {
        // Generar enlace de pago
        $payment_link = "http://localhost/Programacion-de-formulario-con-BD/payment.php?tracking=" . urlencode($tracking_number) . "&amount=" . $estimated_cost;

        // Registrar lo que estamos enviando para depuración
        error_log("Enviando correo a: $email, Tracking: $tracking_number, Monto: $estimated_cost, Link: $payment_link");

        try {
            // Enviar correo con enlace de pago y capturar el resultado
            $correo_enviado = enviarCorreoConfirmacionConPago($email, $name, $tracking_number, $estimated_cost, $payment_link);

            if ($correo_enviado === true) {
                $_SESSION['success'] = "Envío registrado exitosamente. Se ha enviado un correo con instrucciones para completar el pago.";
            } else {
                $_SESSION['warning'] = "Envío registrado, pero hubo un problema al enviar el correo. Usa el botón de pago en esta página.";
                error_log("Error al enviar correo: " . print_r($correo_enviado, true));
            }
        } catch (Exception $e) {
            $_SESSION['warning'] = "Envío registrado, pero hubo un problema al enviar el correo. Usa el botón de pago en esta página.";
            error_log("Excepción al enviar correo: " . $e->getMessage());
        }

        // Redirigir a una página de confirmación
        header("Location: ../php/submit_success.php?tracking=" . urlencode($tracking_number));
        exit();
    } else {
        $_SESSION['error'] = "Error al registrar el envío: " . $stmt->error;
        header("Location: ../php/forms.php");
        exit();
    }
}
