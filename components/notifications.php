<?php
include_once 'email_service.php';
require_once __DIR__ . '/url_helper.php';

function notifyStatusChange($envio_id, $new_status, $custom_message = null)
{
    global $conn;
    
    try {
        // Usar el procedimiento almacenado en lugar de consultas directas
        $stmt = $conn->prepare("CALL sp_notificar_cambio_estado(?, ?, ?, @p_ok, @p_notif_id)");
        $stmt->bind_param("iss", $envio_id, $new_status, $custom_message);
        $stmt->execute();
        $stmt->close();
        
        // Obtener resultados
        $result = $conn->query("SELECT @p_ok AS ok, @p_notif_id AS notif_id");
        $data = $result->fetch_assoc();
        
        if (!$data['ok']) {
            return false;
        }
        
        // Enviar email (opcional) - usar SP para obtener datos
        try {
            $stmt = $conn->prepare("
                SELECT e.tracking_number, u.email, u.nombre_usuario 
                FROM envios e 
                JOIN usuarios u ON e.usuario_id = u.id 
                WHERE e.id = ?
            ");
            $stmt->bind_param("i", $envio_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $envio = $result->fetch_assoc();
            $stmt->close();
            
            if ($envio && !empty($envio['email'])) {
                // Construir enlace dinámico para tracking usando base_url()
                $trackingUrl = rtrim(base_url(), '/') . '/php/tracking.php?tracking=' . urlencode($envio['tracking_number']);
                
                $subject = "📦 Actualización del envío #" . $envio['tracking_number'];
                
                // Mensaje personalizado o predeterminado según estado
                $status_messages = [
                    'Procesando' => 'Tu envío ha sido recibido y está siendo procesado.',
                    'Recibido bodega' => 'Tu envío ha llegado a nuestra bodega y está listo para ser despachado.',
                    'En tránsito' => '¡Tu envío está en camino! El repartidor ya salió con tu paquete.',
                    'En ruta' => 'Tu envío está muy cerca. El repartidor está en la zona de entrega.',
                    'Intentado entregar' => 'Intentamos entregar tu paquete pero no te encontramos. Te contactaremos pronto.',
                    'Entregado' => '¡Felicidades! Tu envío ha sido entregado exitosamente.',
                    'Cancelado' => 'Tu envío ha sido cancelado. Si tienes dudas, contáctanos.'
                ];
                
                $message = $custom_message ?? ($status_messages[$new_status] ?? "Tu envío cambió a: $new_status");
                $full_message = $message . "\n\nRastrear: " . $trackingUrl;
                
                // Enviar correo
                enviarCorreo($envio['email'], $envio['nombre_usuario'], $subject, $full_message);
            }
        } catch (Exception $e) {
            error_log("Error enviando email de notificación: " . $e->getMessage());
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error en notifyStatusChange: " . $e->getMessage());
        return false;
    }
}

function notifyDeliveryUpdate($tracking_number, $status, $location = null, $repartidor_name = null)
{
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT id FROM envios WHERE tracking_number = ?");
        $stmt->bind_param("s", $tracking_number);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $envio_id = $result->fetch_assoc()['id'];
        $stmt->close();
        
        // Crear mensaje personalizado si hay repartidor o ubicación
        $custom_message = null;
        if ($status === 'En tránsito' && $repartidor_name) {
            $custom_message = "¡Tu envío está en camino! El repartidor {$repartidor_name} ya salió con tu paquete.";
            if ($location) {
                $custom_message .= " Ubicación: {$location}";
            }
        }
        
        return notifyStatusChange($envio_id, $status, $custom_message);
    } catch (Exception $e) {
        error_log("Error en notifyDeliveryUpdate: " . $e->getMessage());
        return false;
    }
}

// Nueva función para obtener estadísticas de notificaciones 
function getNotificationStats($usuario_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("CALL sp_estadisticas_notificaciones(?)");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'total' => 0,
                'pendientes' => 0,
                'leidas' => 0,
                'ultima_fecha' => null
            ];
        }
        
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error obteniendo estadísticas de notificaciones: " . $e->getMessage());
        return [
            'total' => 0,
            'pendientes' => 0,
            'leidas' => 0,
            'ultima_fecha' => null
        ];
    }
}
?>
