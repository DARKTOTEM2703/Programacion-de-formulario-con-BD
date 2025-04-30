<?php

/**
 * Servicio para generar facturas electrónicas CFDI con integración al SAT
 */

/**
 * Genera un CFDI (Comprobante Fiscal Digital por Internet)
 * 
 * @param array $envio Datos del envío
 * @param string $invoice_number Número de factura
 * @return array Resultado de la operación
 */
function generarCFDI($envio, $invoice_number)
{
    // Nota: Esta es una implementación de demostración.
    // Para producción, se debe integrar con un PAC (Proveedor Autorizado de Certificación)
    try {
        // Directorio para almacenar los archivos generados
        $upload_dir = '../uploads/facturas/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Generar nombres de archivos
        $xml_filename = $invoice_number . '.xml';
        $pdf_filename = $invoice_number . '.pdf';
        $xml_path = 'uploads/facturas/' . $xml_filename;
        $pdf_path = 'uploads/facturas/' . $pdf_filename;
        $absolute_xml_path = $upload_dir . $xml_filename;
        $absolute_pdf_path = $upload_dir . $pdf_filename;

        // En un entorno real, aquí se realizaría la integración con un PAC
        // En este ejemplo, simplemente generamos archivos de prueba

        // Generar XML de prueba
        $xml_content = '<?xml version="1.0" encoding="UTF-8"?>
<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    Version="4.0" Serie="A" Folio="' . $invoice_number . '" Fecha="' . date('Y-m-d\TH:i:s') . '" FormaPago="99"
    NoCertificado="00000000000000000000" Certificado="" SubTotal="' . $envio['estimated_cost'] . '" Moneda="MXN"
    Total="' . $envio['estimated_cost'] . '" TipoDeComprobante="I" MetodoPago="PUE" LugarExpedicion="97000">
    <cfdi:Emisor Rfc="XAXX010101000" Nombre="MENDEZ TRANSPORTES SA DE CV" RegimenFiscal="601" />
    <cfdi:Receptor Rfc="XEXX010101000" Nombre="' . $envio['name'] . '" DomicilioFiscalReceptor="97000"
        RegimenFiscalReceptor="616" UsoCFDI="G03" />
    <cfdi:Conceptos>
        <cfdi:Concepto ClaveProdServ="78102200" Cantidad="1" ClaveUnidad="E48" Unidad="Servicio"
            Descripcion="Servicio de transporte de paquetería" ValorUnitario="' . $envio['estimated_cost'] . '"
            Importe="' . $envio['estimated_cost'] . '">
            <cfdi:Impuestos>
                <cfdi:Traslados>
                    <cfdi:Traslado Base="' . $envio['estimated_cost'] . '" Impuesto="002" TipoFactor="Tasa"
                        TasaOCuota="0.160000" Importe="' . ($envio['estimated_cost'] * 0.16) . '" />
                </cfdi:Traslados>
            </cfdi:Impuestos>
        </cfdi:Concepto>
    </cfdi:Conceptos>
    <cfdi:Impuestos TotalImpuestosTrasladados="' . ($envio['estimated_cost'] * 0.16) . '">
        <cfdi:Traslados>
            <cfdi:Traslado Base="' . $envio['estimated_cost'] . '" Impuesto="002" TipoFactor="Tasa"
                TasaOCuota="0.160000" Importe="' . ($envio['estimated_cost'] * 0.16) . '" />
        </cfdi:Traslados>
    </cfdi:Impuestos>
</cfdi:Comprobante>';

        file_put_contents($absolute_xml_path, $xml_content);

        // Generar PDF (simulado)
        // En un entorno real, usarías DOMPDF, TCPDF, mPDF, etc.
        $pdf_content = '<html>

<head>
    <style>
    body {
        font-family: Arial, sans-serif;
    }

    h1 {
        color: #0057B8;
    }

    .invoice-box {
        max-width: 800px;
        margin: auto;
        padding: 30px;
        border: 1px solid #eee;
        box-shadow: 0 0 10px rgba(0, 0, 0, .15);
    }

    .invoice-box table {
        width: 100%;
        line-height: 1.5;
    }

    .invoice-box table td {
        padding: 5px;
        vertical-align: top;
    }

    .invoice-box table tr.top td {
        padding-bottom: 20px;
    }

    .invoice-box table tr.top .title {
        font-size: 35px;
        color: #0057B8;
    }

    .invoice-box table tr.information td {
        padding-bottom: 40px;
    }

    .invoice-box table tr.heading td {
        background: #eee;
        border-bottom: 1px solid #ddd;
        font-weight: bold;
    }

    .invoice-box table tr.details td {
        padding-bottom: 20px;
    }

    .invoice-box table tr.item td {
        border-bottom: 1px solid #eee;
    }

    .invoice-box table tr.total td:last-child {
        border-top: 2px solid #eee;
        font-weight: bold;
    }
    </style>
</head>

<body>
    <div class="invoice-box">
        <table cellpadding="0" cellspacing="0">
            <tr class="top">
                <td colspan="2">
                    <table>
                        <tr>
                            <td class="title">MENDEZ TRANSPORTES</td>
                            <td>
                                Factura #: ' . $invoice_number . '<br>
                                Fecha: ' . date('d/m/Y') . '<br>
                                Vencimiento: ' . date('d/m/Y', strtotime('+30 days')) . '
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr class="information">
                <td colspan="2">
                    <table>
                        <tr>
                            <td>
                                MENDEZ TRANSPORTES S.A. DE C.V.<br>
                                Calle 55A #357<br>
                                Mérida, Yucatán 97236<br>
                                RFC: XAXX010101000
                            </td>
                            <td>
                                ' . $envio['name'] . '<br>
                                ' . $envio['email'] . '<br>
                                ' . $envio['phone'] . '<br>
                                ' . $envio['destination'] . '
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr class="heading">
                <td>Concepto</td>
                <td>Precio</td>
            </tr>
            <tr class="item">
                <td>Servicio de transporte de paquetería<br>
                    <small>Tracking: ' . $envio['tracking_number'] . '</small>
                </td>
                <td>$' . number_format($envio['estimated_cost'], 2) . '</td>
            </tr>
            <tr class="total">
                <td></td>
                <td>Total: $' . number_format($envio['estimated_cost'], 2) . ' MXN</td>
            </tr>
        </table>
        <div style="margin-top: 30px; font-size: 12px; text-align: center;">
            <p>Esta factura es un Comprobante Fiscal Digital por Internet (CFDI)</p>
            <p>Puede verificar la autenticidad de este documento en: https://verificacfdi.facturaelectronica.sat.gob.mx/
            </p>
        </div>
    </div>
</body>

</html>';

        file_put_contents($absolute_pdf_path, $pdf_content);

        return [
            'success' => true,
            'xml_path' => $xml_path,
            'pdf_path' => $pdf_path
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Verifica el estado de un CFDI con el SAT
 *
 * @param string $uuid UUID del CFDI
 * @return array Estado del CFDI
 */
function verificarEstadoCFDI($uuid)
{
    // En un entorno real, se consultaría el servicio web del SAT
    // Este es un ejemplo de implementación simulada
    return [
        'success' => true,
        'estado' => 'Vigente',
        'fecha_consulta' => date('Y-m-d H:i:s')
    ];
}

/**
 * Cancela un CFDI en el SAT
 *
 * @param string $uuid UUID del CFDI
 * @param string $motivo Motivo de cancelación
 * @return array Resultado de la operación
 */
function cancelarCFDI($uuid, $motivo)
{
    // En un entorno real, se enviaría la solicitud al SAT
    // Este es un ejemplo de implementación simulada
    return [
        'success' => true,
        'mensaje' => 'CFDI cancelado correctamente',
        'fecha_cancelacion' => date('Y-m-d H:i:s')
    ];
}
