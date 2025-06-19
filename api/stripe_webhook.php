<?php
// filepath: c:\xampp\htdocs\Programacion-de-formulario-con-BD\api\stripe_webhook.php
require_once '../vendor/autoload.php';
require_once '../components/db_connection.php';
require_once '../config/load_env.php';

// Configurar directorio de logs
$logDir = '../logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

// Registro de depuración
$log_file = fopen('../logs/stripe_webhook.log', 'a');
fwrite($log_file, date('Y-m-d H:i:s') . " - Webhook recibido\n");

// Recuperar la carga útil
$payload = @file_get_contents('php://input');
fwrite($log_file, "Payload: " . $payload . "\n");

// Verificar si el encabezado está presente
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;
fwrite($log_file, "Signature header present: " . ($sig_header ? 'Yes' : 'No') . "\n");
if ($sig_header) {
    fwrite($log_file, "Signature value: " . $sig_header . "\n");
}

// Obtener la clave de webhook desde .env de forma segura
$endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? null;

// Verificar si la clave existe
if (!$endpoint_secret && !$isLocalTesting) {
    fwrite($log_file, "ERROR: No se ha configurado STRIPE_WEBHOOK_SECRET en el archivo .env\n");
    http_response_code(500);
    echo json_encode(['error' => 'Error de configuración del servidor']);
    fclose($log_file);
    exit();
}

fwrite($log_file, "Using endpoint secret: " . substr($endpoint_secret, 0, 10) . "...\n");

// Modo de prueba local
$isLocalTesting = empty($sig_header);
if ($isLocalTesting) {
    fwrite($log_file, "MODO DE PRUEBA LOCAL ACTIVADO - Saltando verificación de firma\n");

    // Para pruebas, decodificar directamente el JSON
    try {
        $event = json_decode($payload, false);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON inválido: " . json_last_error_msg());
        }
    } catch (Exception $e) {
        fwrite($log_file, "Error al decodificar JSON: " . $e->getMessage() . "\n");
        http_response_code(400);
        echo json_encode(['error' => 'Payload inválido']);
        fclose($log_file);
        exit();
    }
} else {
    // Verificación normal para solicitudes reales de Stripe
    try {
        $event = \Stripe\Webhook::constructEvent(
            $payload,
            $sig_header,
            $endpoint_secret
        );
    } catch (\UnexpectedValueException $e) {
        fwrite($log_file, "Error de payload: " . $e->getMessage() . "\n");
        http_response_code(400);
        echo json_encode(['error' => 'Payload inválido']);
        fclose($log_file);
        exit();
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        fwrite($log_file, "Error de firma: " . $e->getMessage() . "\n");
        http_response_code(400);
        echo json_encode(['error' => 'Firma inválida']);
        fclose($log_file);
        exit();
    }
}

// Manejar el evento
$eventType = $isLocalTesting ? $event->type : $event->type;
fwrite($log_file, "Tipo de evento: " . $eventType . "\n");

if ($eventType == 'checkout.session.completed') {
    $session = $isLocalTesting ? $event->data->object : $event->data->object;

    // Extraer información del metadata
    $tracking_number = $session->metadata->tracking_number ?? null;
    $envio_id = $session->metadata->envio_id ?? null;

    fwrite($log_file, "Tracking number: " . ($tracking_number ?? 'no disponible') . "\n");
    fwrite($log_file, "Envío ID: " . ($envio_id ?? 'no disponible') . "\n");

    if ($tracking_number && $envio_id) {
        // Actualizar el estado de pago en la base de datos
        $stmt = $conn->prepare("UPDATE envios SET estado_pago = 'pagado', fecha_pago = NOW() WHERE id = ? AND tracking_number = ?");
        $stmt->bind_param("is", $envio_id, $tracking_number);
        $result = $stmt->execute();

        fwrite($log_file, "Actualización de estado: " . ($result ? 'Exitosa' : 'Fallida') . "\n");

        if ($stmt->affected_rows == 0) {
            fwrite($log_file, "Advertencia: No se actualizó ningún registro en la tabla envios\n");
        }

        // También podrías registrar la transacción en una tabla de pagos o movimientos contables
        $stmt = $conn->prepare("
            INSERT INTO movimientos_contables 
            (tipo, concepto, monto, fecha_movimiento, categoria, created_by) 
            VALUES ('ingreso', ?, ?, NOW(), 'pagos_online', 1)
        ");

        $monto = $session->amount_total / 100; // Convertir centavos a pesos
        $concepto = "Pago en línea por envío #{$tracking_number}";

        $stmt->bind_param("sd", $concepto, $monto);
        $result = $stmt->execute();

        fwrite($log_file, "Registro de movimiento: " . ($result ? 'Exitoso' : 'Fallido') . "\n");
    } else {
        fwrite($log_file, "Error: Falta información de tracking o envío\n");
    }
}

fwrite($log_file, "Webhook procesado correctamente\n");
fclose($log_file);

http_response_code(200);
echo json_encode(['status' => 'success']);