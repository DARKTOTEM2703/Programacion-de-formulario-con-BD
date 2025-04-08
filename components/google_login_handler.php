<?php
// filepath: c:\xampp\htdocs\Programacion-de-formulario-con-BD\components\google_login_handler.php
error_log("Google login handler started");
require __DIR__ . '/../vendor/autoload.php'; // Instala Google Client Library con Composer

use Google\Client;
use Dotenv\Dotenv;

session_start();

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$client = new Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']); // Usar la variable de entorno

// Determinar si es una solicitud POST (API) o GET (redirección)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Procesar solicitud API (desde JavaScript)
    $data = json_decode(file_get_contents('php://input'), true);
    $token = $data['token'] ?? '';

    try {
        $payload = $client->verifyIdToken($token);
        if ($payload) {
            procesarUsuario($payload);
            $_SESSION['usuario_id'] = $payload['sub']; // ID único del usuario
            $_SESSION['usuario_nombre'] = $payload['name']; // Nombre del usuario
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    } catch (Exception $e) {
        error_log("Google authentication error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    // Procesar redirección
    $credential = $_GET['credential'] ?? '';

    if (empty($credential)) {
        header('Location: ../login.php?error=missing_credential');
        exit;
    }

    try {
        $payload = $client->verifyIdToken($credential);
        if ($payload) {
            procesarUsuario($payload);
            header('Location: ../index.php');
            exit;
        } else {
            header('Location: ../login.php?error=invalid_token');
            exit;
        }
    } catch (Exception $e) {
        error_log("Google authentication error: " . $e->getMessage());
        header('Location: ../login.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

function procesarUsuario($payload)
{
    $userId = $payload['sub'];
    $email = $payload['email'];
    $name = $payload['name'];

    $_SESSION['user'] = [
        'id' => $userId,
        'email' => $email,
        'nombre_usuario' => $name
    ];

    // Aquí podrías guardar el usuario en tu base de datos si es necesario
}
