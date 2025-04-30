<?php
session_start();
require_once '../components/db_connection.php';
header('Content-Type: application/json');

// Verificar si el usuario está autenticado como administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Obtener datos de la factura
$stmt = $conn->prepare("
    SELECT f.*, e.name AS cliente_nombre, e.email AS cliente_email, e.tracking_number
    FROM facturas f
    JOIN envios e ON f.envio_id = e.id
    WHERE f.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Factura no encontrada']);
    exit();
}

$factura = $result->fetch_assoc();

// Verificar que existan los archivos
if (empty($factura['cfdi_pdf']) || !file_exists('../' . $factura['cfdi_pdf'])) {
    echo json_encode(['success' => false, 'message' => 'El PDF de la factura no está disponible']);
    exit();
}

try {
    // En un entorno real, aquí usarías PHPMailer, Swift Mailer o la función mail() de PHP
    // Este es un ejemplo simulado
    
    // Preparar el mensaje
    $to = $factura['cliente_email'];
    $subject = 'Factura ' . $factura['numero_factura'] . ' - MENDEZ TRANSPORTES';
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #0057B8; color: white; padding: 10px 20px; text-align: center; }
            .content { padding: 20px; border: 1px solid #ddd; border-top: none; }
            .footer { font-size: 12px; text-align: center; margin-top: 20px; color: #777; }
            .button { display: inline-block; background-color: #0057B8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>MENDEZ TRANSPORTES</h2>
            </div>
            <div class='content'>
                <p>Estimado/a " . htmlspecialchars($factura['cliente_nombre']) . ",</p>
                <p>Adjunto encontrará la factura correspondiente al servicio de envío con número de tracking " . htmlspecialchars($factura['tracking_number']) . ".</p>
                <p><strong>Detalles:</strong></p>
                <ul>
                    <li>Número de factura: " . htmlspecialchars($factura['numero_factura']) . "</li>
                    <li>Fecha de emisión: " . date('d/m/Y', strtotime($factura['fecha_emision'])) . "</li>
                    <li>Monto: $" . number_format($factura['monto'], 2) . " MXN</li>
                    <li>Estado: " . ucfirst($factura['status']) . "</li>
                </ul>
                " . ($factura['status'] == 'pendiente' ? "<p>Esta factura vence el " . date('d/m/Y', strtotime($factura['fecha_vencimiento'])) . ".</p>" : "") . "
                <p>Si tiene alguna pregunta sobre esta factura, no dude en contactarnos.</p>
                <p>Atentamente,</p>
                <p>Equipo MENDEZ TRANSPORTES</p>
            </div>
            <div class='footer'>
                <p>Este correo electrónico fue enviado automáticamente. Por favor no responda a este mensaje.</p>
                <p>© " . date('Y') . " MENDEZ TRANSPORTES. Todos los derechos reservados.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Registrar el envío en la base de datos
    $stmt = $conn->prepare("
        INSERT INTO notificaciones (
            tipo, usuario_id, email, asunto, contenido, status, created_at
        ) VALUES ('factura_email', ?, ?, ?, ?, 'enviado', NOW())
    ");
    
    $stmt->bind_param(
        "isss", 
        $_SESSION['usuario_id'], 
        $to, 
        $subject, 
        $message
    );
    
    $stmt->execute();
    
    // En un entorno real, aquí se enviaría el correo
    // Para este ejemplo, simulamos éxito
    $success = true;
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Factura enviada correctamente a ' . $factura['cliente_email']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al enviar el correo']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>