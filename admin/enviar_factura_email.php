<?php
session_start();
require_once '../components/db_connection.php';
require_once 'components/email_facturas.php'; // Cambiado a email_facturas.php
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
    // Ruta completa del archivo PDF
    $pdf_path = '../' . $factura['cfdi_pdf'];

    // Enviar el correo usando PHPMailer
    $resultado = enviarFacturaEmail(
        $factura['cliente_email'],
        $factura['numero_factura'],
        $pdf_path
    );

    if ($resultado === true) {
        // Registrar el envío en la base de datos
        $stmt = $conn->prepare("
            INSERT INTO notificaciones (
                tipo, usuario_id, email, asunto, contenido, status, created_at
            ) VALUES ('factura_email', ?, ?, ?, ?, 'enviado', NOW())
        ");
        $asunto = 'Factura ' . $factura['numero_factura'] . ' - MENDEZ TRANSPORTES';
        $contenido = 'Factura enviada correctamente.';
        $stmt->bind_param(
            "isss",
            $_SESSION['usuario_id'],
            $factura['cliente_email'],
            $asunto,
            $contenido
        );
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Factura enviada correctamente a ' . $factura['cliente_email']]);
    } else {
        echo json_encode(['success' => false, 'message' => $resultado]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
