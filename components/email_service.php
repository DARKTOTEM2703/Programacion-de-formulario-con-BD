<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; 
include 'config.php'; 
try {
} catch (Dotenv\Exception\InvalidPathException $e) {
    die("Error: El archivo .env no se encuentra en la ruta especificada.");
} catch (Dotenv\Exception\InvalidFileException $e) {
    die("Error al cargar el archivo .env: " . $e->getMessage());
}
// Verificar que las variables de entorno se han cargado correctamente
if (!isset($_ENV['DB_SERVER']) || !isset($_ENV['SMTP_HOST'])) {
    die("Error: Variables de entorno no cargadas correctamente.");
}

function enviarCorreo($email, $nombre_usuario, $password = "")
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
        $mail->addAddress($email, $nombre_usuario); // Agregar el destinatario
        $mail->isHTML(true);

        // Configurar la codificación para evitar problemas con caracteres especiales
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        $mail->Subject = 'Confirmación de Registro - Bienvenido/a';

        // Crear un mensaje HTML profesional
        $siteName = $_ENV['SMTP_FROM_NAME'] ?? 'Nuestra plataforma';
        $siteUrl = "http://localhost/Programacion-de-formulario-con-BD/php/login.php";
        $resetPasswordUrl = "http://localhost/Programacion-de-formulario-con-BD/php/reset_password.php";
        $year = date('Y');

        // Plantilla HTML mejorada y profesional para el email
        $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Registro</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333333;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #003366;
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .content {
            padding: 30px;
        }
        .success-badge {
            background-color: #28a745;
            color: white;
            display: inline-block;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 14px;
            margin-bottom: 20px;
        }
        h2 {
            color: #003366;
            margin-top: 0;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #003366;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .password-box {
            background-color: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .button {
            display: inline-block;
            background-color: #003366;
            color: #ffffff !important;
            padding: 12px 24px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            margin: 20px 0;
        }
        .help-section {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .footer {
            background-color: #f5f5f5;
            padding: 20px 30px;
            text-align: center;
            color: #666666;
            font-size: 14px;
        }
        .social-icons {
            margin: 15px 0;
        }
        .social-icon {
            display: inline-block;
            margin: 0 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>¡Registro Exitoso!</h1>
        </div>
        
        <div class="content">
            <div class="success-badge">✓ Cuenta Creada</div>
            
            <h2>¡Felicidades, {$nombre_usuario}!</h2>
            
            <p>Tu cuenta ha sido creada exitosamente. A continuación, encontrarás la información de tu cuenta:</p>
            
            <div class="info-box">
                <p><strong>Nombre de usuario:</strong> {$nombre_usuario}</p>
                <p><strong>Correo electrónico:</strong> {$email}</p>
            </div>
            
            <div class="password-box">
                <p><strong>Contraseña:</strong> {$password}</p>
                <p style="color: #856404; font-size: 13px;">Guarda esta información en un lugar seguro. Por seguridad, te recomendamos cambiar esta contraseña después de tu primer inicio de sesión.</p>
            </div>
            
            <p>Te recomendamos guardar este correo como respaldo de tu información de acceso.</p>
            
            <div style="text-align: center;">
                <a href="{$siteUrl}" class="button">Iniciar Sesión</a>
            </div>
            
            <div class="help-section">
                <h3 style="color: #003366;">¿Olvidaste tu contraseña en el futuro?</h3>
                <p>Si en algún momento olvidas tu contraseña, siempre puedes:</p>
                <ol>
                    <li>Buscar este correo electrónico en tu bandeja de entrada</li>
                    <li>Usar la opción "Olvidé mi contraseña" en la página de inicio de sesión</li>
                    <li>Contactar con nuestro equipo de soporte</li>
                </ol>
            </div>
            
            <p>Si tienes alguna pregunta, no dudes en contactar a nuestro equipo de soporte.</p>
        </div>
        
        <div class="footer">
            <p>© {$year} {$siteName}. Todos los derechos reservados.</p>
            <p>Este es un mensaje automático, por favor no respondas a este correo.</p>
        </div>
    </div>
</body>
</html>
HTML;

        // Versión de texto plano para clientes que no soportan HTML
        $mail->AltBody = "¡Felicidades, {$nombre_usuario}!\n\n" .
            "Tu cuenta ha sido creada exitosamente.\n\n" .
            "Información de tu cuenta:\n" .
            "- Nombre de usuario: {$nombre_usuario}\n" .
            "- Correo electrónico: {$email}\n" .
            "- Contraseña: {$password}\n\n" .
            "Guarda esta información en un lugar seguro. Te recomendamos cambiar esta contraseña después de tu primer inicio de sesión.\n\n" .
            "Te recomendamos guardar este correo como respaldo de tu información de acceso.\n\n" .
            "Puedes iniciar sesión en: {$siteUrl}\n\n" .
            "¿Olvidaste tu contraseña en el futuro?\n" .
            "Si en algún momento olvidas tu contraseña, siempre puedes:\n" .
            "1. Buscar este correo electrónico en tu bandeja de entrada\n" .
            "2. Usar la opción \"Olvidé mi contraseña\" en la página de inicio de sesión\n" .
            "3. Contactar con nuestro equipo de soporte\n\n" .
            "Si tienes alguna pregunta, no dudes en contactar a nuestro equipo de soporte.\n\n" .
            "© {$year} {$siteName}. Todos los derechos reservados.";

        $mail->send();

        // Registrar en archivo log
        $logMsg = "[".date('Y-m-d H:i:s')."] CORREO REGISTRO enviado a: $email, usuario: $nombre_usuario\n";
        file_put_contents(__DIR__ . '/../registro.log', $logMsg, FILE_APPEND);

        return true;
    } catch (Exception $e) {
        // Registrar error en archivo log
        $logMsg = "[".date('Y-m-d H:i:s')."] ERROR CORREO REGISTRO a: $email, usuario: $nombre_usuario. Error: {$mail->ErrorInfo}\n";
        file_put_contents(__DIR__ . '/../registro.log', $logMsg, FILE_APPEND);

        return "Error al enviar el correo: {$mail->ErrorInfo}";
    }
}

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
        // Configuración SMTP (la misma que usas para otros correos)
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