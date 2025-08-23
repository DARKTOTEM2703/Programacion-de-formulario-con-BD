<?php
session_start();

// ✅ AGREGAR LA CONEXIÓN A LA BASE DE DATOS
require_once 'db_connection.php';

// ✅ VERIFICAR QUE EL USUARIO ESTÉ LOGUEADO
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../php/login.php?error=login_required');
    exit();
}

// Generar token CSRF si no existe
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Cargar variables de entorno
require_once __DIR__ . '/../config/load_env.php';
include 'email_confirmacion.php';

// ✅ VERIFICAR QUE LA CONEXIÓN ESTÉ DISPONIBLE
if (!isset($conn) || $conn->connect_error) {
    $_SESSION['error'] = 'Error de conexión a la base de datos. Intenta más tarde.';
    header('Location: ../php/forms.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ✅ VALIDACIÓN CSRF MEJORADA
    $csrf_token = $_POST['csrf_token'] ?? '';
    $session_token = $_SESSION['csrf_token'] ?? '';
    
    // Verificar que ambos tokens existan y coincidan
    if (empty($csrf_token) || empty($session_token) || !hash_equals($session_token, $csrf_token)) {
        $_SESSION['error'] = 'Token de seguridad inválido. Intenta nuevamente.';
        header('Location: ../php/forms.php');
        exit();
    }

    // ✅ VALIDAR USUARIO AUTENTICADO
    $usuario_id = $_POST['usuario_id'] ?? null;
    if (empty($usuario_id) || !isset($_SESSION['usuario_id']) || $_SESSION['usuario_id'] != $usuario_id) {
        $_SESSION['error'] = 'Sesión inválida. Por favor, inicia sesión nuevamente.';
        header('Location: ../php/login.php');
        exit();
    }

    // ✅ DEBUG: Verificar conexión
    if (!isset($conn)) {
        error_log("❌ ERROR: Variable \$conn no definida");
        $_SESSION['error'] = 'Error interno del servidor (DB-001)';
        header('Location: ../php/forms.php');
        exit();
    }

    if ($conn->connect_error) {
        error_log("❌ ERROR: Falla conexión DB: " . $conn->connect_error);
        $_SESSION['error'] = 'Error de base de datos (DB-002)';
        header('Location: ../php/forms.php');
        exit();
    }

    error_log("✅ DEBUG: Conexión DB OK, iniciando procesamiento formulario");

    // Datos básicos del formulario
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
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
    $estimated_cost = 0;
    if (isset($_POST['hidden_calculated_cost']) && trim($_POST['hidden_calculated_cost']) !== '') {
        $estimated_cost = filter_var(
            trim($_POST['hidden_calculated_cost']),
            FILTER_SANITIZE_NUMBER_FLOAT,
            FILTER_FLAG_ALLOW_FRACTION
        );
    }

    if (!is_numeric($estimated_cost)) {
        $estimated_cost = 0;
    }

    // Generar número de seguimiento único
    $tracking_number = 'MENDEZ-' . strtoupper(substr(md5(uniqid()), 0, 8));

    // Estado de pago inicial
    $estado_pago = 'pendiente';

    // Manejo SEGURO de imágenes - NO interrumpe el proceso
    $image_path = '';
    $imagen_warning = '';

    if (isset($_FILES['package_image']) && $_FILES['package_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        try {
            if (!in_array($_FILES['package_image']['type'], $allowed_types)) {
                throw new Exception("Formato de imagen no válido. Use JPG, PNG o GIF.");
            }

            if ($_FILES['package_image']['size'] > $max_size) {
                throw new Exception("Imagen muy grande. Máximo 2MB.");
            }

            $upload_dir = '../uploads/packages/';
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    throw new Exception("No se pudo crear directorio de uploads.");
                }
            }

            $filename = 'pkg_' . date('Ymd_His') . '_' . uniqid() . '.' . 
                       pathinfo($_FILES['package_image']['name'], PATHINFO_EXTENSION);
            $upload_path = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['package_image']['tmp_name'], $upload_path)) {
                $image_path = 'uploads/packages/' . $filename;
                error_log("✅ Imagen subida: " . $image_path);
            } else {
                throw new Exception("Error moviendo archivo subido.");
            }

        } catch (Exception $e) {
            error_log("⚠️ Error imagen: " . $e->getMessage());
            $imagen_warning = "Advertencia: " . $e->getMessage() . " El envío continuará sin imagen.";
            $image_path = '';
        }
    } else if (isset($_FILES['package_image']) && $_FILES['package_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'Archivo muy grande (configuración PHP)',
            UPLOAD_ERR_FORM_SIZE => 'Archivo muy grande (formulario)',
            UPLOAD_ERR_PARTIAL => 'Archivo subido parcialmente',
            UPLOAD_ERR_NO_TMP_DIR => 'Directorio temporal faltante',
            UPLOAD_ERR_CANT_WRITE => 'Error escribiendo archivo',
            UPLOAD_ERR_EXTENSION => 'Extensión PHP bloqueó subida'
        ];
        $error_msg = $error_messages[$_FILES['package_image']['error']] ?? 'Error desconocido';
        error_log("⚠️ Error upload: " . $error_msg);
        $imagen_warning = "Advertencia: " . $error_msg . " Continuando sin imagen.";
        $image_path = '';
    }

    // Validar que valores numéricos no sean null
    $weight = $weight ?? 0;
    $value = $value ?? 0; 
    $estimated_cost = $estimated_cost ?? 0;

    // Insertar datos en la base de datos
    $stmt = $conn->prepare("
        INSERT INTO envios (
            usuario_id, name, email, phone, office_phone, origin, destination, 
            description, value, tracking_number, delivery_date, package_type, 
            weight, insurance, urgent, additional_notes, package_image, estimated_cost, estado_pago, pin_seguro
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "isssssssdsssdiissdsi",
        $usuario_id,        // i
        $name,             // s
        $email,            // s  
        $phone,            // s
        $office_phone,     // s
        $origin,           // s
        $destination,      // s
        $description,      // s
        $value,            // d
        $tracking_number,  // s
        $delivery_date,    // s
        $package_type,     // s
        $weight,           // d
        $insurance,        // i
        $urgent,           // i
        $additional_notes, // s
        $image_path,       // s
        $estimated_cost,   // d
        $estado_pago,      // s
        $pin_seguro        // i
    );

    if ($stmt->execute()) {
        // Generar enlace de pago
        $payment_link = "http://localhost/Programacion-de-formulario-con-BD/payment.php?tracking=" . urlencode($tracking_number) . "&amount=" . $estimated_cost;

        error_log("Enviando correo a: $email, Tracking: $tracking_number, Monto: $estimated_cost, Link: $payment_link");

        try {
            $attachment_path = null;
            if (!empty($image_path) && file_exists('../' . $image_path)) {
                $attachment_path = __DIR__ . '/../' . $image_path;
                error_log("📎 Adjuntando imagen al correo: " . $attachment_path);
            }

            // Generar el QR del envío usando el tracking_number
            $qr_data = $tracking_number; // Información que contendrá el QR
            $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qr_data);
            error_log("✅ QR generado: " . $qr_url);

            // Enviar correo
            $correo_enviado = enviarCorreoEnvio(
                $email,
                $name,
                $tracking_number,
                $estimated_cost,
                $qr_url,           // QR embebido
                $payment_link,     // Enlace de pago
                $attachment_path   // Foto adjunta
            );

            if ($correo_enviado === true) {
                $mensaje_exito = "✅ Envío registrado y correo enviado exitosamente.";
                if (!empty($imagen_warning)) {
                    $mensaje_exito .= " " . $imagen_warning;
                }
                $_SESSION['success'] = $mensaje_exito;
            } else {
                $_SESSION['warning'] = "⚠️ Envío registrado, pero problema con correo. Usa botón de pago.";
                error_log("❌ Error correo: " . print_r($correo_enviado, true));
            }
        } catch (Exception $e) {
            $_SESSION['warning'] = "⚠️ Envío registrado, pero problema con correo.";
            error_log("💥 Excepción correo: " . $e->getMessage());
        }

        header("Location: ../php/submit_success.php?tracking=" . urlencode($tracking_number));
        exit();
    } else {
        $_SESSION['error'] = "Error al registrar el envío: " . $stmt->error;
        error_log("Error SQL: " . $stmt->error);
        header("Location: ../php/forms.php");
        exit();
    }
}
