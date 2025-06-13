<?php
require_once '../vendor/autoload.php'; // Asegúrate de instalar Stripe con Composer
require_once '../components/db_connection.php';

header('Content-Type: application/json');

// Configurar la clave secreta de Stripe
// En producción, usa una variable de entorno o archivo de configuración
\Stripe\Stripe::setApiKey('sk_test_TU_CLAVE_DE_STRIPE'); // Reemplaza con tu clave de prueba

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['amount'])) {
    echo json_encode(['success' => false, 'message' => 'Monto no proporcionado']);
    exit();
}

$amount = $data['amount'] * 100; // Convertir a centavos
$tracking_number = $data['tracking_number'] ?? '';
$email = $data['email'] ?? '';
$name = $data['name'] ?? '';
$envio_id = $data['envio_id'] ?? 0;

try {
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'customer_email' => $email,
        'line_items' => [[
            'price_data' => [
                'currency' => 'mxn', // Moneda en pesos mexicanos
                'product_data' => [
                    'name' => 'Envío #' . $tracking_number,
                    'description' => 'Servicio de transporte de paquetería',
                    'images' => ['https://via.placeholder.com/300x200?text=Envio+MENDEZ'], // Puedes poner el logo de tu empresa
                ],
                'unit_amount' => $amount,
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'metadata' => [
            'tracking_number' => $tracking_number,
            'envio_id' => $envio_id
        ],
        'success_url' => 'http://localhost/Programacion-de-formulario-con-BD/php/payment_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'http://localhost/Programacion-de-formulario-con-BD/php/payment_cancel.php',
    ]);

    echo json_encode(['success' => true, 'payment_url' => $session->url]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}