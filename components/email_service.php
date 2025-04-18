<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Asegúrate de tener instalado PHPMailer con Composer
include 'config.php'; // El archivo de configuración para reconocer el archivo .env
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
        return true;
    } catch (Exception $e) {
        return "Error al enviar el correo: {$mail->ErrorInfo}";
    }
}
