<?php
session_start();
require_once '../components/db_connection.php';
require_once 'components/cfdi_service.php';

// Verificar si el usuario está autenticado como administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../php/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $envio_id = $_POST['envio_id'] ?? 0;

    if (empty($envio_id)) {
        $_SESSION['error'] = "El ID del envío es requerido";
        header("Location: facturas.php");
        exit();
    }

    try {
        // Iniciar transacción
        $conn->begin_transaction();

        // Obtener detalles del envío
        $stmt = $conn->prepare("SELECT * FROM envios WHERE id = ?");
        $stmt->bind_param("i", $envio_id);
        $stmt->execute();
        $envio = $stmt->get_result()->fetch_assoc();

        if (!$envio) {
            throw new Exception("Envío no encontrado");
        }

        // Verificar que no exista una factura para este envío
        $stmt = $conn->prepare("SELECT id FROM facturas WHERE envio_id = ?");
        $stmt->bind_param("i", $envio_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Ya existe una factura para este envío");
        }

        // Generar número de factura único
        $invoice_number = 'FAC-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));

        // Calcular fecha de vencimiento (30 días después)
        $fecha_vencimiento = date('Y-m-d H:i:s', strtotime('+30 days'));

        // Generar XML para CFDI
        $cfdi_result = generarCFDI($envio, $invoice_number);

        if (!$cfdi_result['success']) {
            throw new Exception("Error al generar el CFDI: " . $cfdi_result['message']);
        }

        // Guardar factura en la base de datos
        $stmt = $conn->prepare("
            INSERT INTO facturas (
                envio_id, numero_factura, fecha_emision, fecha_vencimiento, 
                monto, status, cfdi_xml, cfdi_pdf
            ) VALUES (?, ?, NOW(), ?, ?, 'pendiente', ?, ?)
        ");

        $costo = isset($envio['estimated_cost']) ? $envio['estimated_cost'] : 0;

        $stmt->bind_param(
            "issdss",
            $envio_id,
            $invoice_number,
            $fecha_vencimiento,
            $costo,
            $cfdi_result['xml_path'],
            $cfdi_result['pdf_path']
        );

        $stmt->execute();
        $factura_id = $conn->insert_id;

        // Crear registro en movimientos contables
        $stmt = $conn->prepare("
            INSERT INTO movimientos_contables (
                tipo, factura_id, concepto, monto, fecha_movimiento, 
                categoria, created_by
            ) VALUES ('ingreso', ?, ?, ?, CURDATE(), 'ventas', ?)
        ");

        $concepto = "Factura {$invoice_number} por servicio de envío";
        $stmt->bind_param("isdi", $factura_id, $concepto, $costo, $_SESSION['usuario_id']);
        $stmt->execute();

        // Confirmar transacción
        $conn->commit();

        // Enviar factura por correo electrónico (opcional)
        // Esto podría implementarse en un archivo separado

        $_SESSION['success'] = "Factura generada correctamente: {$invoice_number}";
        header("Location: facturas.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error al generar la factura: " . $e->getMessage();
        header("Location: facturas.php");
        exit();
    }
} else {
    header("Location: facturas.php");
    exit();
}
$stmt = $conn->prepare("CALL sp_generar_factura_envio(?, ?, @ok,@msg)");
$stmt->bind_param("ii",$envio_id,$_SESSION['usuario_id']);
$stmt->execute();
$r = $conn->query("SELECT @ok ok,@msg msg")->fetch_assoc();
if (!$r['ok']) {
  throw new Exception($r['msg']);
}
