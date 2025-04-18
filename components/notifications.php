<?php
include_once 'email_service.php';

function notifyStatusChange($envio_id, $new_status)
{
    global $conn;

    // Obtener datos del envío y cliente
    $stmt = $conn->prepare("SELECT e.*, u.email, u.nombre_usuario FROM envios e 
                           JOIN usuarios u ON e.usuario_id = u.id 
                           WHERE e.id = ?");
    $stmt->bind_param("i", $envio_id);
    $stmt->execute();
    $envio = $stmt->get_result()->fetch_assoc();

    // Enviar notificación por email
    $subject = "Actualización de tu envío #" . $envio['tracking_number'];
    $message = "Hola " . $envio['nombre_usuario'] . ",\n\n";
    $message .= "Tu envío #" . $envio['tracking_number'] . " ha sido actualizado a: " . $new_status . "\n\n";
    $message .= "Puedes seguir tu envío en: http://tudominio.com/tracking.php?tracking=" . $envio['tracking_number'];

    return enviarCorreo($envio['email'], $envio['nombre_usuario'], $subject, $message);
}
