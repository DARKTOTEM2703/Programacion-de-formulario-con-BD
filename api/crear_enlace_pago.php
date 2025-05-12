<?php
require_once '../vendor/autoload.php'; // Asegúrate de instalar Stripe con Composer
require_once '../components/db_connection.php';

header('Content-Type: application/json');

// Configurar la clave secreta de Stripe
\Stripe\Stripe::setApiKey('TU_CLAVE_SECRETA_DE_STRIPE');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['amount'])) {
    echo json_encode(['success' => false, 'message' => 'Monto no proporcionado']);
    exit();
}

$amount = $data['amount'] * 100; // Convertir a centavos

try {
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'mxn',
                'product_data' => [
                    'name' => 'Servicio de Envío',
                ],
                'unit_amount' => $amount,
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'http://localhost/Programacion-de-formulario-con-BD/php/success.php',
        'cancel_url' => 'http://localhost/Programacion-de-formulario-con-BD/php/cancel.php',
    ]);

    echo json_encode(['success' => true, 'payment_url' => $session->url]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
