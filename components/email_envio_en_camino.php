<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../config/load_env.php';

function enviarCorreoEnvioEnCamino($email, $nombre, $tracking_number, $pin_seguro)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USERNAME'];
        $mail->Password = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['SMTP_PORT'];
        $mail->setFrom($_ENV['SMTP_FROM_EMAIL'], $_ENV['SMTP_FROM_NAME']);
        $mail->addAddress($email, $nombre);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = "¡Tu paquete está en camino! - MENDEZ";

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
                .pin { font-size: 28px; font-weight: bold; text-align: center; color: #D32F2F; margin: 20px 0; }
                .highlight { font-size: 18px; font-weight: bold; color: #0057B8; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>¡Tu paquete está en camino!</h1>
                </div>
                <div class="content">
                    <p>Hola <strong>' . htmlspecialchars($nombre) . '</strong>,</p>
                    <p>Nos complace informarte que tu envío con número de seguimiento <span class="highlight">' . htmlspecialchars($tracking_number) . '</span> ha salido de la bodega y está en camino a su destino.</p>
                    <div class="tracking">
                        Número de seguimiento: ' . htmlspecialchars($tracking_number) . '
                    </div>
                    <p>Para recibir tu paquete, deberás proporcionar el siguiente <strong>PIN SEGURO</strong> al repartidor:</p>
                    <div class="pin">
                        ' . htmlspecialchars($pin_seguro) . '
                    </div>
                    <p>Gracias por confiar en MENDEZ. ¡Esperamos que disfrutes de nuestro servicio!</p>
                </div>
                <div class="footer">
                    &copy; ' . date('Y') . ' MENDEZ. Todos los derechos reservados.
                </div>
            </div>
        </body>
        </html>';

        $mail->Body = $body;
        $mail->AltBody = "Hola $nombre,\n\nTu envío #$tracking_number está en camino.\nPara recibir tu paquete, proporciona el siguiente PIN SEGURO al repartidor: $pin_seguro.\nGracias por confiar en MENDEZ.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo de envío en camino: " . $mail->ErrorInfo);
        return "Error al enviar el correo: {$mail->ErrorInfo}";
    }
}