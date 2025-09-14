<?php
include_once 'email_service.php';
require_once __DIR__ . '/url_helper.php';

function notifyStatusChange($envio_id, $new_status)
{
    global $conn;

    // Obtener datos del envío y cliente
    $stmt = $conn->prepare("SELECT e.*, u.email, u.nombre_usuario, e.usuario_id FROM envios e 
                           JOIN usuarios u ON e.usuario_id = u.id 
                           WHERE e.id = ?");
    $stmt->bind_param("i", $envio_id);
    $stmt->execute();
    $envio = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$envio) return false;

    // Construir enlace dinámico para tracking
    $trackingUrl = rtrim(base_url(), '/') . '/php/tracking.php?tracking=' . urlencode($envio['tracking_number']);

    // Preparar contenido
    $subject = "Actualización de tu envío #" . $envio['tracking_number'];
    $message = "Tu envío #" . $envio['tracking_number'] . " ha sido actualizado a: " . $new_status;
    $link = $trackingUrl;
    $tipo = 'envio_status';
    $usuario_id = (int) $envio['usuario_id'];
    $email = $envio['email'] ?? null;

    // Insertar notificación en la base de datos
    $ins = $conn->prepare("INSERT INTO notificaciones (tipo, usuario_id, email, asunto, contenido, link, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pendiente', NOW())");
    $ins->bind_param("sissss", $tipo, $usuario_id, $email, $subject, $message, $link);
    $ok = $ins->execute();
    $ins->close();

    // Enviar notificación por email (si quieres mantener email)
    try {
        enviarCorreo($email, $envio['nombre_usuario'] ?? '', $subject, $message . "\n\n" . $link);
    } catch (Exception $e) {
        // no bloquear por email
    }

    return $ok;
}
?>
