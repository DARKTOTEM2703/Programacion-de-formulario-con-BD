<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../components/db_connection.php';
require_once __DIR__ . '/../components/url_helper.php'; 

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'error' => 'no_session']);
    exit;
}

$usuario_id = (int)$_SESSION['usuario_id'];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$solo_pendientes = isset($_GET['pendientes']) && $_GET['pendientes'] == '1';

try {
    // Usar el procedimiento almacenado para obtener notificaciones
    $stmt = $conn->prepare("CALL sp_obtener_notificaciones(?, ?, ?)");
    $stmt->bind_param("iii", $usuario_id, $limit, $solo_pendientes);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    // Al procesar las notificaciones:
    while ($row = $result->fetch_assoc()) {
        // Completar la URL relativa con la base_url 
        $enlace = $row['enlace'];
        if ($enlace && substr($enlace, 0, 1) == '/') {
            // Obtener base_url
            $base = rtrim(base_url(), '/');
            $base = preg_replace('|/api$|', '', $base);
            
            $enlace = $base . $enlace;
        }
        
        $notifications[] = [
            'id' => (int)$row['id'],
            'tipo' => $row['tipo'],
            'titulo' => $row['titulo'],
            'asunto' => $row['titulo'], 
            'mensaje' => $row['mensaje'],
            'contenido' => $row['mensaje'],
            'enlace' => $enlace,
            'link' => $enlace, 
            'leida' => (int)$row['leida'],
            'status' => $row['leida'] == 1 ? 'leido' : 'pendiente',
            'created_at' => $row['created_at'],
            'tracking_code' => $row['tracking_code'] ?? null
        ];
    }
    
    echo json_encode([
        'ok' => true,
        'notifications' => $notifications
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'ok' => false, 
        'error' => 'database_error',
        'message' => $e->getMessage()
    ]);
}
?>