<?php
session_start();
require_once '../components/db_connection.php';
require_once 'components/cfdi_service.php';

// Verificar si el usuario está autenticado como administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../php/login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Obtener datos de la factura
$stmt = $conn->prepare("SELECT * FROM facturas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$factura = $stmt->get_result()->fetch_assoc();

if (!$factura) {
    $_SESSION['error'] = "Factura no encontrada";
    header("Location: facturas.php");
    exit();
}

// Verificar que la factura no esté ya cancelada
if ($factura['status'] === 'cancelado') {
    $_SESSION['error'] = "Esta factura ya ha sido cancelada previamente";
    header("Location: detalle_factura.php?id=" . $id);
    exit();
}

try {
    // Iniciar transacción
    $conn->begin_transaction();

    // Cancelar la factura en el SAT (simulado)
    $result = cancelarCFDI('uuid-simulado', 'Cancelación solicitada por el administrador');

    // Si no hubo éxito en la cancelación con el SAT, lanzar excepción
    if (!$result['success']) {
        throw new Exception("Error al cancelar el CFDI: " . $result['mensaje']);
    }

    // Actualizar el estado de la factura en la base de datos
    $stmt = $conn->prepare("UPDATE facturas SET status = 'cancelado' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // Registrar en movimientos contables la cancelación
    $stmt = $conn->prepare("
        INSERT INTO movimientos_contables (
            tipo, factura_id, concepto, monto, fecha_movimiento, 
            categoria, created_by
        ) VALUES ('egreso', ?, ?, ?, CURDATE(), 'cancelaciones', ?)
    ");

    $concepto = "Cancelación de factura: " . $factura['numero_factura'];
    $monto = $factura['monto']; // El monto que se cancela (negativo como egreso)

    $stmt->bind_param("isdi", $id, $concepto, $monto, $_SESSION['usuario_id']);
    $stmt->execute();

    // Confirmar transacción
    $conn->commit();

    $_SESSION['success'] = "Factura cancelada correctamente";
    header("Location: detalle_factura.php?id=" . $id);
    exit();
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollback();

    $_SESSION['error'] = "Error al cancelar la factura: " . $e->getMessage();
    header("Location: detalle_factura.php?id=" . $id);
    exit();
}
