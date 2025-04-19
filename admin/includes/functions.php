<?php

/**
 * Funciones auxiliares para el panel administrativo
 * con enfoque en seguridad
 */

/**
 * Sanitiza una cadena para salida HTML segura
 */
function safeOutput($string)
{
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Registra un evento importante en el log de seguridad
 */
function logSecurityEvent($event_type, $details, $user_id = null)
{
    $log_file = dirname(__DIR__, 2) . '/logs/security.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    $user_info = $user_id ? "Usuario #$user_id" : "No autenticado";

    $log_entry = "[$timestamp] [$event_type] [$ip] [$user_info] [$user_agent] $details\n";

    error_log($log_entry, 3, $log_file);
}

/**
 * Valida y sanitiza una ID numérica
 */
function validateId($id)
{
    $id = filter_var($id, FILTER_VALIDATE_INT);
    return ($id !== false && $id > 0) ? $id : false;
}

/**
 * Genera un hash seguro para contraseñas
 */
function secureHash($password)
{
    // Usar algoritmo Argon2id si está disponible (PHP 7.3+)
    if (defined('PASSWORD_ARGON2ID')) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64MB
            'time_cost'   => 4,     // 4 iteraciones
            'threads'     => 3      // 3 hilos paralelos
        ]);
    }

    // Alternativa: usar Bcrypt con costo alto
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Genera un token aleatorio para operaciones sensibles
 */
function generateSecureToken($length = 32)
{
    return bin2hex(random_bytes($length));
}

/**
 * Verifica si una solicitud proviene de la misma sesión y sitio
 */
function verifyRequestOrigin()
{
    // Verificar Referer
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'];

    if (empty($referer) || strpos($referer, $host) === false) {
        return false;
    }

    // Verificar que existe sesión activa
    if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['usuario_id'])) {
        return false;
    }

    return true;
}

/**
 * Limita el número de intentos para operaciones sensibles
 */
function rateLimiter($key, $max_attempts = 5, $timeout = 300)
{
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = [];
    }

    $now = time();
    $key = 'limit_' . md5($key);

    if (!isset($_SESSION['rate_limits'][$key])) {
        $_SESSION['rate_limits'][$key] = [
            'attempts' => 0,
            'timestamp' => $now
        ];
    }

    // Si ha pasado el tiempo de timeout, reiniciar contador
    if (($now - $_SESSION['rate_limits'][$key]['timestamp']) > $timeout) {
        $_SESSION['rate_limits'][$key] = [
            'attempts' => 1,
            'timestamp' => $now
        ];
        return true;
    }

    // Incrementar contador de intentos
    $_SESSION['rate_limits'][$key]['attempts']++;

    // Verificar si ha excedido el límite
    if ($_SESSION['rate_limits'][$key]['attempts'] > $max_attempts) {
        // Registrar en log de seguridad
        logSecurityEvent('RATE_LIMIT', "Excedido límite para operación: $key", $_SESSION['usuario_id'] ?? null);
        return false;
    }

    return true;
}

/**
 * Genera log de auditoría para operaciones críticas
 */
function auditLog($action, $target_type, $target_id, $details = '')
{
    global $conn;

    $usuario_id = $_SESSION['usuario_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'];

    if ($conn && $usuario_id) {
        $stmt = $conn->prepare("INSERT INTO audit_log (usuario_id, action, target_type, target_id, ip_address, details) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ississ", $usuario_id, $action, $target_type, $target_id, $ip, $details);
        $stmt->execute();
    }

    // También guardar en logs del sistema
    $log_message = sprintf(
        "[AUDIT] User #%s performed %s on %s #%s from %s: %s",
        $usuario_id ?? 'unknown',
        $action,
        $target_type,
        $target_id,
        $ip,
        $details
    );
    error_log($log_message, 3, dirname(__DIR__, 2) . '/logs/admin_audit.log');
}

/**
 * Funciones específicas para operaciones de base de datos
 */

/**
 * Obtiene un único registro por ID
 */
function getById($table, $id, $fields = '*')
{
    global $conn;
    $stmt = $conn->prepare("SELECT $fields FROM $table WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Lista registros con paginación y filtros
 */
function getList($table, $filters = [], $page = 1, $limit = 20, $orderBy = 'id DESC')
{
    global $conn;

    $offset = ($page - 1) * $limit;
    $where = '';
    $params = [];
    $types = '';

    // Construir cláusulas WHERE
    if (!empty($filters)) {
        // Implementar lógica para generar filtros dinámicos
    }

    // Consulta principal
    $sql = "SELECT * FROM $table $where ORDER BY $orderBy LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);

    // Añadir parámetros de paginación
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    // Contar total para paginación
    $count_sql = "SELECT COUNT(*) as total FROM $table $where";
    $stmt = $conn->prepare($count_sql);
    if (!empty($params)) {
        array_pop($params); // Quitar offset
        array_pop($params); // Quitar limit
        $types = substr($types, 0, -2); // Ajustar tipos
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];

    return [
        'data' => $result->fetch_all(MYSQLI_ASSOC),
        'total' => $total,
        'pages' => ceil($total / $limit),
        'page' => $page
    ];
}

/**
 * Funciones de interfaz de usuario
 */

/**
 * Genera un componente de paginación
 */
function generatePagination($current_page, $total_pages, $base_url, $params = [])
{
    $pagination = '<nav aria-label="Navegación"><ul class="pagination justify-content-center">';

    // Botón anterior
    $prev_disabled = $current_page <= 1 ? 'disabled' : '';
    $prev_url = $base_url . '?page=' . ($current_page - 1);

    // Añadir parámetros adicionales
    foreach ($params as $key => $value) {
        $prev_url .= "&{$key}=" . urlencode($value);
    }

    $pagination .= '<li class="page-item ' . $prev_disabled . '">
                    <a class="page-link" href="' . $prev_url . '">Anterior</a>
                </li>';

    // Números de página
    for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
        $active = $i === $current_page ? 'active' : '';
        $page_url = $base_url . '?page=' . $i;

        foreach ($params as $key => $value) {
            $page_url .= "&{$key}=" . urlencode($value);
        }

        $pagination .= '<li class="page-item ' . $active . '">
                        <a class="page-link" href="' . $page_url . '">' . $i . '</a>
                    </li>';
    }

    // Botón siguiente
    $next_disabled = $current_page >= $total_pages ? 'disabled' : '';
    $next_url = $base_url . '?page=' . ($current_page + 1);

    foreach ($params as $key => $value) {
        $next_url .= "&{$key}=" . urlencode($value);
    }

    $pagination .= '<li class="page-item ' . $next_disabled . '">
                    <a class="page-link" href="' . $next_url . '">Siguiente</a>
                </li>';

    $pagination .= '</ul></nav>';

    return $pagination;
}

/**
 * Genera un componente de alerta 
 */
function generateAlert($message, $type = 'info', $dismissible = true)
{
    $icon = '';
    switch ($type) {
        case 'success':
            $icon = '<i class="bi bi-check-circle-fill me-2"></i>';
            break;
        case 'danger':
            $icon = '<i class="bi bi-exclamation-triangle-fill me-2"></i>';
            break;
        case 'warning':
            $icon = '<i class="bi bi-exclamation-circle-fill me-2"></i>';
            break;
        default:
            $icon = '<i class="bi bi-info-circle-fill me-2"></i>';
    }

    $dismiss_button = $dismissible ? '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' : '';

    return '<div class="alert alert-' . $type . ($dismissible ? ' alert-dismissible' : '') . ' fade show" role="alert">
                <div class="d-flex align-items-center">
                    ' . $icon . safeOutput($message) . '
                </div>
                ' . $dismiss_button . '
            </div>';
}

// Más funciones UI...