<?php
// filepath: c:\xampp\htdocs\Programacion-de-formulario-con-BD\components\google_login_handler.php
require 'vendor/autoload.php'; // Instala Google Client Library con Composer
require __DIR__ . '/config.php';

use Google\Client;

$client = new Client();
$client->setClientId(getenv('GOOGLE_CLIENT_ID')); // Usar la variable de entorno

// Obtener el token enviado desde el cliente
$data = json_decode(file_get_contents('php://input'), true);
$token = $data['token'] ?? '';

try {
    $payload = $client->verifyIdToken($token);
    if ($payload) {
        // Token válido, puedes obtener información del usuario
        $userId = $payload['sub'];
        $email = $payload['email'];
        $name = $payload['name'];

        // Aquí puedes guardar al usuario en tu base de datos o iniciar sesión
        session_start();
        $_SESSION['user'] = [
            'id' => $userId,
            'email' => $email,
            'name' => $name
        ];

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Token inválido']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}