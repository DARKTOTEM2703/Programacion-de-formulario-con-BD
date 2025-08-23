<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../config/load_env.php';
require_once 'email_service.php';

/**
 * Envía un correo de confirmación de envío, con QR y/o enlace de pago.
 */
function enviarCorreoEnvio($email, $nombre, $tracking_number, $costo, $qr_url = null, $payment_link = null, $attachment_path = null)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USERNAME'] ?? 'tu_correo@gmail.com';
        $mail->Password = $_ENV['SMTP_PASSWORD'] ?? 'tu_contraseña';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['SMTP_PORT'] ?? 587;
        $mail->setFrom($_ENV['SMTP_FROM_EMAIL'] ?? 'envios@mendez.com', $_ENV['SMTP_FROM_NAME'] ?? 'MENDEZ Transportes');
        $mail->addAddress($email, $nombre);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = "Confirmación de Envío #$tracking_number";

        // Adjuntar imagen si existe
        if ($attachment_path && file_exists($attachment_path)) {
            $mail->addAttachment($attachment_path, 'imagen_paquete_' . $tracking_number . '.' . pathinfo($attachment_path, PATHINFO_EXTENSION));
        }

        // Construir el cuerpo del correo
        $body = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; padding: 20px; background-color: #003366; color: white; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .footer { text-align: center; padding: 10px; font-size: 12px; color: #666; }
                .tracking { font-size: 24px; font-weight: bold; text-align: center; padding: 15px; background-color: #f1c40f; color: #003366; margin: 20px 0; }
                .details { background-color: white; padding: 15px; border-radius: 5px; margin-top: 20px; }
                .price { font-size: 20px; text-align: right; margin-top: 20px; }
                .btn-pagar { display: block; width: 200px; margin: 20px auto; background: #28a745; color: white; text-align: center; padding: 12px; text-decoration: none; font-weight: bold; border-radius: 4px; }
                .qr-container { text-align: center; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>¡Gracias por tu envío!</h1>
                </div>
                <div class="content">
                    <p>Hola <strong>' . htmlspecialchars($nombre) . '</strong>,</p>
                    <p>Hemos recibido tu solicitud de envío y la estamos procesando.</p>
                    <div class="tracking">
                        Número de seguimiento: ' . htmlspecialchars($tracking_number) . '
                    </div>
                    <div class="details">
                        <h3>Detalles del envío:</h3>
                        <p><strong>Fecha de registro:</strong> ' . date('d/m/Y H:i') . '</p>
                        <p><strong>Estado actual:</strong> ' . ($payment_link ? 'Pendiente de pago' : 'Procesando') . '</p>
                        <p><strong>Costo total:</strong> $' . number_format($costo, 2) . ' MXN</p>
                    </div>
                    <div class="price">
                        Total: $' . number_format($costo, 2) . ' MXN
                    </div>';

        // Mostrar QR si existe
        if ($qr_url) {
            $body .= '
                    <div class="qr-container">
                        <img src="' . $qr_url . '" alt="QR de seguimiento" style="max-width: 100%; height: auto;">
                        <p>Presenta este QR en la bodega para ingresar tu paquete.</p>
                    </div>';
        }

        // Mostrar botón de pago si existe
        if ($payment_link) {
            $body .= '
                    <p style="text-align: center; margin-top: 30px;">
                        <a href="' . $payment_link . '" class="btn-pagar">PAGAR AHORA</a>
                    </p>
                    <p>Una vez realizado el pago, procesaremos tu envío.</p>';
        }

        $body .= '
                    <p>Si tienes alguna pregunta o necesitas ayuda, no dudes en contactarnos.</p>
                    <p>Atentamente,<br>El equipo de MENDEZ</p>
                </div>
                <div class="footer">
                    &copy; ' . date('Y') . ' MENDEZ. Todos los derechos reservados.
                </div>
            </div>
        </body>
        </html>';

        $mail->Body = $body;

        // AltBody simplificado
        $mail->AltBody = "Hola $nombre,\n\n"
            . "Tu número de seguimiento es: $tracking_number\n"
            . ($payment_link ? "Estado: Pendiente de pago\nEnlace de pago: $payment_link\n" : "Estado: Procesando\n")
            . "Costo total: $" . number_format($costo, 2) . " MXN\n"
            . ($qr_url ? "Presenta el QR adjunto en la bodega.\n" : "")
            . "Atentamente, El equipo de MENDEZ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo de confirmación: " . $mail->ErrorInfo);
        return "Error al enviar el correo de confirmación: {$mail->ErrorInfo}";
    }
}