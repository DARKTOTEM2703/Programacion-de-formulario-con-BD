<?php
// filepath: c:\xampp\htdocs\Programacion-de-formulario-con-BD\admin\functions.php

/**
 * Funciones específicas para el panel administrativo
 */

/**
 * Obtiene un listado de usuarios con filtros
 * @param array $filters Filtros de búsqueda
 * @param int $page Número de página
 * @param int $limit Elementos por página
 * @return array Resultado con usuarios y paginación
 */
function getUsersList($filters = [], $page = 1, $limit = 20)
{
    global $conn;

    // Calcular offset para paginación
    $offset = ($page - 1) * $limit;

    // Construir consulta con filtros
    $where_clauses = [];
    $params = [];
    $types = '';

    // Filtrar por rol
    if (!empty($filters['rol_id'])) {
        $where_clauses[] = "u.rol_id = ?";
        $params[] = $filters['rol_id'];
        $types .= 'i';
    }

    // Filtrar por estado
    if (!empty($filters['status'])) {
        $where_clauses[] = "u.status = ?";
        $params[] = $filters['status'];
        $types .= 's';
    }

    // Búsqueda por nombre o email
    if (!empty($filters['search'])) {
        $where_clauses[] = "(u.nombre_usuario LIKE ? OR u.email LIKE ?)";
        $search_param = "%{$filters['search']}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'ss';
    }

    $where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

    // Contar total de registros para paginación
    $count_sql = "SELECT COUNT(*) as total FROM usuarios u $where_sql";
    $stmt = $conn->prepare($count_sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];

    // Consulta principal
    $sql = "SELECT u.id, u.nombre_usuario, u.email, u.created_at, u.status, 
                  r.nombre as rol_nombre, r.id as rol_id
           FROM usuarios u
           JOIN roles r ON u.rol_id = r.id
           $where_sql
           ORDER BY u.created_at DESC
           LIMIT ? OFFSET ?";

    // Añadir parámetros de paginación
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    return [
        'users' => $users,
        'total' => $total,
        'pages' => ceil($total / $limit),
        'page' => $page
    ];
}

/**
 * Obtiene la lista de roles del sistema
 * @return array Lista de roles
 */
function getRoles()
{
    global $conn;
    $result = $conn->query("SELECT id, nombre FROM roles ORDER BY id");
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Actualiza el estado de un usuario
 * @param int $user_id ID del usuario
 * @param string $status Nuevo estado
 * @return bool Resultado de la operación
 */
function updateUserStatus($user_id, $status)
{
    global $conn;

    // Validar estado
    $valid_statuses = ['activo', 'suspendido', 'eliminado', 'pendiente'];
    if (!in_array($status, $valid_statuses)) {
        return false;
    }

    $stmt = $conn->prepare("UPDATE usuarios SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $user_id);
    $result = $stmt->execute();

    if ($result) {
        // Registrar en log de auditoría
        auditLog('cambio_estado', 'usuario', $user_id, "Cambio a estado: $status");
    }

    return $result;
}

/**
 * Registra actividad en log de auditoría
 * @param string $action Acción realizada
 * @param string $target_type Tipo de objetivo
 * @param int $target_id ID del objetivo
 * @param string $details Detalles adicionales
 */
function auditLog($action, $target_type, $target_id, $details = '')
{
    global $conn;
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'];

    // Si existe tabla de auditoría, registrar
    try {
        $stmt = $conn->prepare("INSERT INTO audit_log 
                              (usuario_id, action, target_type, target_id, ip_address, details) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ississ", $usuario_id, $action, $target_type, $target_id, $ip, $details);
        $stmt->execute();
    } catch (Exception $e) {
        // Si la tabla no existe, solo registrar en log
        logSecurityEvent('AUDIT', "$action sobre $target_type #$target_id: $details", $usuario_id);
    }
}