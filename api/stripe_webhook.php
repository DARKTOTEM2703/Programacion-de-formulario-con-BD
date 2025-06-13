<?php
require_once '../vendor/autoload.php';
require_once '../components/db_connection.php';

// Recuperar la carga útil
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

// En producción, establece esta clave en un archivo de configuración seguro
$endpoint_secret = 'whsec_TU_CLAVE_DEL_WEBHOOK';

try {
    // Verificar que la petición viene de Stripe
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sig_header,
        $endpoint_secret
    );
} catch (\UnexpectedValueException $e) {
    // Payload inválido
    http_response_code(400);
    exit();
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // Firma inválida
    http_response_code(400);
    exit();
}

// Manejar el evento
if ($event->type == 'checkout.session.completed') {
    $session = $event->data->object;

    // Extraer información del metadata
    $tracking_number = $session->metadata->tracking_number;
    $envio_id = $session->metadata->envio_id;

    if ($tracking_number && $envio_id) {
        // Actualizar el estado de pago en la base de datos
        $stmt = $conn->prepare("UPDATE envios SET estado_pago = 'pagado', fecha_pago = NOW() WHERE id = ? AND tracking_number = ?");
        $stmt->bind_param("is", $envio_id, $tracking_number);
        $stmt->execute();

        // También podrías registrar la transacción en una tabla de pagos o movimientos contables
        $stmt = $conn->prepare("
            INSERT INTO movimientos_contables 
            (tipo, concepto, monto, fecha_movimiento, categoria, created_by) 
            VALUES ('ingreso', ?, ?, NOW(), 'pagos_online', 1)
        ");

        $monto = $session->amount_total / 100; // Convertir centavos a pesos
        $concepto = "Pago en línea por envío #{$tracking_number}";

        $stmt->bind_param("sd", $concepto, $monto);
        $stmt->execute();
    }
}

http_response_code(200);