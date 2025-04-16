<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'email_service.php';

function enviarCorreoConfirmacion($email, $nombre_usuario, $tracking_number, $estimated_cost)
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
        $mail->addAddress($email, $nombre_usuario);

        $mail->isHTML(true);
        $mail->Subject = 'Confirmación de Envío #' . $tracking_number;

        // Construir el cuerpo del correo con un diseño profesional
        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; padding: 20px; background-color: #003366; color: white; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .footer { text-align: center; padding: 10px; font-size: 12px; color: #666; }
                .tracking { font-size: 24px; font-weight: bold; text-align: center; padding: 15px; 
                           background-color: #f1c40f; color: #003366; margin: 20px 0; }
                .details { background-color: white; padding: 15px; border-radius: 5px; margin-top: 20px; }
                .price { font-size: 20px; text-align: right; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>¡Gracias por tu envío!</h1>
                </div>
                <div class="content">
                    <p>Hola <strong>' . $nombre_usuario . '</strong>,</p>
                    <p>Hemos recibido tu solicitud de envío y la estamos procesando. A continuación encontrarás los detalles:</p>
                    
                    <div class="tracking">
                        Número de seguimiento: ' . $tracking_number . '
                    </div>
                    
                    <p>Puedes dar seguimiento a tu envío en cualquier momento ingresando este número en nuestra plataforma.</p>
                    
                    <div class="details">
                        <h3>Detalles del envío:</h3>
                        <p><strong>Fecha de registro:</strong> ' . date('d/m/Y H:i') . '</p>
                        <p><strong>Estado actual:</strong> Procesando</p>
                        <p><strong>Costo estimado:</strong> $' . number_format($estimated_cost, 2) . ' MXN</p>
                    </div>
                    
                    <div class="price">
                        Total: $' . number_format($estimated_cost, 2) . ' MXN
                    </div>
                    
                    <p>Si tienes alguna pregunta o necesitas ayuda, no dudes en contactarnos.</p>
                    
                    <p>Atentamente,<br>El equipo de MENDEZ</p>
                </div>
                <div class="footer">
                    &copy; ' . date('Y') . ' MENDEZ. Todos los derechos reservados.
                </div>
            </div>
        </body>
        </html>
        ';

        $mail->AltBody = "Hola $nombre_usuario, 
        
Gracias por tu envío. Hemos recibido tu solicitud y la estamos procesando.

Tu número de seguimiento es: $tracking_number

Detalles del envío:
- Fecha de registro: " . date('d/m/Y H:i') . "
- Estado actual: Procesando
- Costo estimado: $" . number_format($estimated_cost, 2) . " MXN

Si tienes alguna pregunta, contáctanos.

Atentamente,
El equipo de MENDEZ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo de confirmación: " . $mail->ErrorInfo);
        return "Error al enviar el correo de confirmación: {$mail->ErrorInfo}";
    }
}
