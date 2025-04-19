<?php
// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    // Configurar cookies seguras
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $httponly = true;

    ini_set('session.cookie_secure', $secure);
    ini_set('session.cookie_httponly', $httponly);
    ini_set('session.use_only_cookies', 1);

    session_start();
}

// Función para generar un nuevo token CSRF
function regenerateCSRFToken()
{
    $token = bin2hex(random_bytes(32));
    $_SESSION['admin_csrf_token'] = $token;
    $_SESSION['admin_csrf_token_time'] = time();
    return $token;
}

// Función para validar token CSRF
function validateCSRFToken($token)
{
    if (!isset($_SESSION['admin_csrf_token']) || !isset($_SESSION['admin_csrf_token_time'])) {
        return false;
    }

    // Verificar tiempo de expiración (1 hora)
    if (time() - $_SESSION['admin_csrf_token_time'] > 3600) {
        return false;
    }

    return hash_equals($_SESSION['admin_csrf_token'], $token);
}

// Verificación básica de autenticación
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    // Registrar intento de acceso no autorizado
    $ip = $_SERVER['REMOTE_ADDR'];
    $page = $_SERVER['REQUEST_URI'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $timestamp = date('Y-m-d H:i:s');

    // Escribir log de seguridad
    $log_message = "[$timestamp] Intento de acceso no autorizado: IP: $ip, URI: $page, User-Agent: $user_agent\n";
    error_log($log_message, 3, dirname(__DIR__, 2) . '/logs/security.log');

    // Redirigir a página de login con mensaje de error
    $_SESSION['error'] = "Acceso restringido. Se requieren credenciales de administrador.";
    header("Location: ../php/login.php");
    exit();
}

// Verificar si la sesión no ha expirado (30 minutos)
$session_timeout = 1800; // 30 minutos
if (!isset($_SESSION['last_activity']) || (time() - $_SESSION['last_activity'] > $session_timeout)) {
    // Destruir la sesión
    session_unset();
    session_destroy();

    // Redirigir a login
    $_SESSION['error'] = "Tu sesión ha expirado por inactividad.";
    header("Location: ../php/login.php");
    exit();
}

// Actualizar tiempo de última actividad
$_SESSION['last_activity'] = time();

// Generar token CSRF si no existe
if (!isset($_SESSION['admin_csrf_token'])) {
    regenerateCSRFToken();
}

// Verificar que el usuario tiene el rol y permisos correctos
require_once dirname(__DIR__, 2) . '/components/db_connection.php';

// Verificar en BD que el usuario sigue siendo admin
$stmt = $conn->prepare("SELECT rol_id FROM usuarios WHERE id = ? AND status = 'activo'");
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0 || $result->fetch_assoc()['rol_id'] !== 1) {
    // Destruir la sesión
    session_unset();
    session_destroy();

    // Redirigir a login
    $_SESSION['error'] = "Tu cuenta ha sido desactivada o tus permisos han cambiado.";
    header("Location: ../php/login.php");
    exit();
}

// Opcional: Regenerar ID de sesión periódicamente
if (!isset($_SESSION['last_regenerate']) || (time() - $_SESSION['last_regenerate'] > 300)) {
    session_regenerate_id(true);
    $_SESSION['last_regenerate'] = time();
}
