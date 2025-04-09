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

function enviarCorreo($email, $nombre_usuario)
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
        $mail->Subject = 'Confirmación de Registro';
        $mail->Body = "<h1>Hola, $nombre_usuario!</h1><p>Gracias por registrarte. Tu cuenta ha sido creada exitosamente.</p>";
        $mail->AltBody = "Hola, $nombre_usuario! Gracias por registrarte. Tu cuenta ha sido creada exitosamente.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Error al enviar el correo: {$mail->ErrorInfo}";
    }
}
