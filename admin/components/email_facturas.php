<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Asegúrate de tener instalado PHPMailer con Composer
include 'config.php'; // El archivo de configuración para reconocer el archivo .env

/**
 * Envía una factura por correo electrónico usando PHPMailer
 * 
 * @param string $email Correo del destinatario
 * @param string $invoice_number Número de factura
 * @param string $pdf_path Ruta al archivo PDF de la factura
 * @return boolean|string true si se envió correctamente, mensaje de error si falló
 */
function enviarFacturaEmail($email, $invoice_number, $pdf_path)
{
    $mail = new PHPMailer(true);

    try {
        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USERNAME'];
        $mail->Password = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['SMTP_PORT'];
        $mail->setFrom($_ENV['SMTP_FROM_EMAIL'], $_ENV['SMTP_FROM_NAME']);

        // Destinatario
        $mail->addAddress($email);

        // Configuración HTML y codificación
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        // Asunto del correo
        $mail->Subject = "MENDEZ Transportes - Factura #$invoice_number";

        // Contenido HTML
        $year = date('Y');
        $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
        .container { width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; }
        .header { background-color: #003366; color: white; padding: 20px; text-align: center; }
        .content { padding: 25px; border: 1px solid #ddd; }
        .footer { font-size: 12px; text-align: center; margin-top: 20px; color: #777; padding: 15px; background-color: #f8f9fa; }
        .invoice-number { font-size: 22px; font-weight: bold; background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 15px 0; text-align: center; color: #003366; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>Factura de MENDEZ Transportes</h2>
        </div>
        <div class='content'>
            <p>Estimado cliente,</p>
            
            <div class="invoice-number">
                Factura: {$invoice_number}
            </div>
            
            <p>Adjunto encontrará su factura por el servicio de transporte contratado.</p>
            <p>Para cualquier consulta relacionada con esta factura, por favor contáctenos respondiendo a este correo o llamando a nuestro teléfono de atención al cliente: <strong>(999) 123-4567</strong>.</p>
            <p>¡Gracias por confiar en MENDEZ Transportes!</p>
        </div>
        <div class='footer'>
            <p>Este correo fue enviado automáticamente, por favor no responda directamente.</p>
            <p>MENDEZ Transportes &copy; {$year} - Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
HTML;

        // Versión texto plano
        $mail->AltBody = "Estimado cliente,\n\n" .
            "Adjunto encontrará su factura #{$invoice_number} por el servicio de transporte contratado.\n\n" .
            "Para cualquier consulta, contáctenos.\n\n" .
            "¡Gracias por confiar en MENDEZ Transportes!\n\n" .
            "MENDEZ Transportes - {$year}";

        // Adjuntar el PDF
        $full_path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $pdf_path), '\\/');
        if (file_exists($full_path)) {
            $mail->addAttachment($full_path, "Factura-{$invoice_number}.pdf");
        } else {
            error_log("Error: PDF de factura no encontrado en: {$full_path}");
            return "Error: Archivo de factura no encontrado";
        }

        // Enviar el correo
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar factura por email: " . $mail->ErrorInfo);
        return "Error al enviar la factura: {$mail->ErrorInfo}";
    }
}
