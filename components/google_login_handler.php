<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require '../vendor/autoload.php'; // Asegúrate de tener instalado el cliente de Google con Composer
include 'db_connection.php';

// Cargar las variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Verificar que las variables de entorno estén configuradas
if (!isset($_ENV['GOOGLE_CLIENT_ID']) || !isset($_ENV['GOOGLE_CLIENT_SECRET'])) {
    die("Error: Las variables de entorno GOOGLE_CLIENT_ID o GOOGLE_CLIENT_SECRET no están configuradas.");
}

$client = new Google\Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri('http://localhost/Programacion-de-formulario-con-BD/components/google_login_handler.php');
$client->addScope('email');
$client->addScope('profile');

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (!isset($token['error'])) {
        echo "Token recibido: " . json_encode($token) . "<br>";
        $client->setAccessToken($token['access_token']);
        $google_service = new Google\Service\Oauth2($client);
        $data = $google_service->userinfo->get();

        // Extraer datos del usuario
        $google_id = $data['id'];
        $name = $data['name'];
        $email = $data['email'];

        // Verificar si el usuario ya existe en la base de datos
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE google_id = ?");
        $stmt->bind_param("s", $google_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Usuario existente, iniciar sesión
            $user = $result->fetch_assoc();
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nombre_usuario'] = $user['nombre_usuario'];
        } else {
            // Nuevo usuario, guardar en la base de datos
            $stmt = $conn->prepare("INSERT INTO usuarios (google_id, nombre_usuario, email) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $google_id, $name, $email);
            if ($stmt->execute()) {
                $_SESSION['usuario_id'] = $conn->insert_id;
                $_SESSION['nombre_usuario'] = $name;
            } else {
                $_SESSION['error'] = "Error al guardar el usuario: " . $stmt->error;
                header("Location: ../login.php");
                exit();
            }
        }

        // Redirigir al usuario al índice principal
        header("Location: ../index.php");
        exit();
    } else {
        echo "Error al obtener el token: " . json_encode($token['error']);
        exit();
    }
} else {
    $_SESSION['error'] = "Error al autenticar con Google.";
    header("Location: ../login.php");
    exit();
}
