<?php
// filepath: api/notifications_mark_read.php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../components/db_connection.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'error' => 'no_session']);
    exit;
}

$usuario_id = (int)$_SESSION['usuario_id'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    if (isset($input['all']) && $input['all'] === true) {
        // Marcar todas como leídas usando el SP
        $stmt = $conn->prepare("CALL sp_marcar_todas_leidas(?, @count)");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $stmt->close();
        
        // Obtener cantidad de filas afectadas
        $result = $conn->query("SELECT @count as count");
        $count = $result->fetch_assoc()['count'];
        
        echo json_encode([
            'ok' => true, 
            'message' => 'Todas las notificaciones marcadas como leídas',
            'count' => (int)$count
        ]);
    } else {
        // Marcar una específica como leída
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['ok' => false, 'error' => 'invalid_id']);
            exit;
        }
        
        $stmt = $conn->prepare("CALL sp_marcar_notificacion_leida(?, ?, @ok)");
        $stmt->bind_param("ii", $id, $usuario_id);
        $stmt->execute();
        $stmt->close();
        
        // Verificar resultado
        $result = $conn->query("SELECT @ok as ok");
        $ok = (bool)$result->fetch_assoc()['ok'];
        
        echo json_encode([
            'ok' => $ok,
            'message' => $ok ? 'Notificación marcada como leída' : 'No se pudo marcar la notificación'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'ok' => false, 
        'error' => 'database_error',
        'message' => $e->getMessage()
    ]);
}
?>