<?php
// filepath: c:\xampp\htdocs\Programacion-de-formulario-con-BD\components\invoice_handler.php
require_once 'db_connection.php';
require_once 'email_service.php';

/**
 * Genera una factura cuando se completa un envío
 * @param int $envio_id ID del envío completado
 * @return string|bool Número de factura o false si falla
 */
function generateInvoice($envio_id)
{
    global $conn;

    // Obtener datos del envío
    $stmt = $conn->prepare("SELECT e.*, u.nombre_usuario, u.email, u.rfc FROM envios e
                           JOIN usuarios u ON e.usuario_id = u.id
                           WHERE e.id = ?");
    $stmt->bind_param("i", $envio_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $envio = $result->fetch_assoc();

    if (!$envio) {
        error_log("No se encontró el envío ID: $envio_id");
        return false;
    }

    // Generar número de factura único
    $invoice_number = 'MENDEZ-' . date('Ymd') . '-' . str_pad($envio_id, 4, '0', STR_PAD_LEFT);

    // Fecha de vencimiento (15 días)
    $fecha_vencimiento = date('Y-m-d', strtotime('+15 days'));

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Crear registro de factura
        $stmt = $conn->prepare("INSERT INTO facturas (
            envio_id, numero_factura, monto, status, fecha_vencimiento
        ) VALUES (?, ?, ?, 'pendiente', ?)");

        $stmt->bind_param("isds", $envio_id, $invoice_number, $envio['estimated_cost'], $fecha_vencimiento);
        $stmt->execute();

        $factura_id = $conn->insert_id;

        // Registrar movimiento contable
        $stmt = $conn->prepare("INSERT INTO movimientos_contables 
                               (tipo, factura_id, concepto, monto, fecha_movimiento, categoria, created_by) 
                               VALUES ('ingreso', ?, ?, ?, CURDATE(), 'Envío', ?)");

        $concepto = "Factura {$invoice_number} - Servicio de envío";
        $admin_id = 1; // Usuario administrador por defecto

        $stmt->bind_param("isdi", $factura_id, $concepto, $envio['estimated_cost'], $admin_id);
        $stmt->execute();

        // Confirmar transacción
        $conn->commit();

        // Generar PDF (placeholder - se implementará con librería FPDF)
        $pdf_path = generateInvoicePDF($factura_id);

        // Enviar factura por correo si hay email
        if (!empty($envio['email'])) {
            enviarFacturaEmail($envio['email'], $invoice_number, $pdf_path);
        }

        return $invoice_number;
    } catch (Exception $e) {
        // Revertir cambios en caso de error
        $conn->rollback();
        error_log("Error al generar factura: " . $e->getMessage());
        return false;
    }
}

/**
 * Genera el PDF de una factura
 * @param int $factura_id ID de la factura
 * @return string Ruta al PDF generado
 */
function generateInvoicePDF($factura_id)
{
    global $conn;

    // Crear directorio si no existe
    $upload_dir = '../uploads/facturas/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $filename = 'factura-' . $factura_id . '.pdf';
    $filepath = $upload_dir . $filename;

    // Aquí implementarías la generación real del PDF con FPDF o TCPDF
    // Por ahora es un placeholder

    return 'uploads/facturas/' . $filename;
}

/**
 * Marca una factura como pagada
 */
function markInvoiceAsPaid($factura_id, $metodo_pago, $referencia = '', $notas = '')
{
    global $conn;

    $stmt = $conn->prepare("UPDATE facturas SET 
                         status = 'pagado', 
                         metodo_pago = ?,
                         referencia_pago = ?,
                         fecha_pago = NOW(),
                         notas = ?
                         WHERE id = ?");

    $stmt->bind_param("sssi", $metodo_pago, $referencia, $notas, $factura_id);

    if ($stmt->execute()) {
        // Registrar en log de auditoría
        if (function_exists('auditLog')) {
            auditLog('payment', 'factura', $factura_id, "Factura marcada como pagada via $metodo_pago");
        }
        return true;
    }

    return false;
}

/**
 * Integración con SAT para timbrado de CFDI
 * Esta es una versión simplificada, necesitarás integrar un PAC real
 */
function generateCFDI($factura_id)
{
    global $conn;

    // Obtener datos de la factura
    $stmt = $conn->prepare("SELECT f.*, e.*, u.* 
                          FROM facturas f
                          JOIN envios e ON f.envio_id = e.id
                          JOIN usuarios u ON e.usuario_id = u.id
                          WHERE f.id = ?");
    $stmt->bind_param("i", $factura_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $factura = $result->fetch_assoc();

    if (!$factura) {
        return false;
    }

    // Aquí implementarías la conexión con un PAC para generar el CFDI
    // Por ejemplo con Facturama, SOLPAC, Finkok, etc.

    // Simulación de éxito
    $stmt = $conn->prepare("UPDATE facturas SET 
                         cfdi_xml = ?,
                         cfdi_pdf = ?
                         WHERE id = ?");

    $xml_path = 'xml_simulado';
    $pdf_path = 'pdf_simulado';

    $stmt->bind_param("ssi", $xml_path, $pdf_path, $factura_id);
    return $stmt->execute();
}