<?php
session_start();
require_once '../components/db_connection.php';
require_once 'components/factura_controller.php';

// Verificar autenticación del administrador
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$factura_controller = new FacturaController($conn);

// Acción para generar nueva factura
if (isset($_POST['accion']) && $_POST['accion'] === 'generar') {
    $envio_id = $_POST['envio_id'] ?? 0;
    $monto = $_POST['monto'] ?? 0;
    
    $resultado = $factura_controller->generarFactura($envio_id, $monto);
    
    if ($resultado['success']) {
        $_SESSION['mensaje'] = "Factura generada correctamente: " . $resultado['numero_factura'];
    } else {
        $_SESSION['error'] = "Error al generar factura: " . ($resultado['message'] ?? 'Error desconocido');
    }
}

// Acción para actualizar estado
if (isset($_POST['accion']) && $_POST['accion'] === 'actualizar_estado') {
    $factura_id = $_POST['factura_id'] ?? 0;
    $nuevo_estado = $_POST['nuevo_estado'] ?? '';
    
    $resultado = $factura_controller->actualizarEstadoFactura($factura_id, $nuevo_estado);
    
    if ($resultado['success']) {
        $_SESSION['mensaje'] = "Estado actualizado correctamente";
    } else {
        $_SESSION['error'] = "Error al actualizar estado: " . ($resultado['message'] ?? 'Error desconocido');
    }
}

// Obtener listado de facturas
$facturas = $factura_controller->obtenerFacturas();

// Obtener envíos sin factura para el formulario de creación
$query_envios = "
    SELECT e.id, e.tracking_number, e.name, e.estimated_cost, e.fecha_pago
    FROM envios e
    LEFT JOIN facturas f ON e.id = f.envio_id
    WHERE e.estado_pago = 'pagado' 
    AND f.id IS NULL
    ORDER BY e.fecha_pago DESC
";
$result_envios = $conn->query($query_envios);
$envios_sin_factura = [];
while ($row = $result_envios->fetch_assoc()) {
    $envios_sin_factura[] = $row;
}

// Incluir la plantilla de encabezado
$titulo = "Gestión de Facturas | MENDEZ";
include 'components/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'components/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-receipt me-2"></i>Gestión de Facturas</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaFacturaModal">
                    <i class="bi bi-plus-circle me-1"></i> Nueva Factura
                </button>
            </div>
            
            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            <?php endif; ?>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Facturas Emitidas</h6>
                    <div class="input-group" style="width: 300px;">
                        <input type="text" class="form-control" id="buscarFactura" placeholder="Buscar factura...">
                        <button class="btn btn-outline-secondary" type="button">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($facturas)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i> No hay facturas registradas.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nº Factura</th>
                                        <th>Cliente</th>
                                        <th>Tracking</th>
                                        <th>Fecha</th>
                                        <th>Monto</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($facturas as $factura): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($factura['numero_factura']); ?></td>
                                        <td><?php echo htmlspecialchars($factura['cliente_nombre']); ?></td>
                                        <td>
                                            <span class="badge bg-info text-dark">
                                                <?php echo htmlspecialchars($factura['tracking_number']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($factura['fecha_emision'])); ?></td>
                                        <td>$<?php echo number_format($factura['monto'], 2); ?></td>
                                        <td>
                                            <?php if ($factura['status'] == 'pendiente'): ?>
                                                <span class="badge bg-warning text-dark">Pendiente</span>
                                            <?php elseif ($factura['status'] == 'pagado'): ?>
                                                <span class="badge bg-success">Pagado</span>
                                            <?php elseif ($factura['status'] == 'cancelado'): ?>
                                                <span class="badge bg-danger">Cancelado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="detalle_factura.php?id=<?php echo $factura['id']; ?>" class="btn btn-info">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="../uploads/facturas/<?php echo $factura['numero_factura']; ?>.pdf" target="_blank" class="btn btn-primary">
                                                    <i class="bi bi-file-pdf"></i>
                                                </a>
                                                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="enviar_factura_email.php?id=<?php echo $factura['id']; ?>">
                                                            <i class="bi bi-envelope me-2"></i> Enviar por email
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="../uploads/facturas/<?php echo $factura['numero_factura']; ?>.xml" download>
                                                            <i class="bi bi-file-earmark-code me-2"></i> Descargar XML
                                                        </a>
                                                    </li>
                                                    <?php if ($factura['status'] == 'pendiente'): ?>
                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>
                                                    <li>
                                                        <form action="" method="post">
                                                            <input type="hidden" name="accion" value="actualizar_estado">
                                                            <input type="hidden" name="factura_id" value="<?php echo $factura['id']; ?>">
                                                            <input type="hidden" name="nuevo_estado" value="pagado">
                                                            <button type="submit" class="dropdown-item text-success">
                                                                <i class="bi bi-check-circle me-2"></i> Marcar como pagada
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form action="" method="post">
                                                            <input type="hidden" name="accion" value="actualizar_estado">
                                                            <input type="hidden" name="factura_id" value="<?php echo $factura['id']; ?>">
                                                            <input type="hidden" name="nuevo_estado" value="cancelado">
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="bi bi-x-circle me-2"></i> Cancelar factura
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal para nueva factura -->
<div class="modal fade" id="nuevaFacturaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-receipt me-2"></i>Generar Nueva Factura</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="post">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="generar">
                    
                    <div class="mb-3">
                        <label for="envio_id" class="form-label">Seleccionar Envío</label>
                        <select class="form-select" id="envio_id" name="envio_id" required>
                            <option value="">-- Seleccione un envío --</option>
                            <?php foreach ($envios_sin_factura as $envio): ?>
                            <option value="<?php echo $envio['id']; ?>" data-monto="<?php echo $envio['estimated_cost']; ?>">
                                <?php echo $envio['tracking_number'] . ' - ' . $envio['name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="monto" class="form-label">Monto ($)</label>
                        <input type="number" class="form-control" id="monto" name="monto" step="0.01" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Generar Factura</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Autocompletar monto al seleccionar envío
document.getElementById('envio_id').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    if (option && option.dataset.monto) {
        document.getElementById('monto').value = option.dataset.monto;
    } else {
        document.getElementById('monto').value = '';
    }
});

// Filtro de búsqueda para la tabla
document.getElementById('buscarFactura').addEventListener('keyup', function() {
    const texto = this.value.toLowerCase();
    const tabla = document.querySelector('table');
    const filas = tabla.getElementsByTagName('tr');
    
    for (let i = 1; i < filas.length; i++) { // Empezar desde 1 para omitir el encabezado
        const celdas = filas[i].getElementsByTagName('td');
        let mostrar = false;
        
        for (let j = 0; j < celdas.length; j++) {
            if (celdas[j].textContent.toLowerCase().indexOf(texto) > -1) {
                mostrar = true;
                break;
            }
        }
        
        filas[i].style.display = mostrar ? '' : 'none';
    }
});
</script>

<?php include 'components/footer.php'; ?>