<?php
// Configurar el archivo de registro para errores
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../google_oauth.log');

// Registrar inicio y parámetros
error_log("=== INICIO GOOGLE LOGIN HANDLER ===");
error_log("Método HTTP: " . $_SERVER['REQUEST_METHOD']);
error_log("URI solicitado: " . $_SERVER['REQUEST_URI']);
error_log("GET params: " . json_encode($_GET));
error_log("POST params: " . json_encode($_POST));
error_log("SESSION: " . json_encode(isset($_SESSION) ? $_SESSION : []));

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require '../vendor/autoload.php'; // Asegúrate de tener instalado el cliente de Google con Composer
require 'db_connection.php';
require 'email_service.php'; // Incluye el servicio de correo

// Cargar las variables de entorno
require 'config.php';

// Verificar que las variables de entorno estén configuradas
if (!isset($_ENV['GOOGLE_CLIENT_ID'])) {
    $_SESSION['error'] = "Error: Google Client ID no configurado";
    header('Location: ../php/login.php');
    exit();
}

// Manejar el token JWT recibido vía POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['credential'])) {
    $jwt = $_POST['credential'];

    try {
        // Dividir el JWT en sus partes: header.payload.signature
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new Exception("Token JWT inválido");
        }

        // Decodificar la parte del payload (segunda parte)
        $payload = json_decode(base64_decode(str_replace(
            ['-', '_'],
            ['+', '/'],
            $parts[1]
        )), true);

        if (!$payload) {
            throw new Exception("No se pudo decodificar el payload del JWT");
        }

        error_log("Información de usuario: " . json_encode($payload));

        // Extraer información del usuario
        $google_id = $payload['sub'];
        $name = $payload['name'];
        $email = $payload['email'];

        // Verificar usuario en la base de datos
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE google_id = ?");
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $conn->error);
        }

        $stmt->bind_param("s", $google_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Usuario existente
            $user = $result->fetch_assoc();
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nombre_usuario'] = $user['nombre_usuario'];
            $_SESSION['success'] = "Inicio de sesión exitoso";
        } else {
            // Nuevo usuario, registrarlo
            $stmt = $conn->prepare("INSERT INTO usuarios (google_id, nombre_usuario, email) VALUES (?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Error preparando consulta: " . $conn->error);
            }

            $stmt->bind_param("sss", $google_id, $name, $email);
            if ($stmt->execute()) {
                $_SESSION['usuario_id'] = $conn->insert_id;
                $_SESSION['nombre_usuario'] = $name;
                $_SESSION['success'] = "Cuenta creada con éxito";

                // Enviar correo de bienvenida
                $resultadoCorreo = enviarCorreo($email, $name);
                if ($resultadoCorreo !== true) {
                    error_log("Error al enviar correo: " . $resultadoCorreo);
                }
            } else {
                throw new Exception("Error insertando usuario: " . $stmt->error);
            }
        }

        header("Location: ../php/dashboard.php");
        exit();
    } catch (Exception $e) {
        error_log("Error en autenticación JWT: " . $e->getMessage());
        $_SESSION['error'] = "Error al autenticar con Google: " . $e->getMessage();
        header('Location: ../php/login.php');
        exit();
    }
} else {
    // Si no hay credenciales en POST, redirigir con error
    $_SESSION['error'] = "No se recibieron credenciales de autenticación";
    header('Location: ../php/login.php');
    exit();
}
