<?php
session_start();
header('Content-Type: application/json');
require_once '../../components/db_connection.php';

// Verificar autenticación
if (!isset($_SESSION['repartidor_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

$repartidor_id = $_SESSION['repartidor_id'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'entregas_hoy':
        // Obtener cantidad de entregas de hoy
        $today = date('Y-m-d');
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total FROM envios e 
            JOIN repartidores_envios re ON e.id = re.envio_id 
            WHERE re.usuario_id = ? AND e.status = 'Entregado' 
            AND DATE(e.updated_at) = ?
        ");
        $stmt->bind_param("is", $repartidor_id, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        echo json_encode([
            'success' => true,
            'count' => $row['total']
        ]);
        break;

    case 'actualizar_estado':
        // Asegurarse de que se reciben los datos necesarios
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['envio_id']) || !isset($data['status'])) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            exit();
        }

        $envio_id = $data['envio_id'];
        $status = $data['status'];
        $notes = isset($data['notes']) ? $data['notes'] : '';

        // Verificar que el envío esté asignado a este repartidor
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count FROM repartidores_envios 
            WHERE usuario_id = ? AND envio_id = ?
        ");
        $stmt->bind_param("ii", $repartidor_id, $envio_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] == 0) {
            echo json_encode(['success' => false, 'error' => 'Envío no asignado a este repartidor']);
            exit();
        }

        // Obtener la ubicación actual (en una app real vendrían las coordenadas GPS)
        $location = isset($data['location']) ? $data['location'] : '';

        // Iniciar transacción
        $conn->begin_transaction();

        try {
            // Actualizar estado del envío
            $stmt = $conn->prepare("UPDATE envios SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $envio_id);
            $stmt->execute();

            // Registrar en el historial de tracking
            $stmt = $conn->prepare("
                INSERT INTO tracking_history (envio_id, status, location, notes, created_by) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("isssi", $envio_id, $status, $location, $notes, $repartidor_id);
            $stmt->execute();

            $conn->commit();

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Acción no reconocida']);
}
