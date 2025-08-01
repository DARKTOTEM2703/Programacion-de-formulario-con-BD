<?php

/**
 * Controlador de Facturas para el sistema MENDEZ
 */

class FacturaController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Obtiene todas las facturas
     */
    public function obtenerFacturas($limit = 10)
    {
        $query = "
            SELECT f.id, f.numero_factura, f.fecha_emision, f.monto, f.status,
                   e.name as cliente_nombre, e.tracking_number
            FROM facturas f
            JOIN envios e ON f.envio_id = e.id
            ORDER BY f.fecha_emision DESC
            LIMIT ?
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $facturas = [];
        while ($row = $result->fetch_assoc()) {
            $facturas[] = $row;
        }

        return $facturas;
    }

    /**
     * Obtiene una factura por su ID
     */
    public function obtenerFacturaPorId($id)
    {
        $query = "
            SELECT f.*, e.name, e.email, e.tracking_number, e.origin, e.destination
            FROM facturas f
            JOIN envios e ON f.envio_id = e.id
            WHERE f.id = ?
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }

    /**
     * Genera una nueva factura
     */
    public function generarFactura($envio_id, $monto, $numero_factura = null)
    {
        // Si no se proporciona un número de factura, generar uno
        if ($numero_factura === null) {
            $numero_factura = 'FAC-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        }

        // Crear la factura en la base de datos
        $query = "
            INSERT INTO facturas (
                envio_id, numero_factura, fecha_emision, fecha_vencimiento, 
                monto, status
            ) VALUES (
                ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 
                ?, 'pendiente'
            )
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("isd", $envio_id, $numero_factura, $monto);

        if ($stmt->execute()) {
            $factura_id = $this->conn->insert_id;

            // Obtener datos del envío
            $query_envio = "SELECT * FROM envios WHERE id = ?";
            $stmt_envio = $this->conn->prepare($query_envio);
            $stmt_envio->bind_param("i", $envio_id);
            $stmt_envio->execute();
            $envio = $stmt_envio->get_result()->fetch_assoc();

            // Generar los documentos PDF y XML
            require_once 'cfdi_service.php';
            $resultado = generarCFDI($envio, $numero_factura);

            if ($resultado['success']) {
                // Actualizar la factura con las rutas de los archivos
                $query_update = "
                    UPDATE facturas 
                    SET pdf_path = ?, xml_path = ?
                    WHERE id = ?
                ";
                $stmt_update = $this->conn->prepare($query_update);
                $stmt_update->bind_param("ssi", $resultado['pdf_path'], $resultado['xml_path'], $factura_id);
                $stmt_update->execute();

                return [
                    'success' => true,
                    'factura_id' => $factura_id,
                    'numero_factura' => $numero_factura
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al generar documentos: ' . ($resultado['message'] ?? 'Error desconocido')
                ];
            }
        }

        return ['success' => false, 'message' => $stmt->error];
    }

    /**
     * Actualiza el estado de una factura
     */
    public function actualizarEstadoFactura($id, $status)
    {
        $query = "UPDATE facturas SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $status, $id);

        if ($stmt->execute()) {
            return ['success' => true];
        }

        return ['success' => false, 'message' => $stmt->error];
    }

    /**
     * Obtiene estadísticas de facturas
     */
    public function obtenerEstadisticas()
    {
        // Facturas pendientes
        $query_pendientes = "SELECT COUNT(*) as total FROM facturas WHERE status = 'pendiente'";
        $result_pendientes = $this->conn->query($query_pendientes);
        $facturas_pendientes = $result_pendientes->fetch_assoc()['total'];

        // Facturas pagadas este mes
        $query_pagadas = "
            SELECT COUNT(*) as total, SUM(monto) as monto_total 
            FROM facturas 
            WHERE status = 'pagado' 
            AND MONTH(fecha_pago) = MONTH(CURRENT_DATE()) 
            AND YEAR(fecha_pago) = YEAR(CURRENT_DATE())
        ";
        $result_pagadas = $this->conn->query($query_pagadas);
        $pagadas = $result_pagadas->fetch_assoc();

        return [
            'pendientes' => $facturas_pendientes,
            'pagadas_mes' => $pagadas['total'] ?? 0,
            'monto_mes' => $pagadas['monto_total'] ?? 0
        ];
    }
}
