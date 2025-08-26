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

function getCredentialFromRequest(): ?string {
    // 1) Envío estándar de GIS (form post: credential=...)
    if (!empty($_POST['credential'])) return $_POST['credential'];

    // 2) Envío vía fetch JSON (ver JS/googleconection.js)
    $raw = file_get_contents('php://input');
    if ($raw) {
        $json = json_decode($raw, true);
        if (!empty($json['credential'])) return $json['credential'];
        if (!empty($json['token'])) return $json['token'];
    }
    return null;
}

$jwt = getCredentialFromRequest();
if (!$jwt) {
    $_SESSION['error'] = "No se recibió el token de Google";
    header('Location: ../php/login.php');
    exit();
}

try {
    $clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
    if (!$clientId) {
        $_SESSION['error'] = "Google Client ID no configurado";
        header('Location: ../php/login.php');
        exit();
    }

    // Validar token con librería oficial (verifica firma, exp, iss, aud)
    $client = new Google_Client();
    $client->setClientId($clientId);
    $payload = $client->verifyIdToken($jwt);

    if (!$payload) {
        throw new Exception("ID token inválido");
    }

    // Validaciones adicionales recomendadas
    $iss = $payload['iss'] ?? '';
    if (!in_array($iss, ['https://accounts.google.com', 'accounts.google.com'])) {
        throw new Exception("Issuer inválido");
    }
    if (empty($payload['email']) || empty($payload['email_verified'])) {
        throw new Exception("Email no verificado");
    }

    $google_id = $payload['sub'];
    $name      = $payload['name'] ?? ($payload['given_name'] ?? 'Usuario');
    $email     = $payload['email'];

    // Buscar o crear usuario
    $stmt = $conn->prepare("SELECT id, nombre_usuario, email, rol_id FROM usuarios WHERE google_id = ? OR email = ? LIMIT 1");
    $stmt->bind_param("ss", $google_id, $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows) {
        $user = $res->fetch_assoc();
        // Vincular google_id si faltaba
        if (empty($user['google_id'])) {
            $upd = $conn->prepare("UPDATE usuarios SET google_id=? WHERE id=?");
            $upd->bind_param("si", $google_id, $user['id']);
            $upd->execute();
        }
        $_SESSION['usuario_id']    = $user['id'];
        $_SESSION['nombre_usuario']= $user['nombre_usuario'];
        $_SESSION['rol_id']        = (int)$user['rol_id'];
        $_SESSION['email']         = $user['email'];
    } else {
        // Alta mínima (rol cliente = 2)
        $rol_id = 2;
        $stmt = $conn->prepare("INSERT INTO usuarios (google_id, nombre_usuario, email, rol_id, status) VALUES (?, ?, ?, ?, 'activo')");
        $stmt->bind_param("sssi", $google_id, $name, $email, $rol_id);
        $stmt->execute();
        $_SESSION['usuario_id']     = $stmt->insert_id;
        $_SESSION['nombre_usuario'] = $name;
        $_SESSION['rol_id']         = $rol_id;
        $_SESSION['email']          = $email;

        
        // ENVIAR CORREO DE BIENVENIDA SOLO SI ES USUARIO NUEVO
        $correo_result = enviarCorreo($email, $name, '');
        if ($correo_result === true) {
            error_log("Correo de bienvenida enviado a $email");
        } else {
            error_log("Error al enviar correo de bienvenida a $email: $correo_result");
        }
    }

    // Redirección por rol (mismo mapa usado en login normal)
    $redirects = [
        1 => '../admin/dashboard.php',
        2 => '../php/dashboard.php',
        3 => '../pwa/dashboard.php',
        4 => '../bodega/dashboard_bodega.php',
        5 => '../soporte/dashboard_soporte.php',
        6 => '../supervisor/dashboard_supervisor.php',
        7 => '../contador/dashboard_contador.php',
        8 => '../admin/dashboard.php'
    ];

    $destino = $redirects[$_SESSION['rol_id']] ?? '../php/dashboard.php';
    $_SESSION['success'] = "Inicio de sesión con Google exitoso";
    header("Location: " . $destino);
    exit();
} catch (Exception $e) {
    error_log("Google Auth error: " . $e->getMessage());
    $_SESSION['error'] = "Error al validar Google: " . $e->getMessage();
    header('Location: ../php/login.php');
    exit();
}