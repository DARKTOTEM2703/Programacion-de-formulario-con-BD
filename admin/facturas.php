<?php
session_start();
require_once '../components/db_connection.php';

// Verificar si el usuario está autenticado como administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../php/login.php");
    exit();
}

// Verificar si la tabla facturas existe
$result = $conn->query("SHOW TABLES LIKE 'facturas'");
if ($result->num_rows == 0) {
    // Crear la tabla facturas si no existe
    $sql = "CREATE TABLE facturas (
        id INT(11) NOT NULL AUTO_INCREMENT,
        numero_factura VARCHAR(50) NOT NULL,
        envio_id INT(11) NOT NULL,
        fecha_emision DATETIME DEFAULT CURRENT_TIMESTAMP,
        fecha_vencimiento DATETIME,
        monto DECIMAL(10,2) NOT NULL,
        status ENUM('pendiente', 'pagado', 'cancelado', 'vencido') DEFAULT 'pendiente',
        fecha_pago DATETIME NULL,
        cfdi_xml VARCHAR(255),
        cfdi_pdf VARCHAR(255),
        PRIMARY KEY (id),
        FOREIGN KEY (envio_id) REFERENCES envios(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $conn->query($sql);

    // Crear tabla movimientos_contables si no existe
    $sql = "CREATE TABLE movimientos_contables (
        id INT(11) NOT NULL AUTO_INCREMENT,
        tipo ENUM('ingreso', 'egreso') NOT NULL,
        factura_id INT(11) NULL,
        concepto VARCHAR(255) NOT NULL,
        monto DECIMAL(10,2) NOT NULL,
        fecha_movimiento DATE NOT NULL,
        categoria VARCHAR(50) DEFAULT 'otros',
        created_by INT(11) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $conn->query($sql);
}

// Paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filtros
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Construir consulta
$query = "SELECT f.*, e.tracking_number, e.name AS cliente_nombre, 
          u.nombre_usuario AS usuario_nombre
          FROM facturas f 
          LEFT JOIN envios e ON f.envio_id = e.id
          LEFT JOIN usuarios u ON e.usuario_id = u.id
          WHERE 1=1";

$count_query = "SELECT COUNT(*) as total FROM facturas f 
               LEFT JOIN envios e ON f.envio_id = e.id
               WHERE 1=1";

// Aplicar filtros
if ($status != 'all') {
    $query .= " AND f.status = '$status'";
    $count_query .= " AND f.status = '$status'";
}

if (!empty($search)) {
    $search = $conn->real_escape_string("%$search%");
    $query .= " AND (f.numero_factura LIKE '$search' OR e.tracking_number LIKE '$search' OR e.name LIKE '$search')";
    $count_query .= " AND (f.numero_factura LIKE '$search' OR e.tracking_number LIKE '$search' OR e.name LIKE '$search')";
}

// Ordenar por fecha de emisión más reciente
$query .= " ORDER BY f.fecha_emision DESC LIMIT $offset, $limit";

// Ejecutar la consulta
$result = $conn->query($query);
$facturas = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $facturas[] = $row;
    }
}

// Obtener el conteo total para paginación
$result = $conn->query($count_query);
$total_records = $result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Facturas - MENDEZ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/admin.css">
</head>

<body>
    <!-- Botón hamburguesa para dispositivos móviles -->
    <button class="toggle-sidebar" id="toggleSidebar">
        <i class="bi bi-list"></i>
    </button>

    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-receipt"></i> Gestión de Facturas</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaFacturaModal">
                    <i class="bi bi-plus-lg"></i> Nueva Factura
                </button>
            </div>

            <!-- Mensajes de sistema -->
            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success'];
                    unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error'];
                    unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control"
                                    placeholder="Buscar por número, tracking o cliente..." name="search"
                                    value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>Todos los estados
                                </option>
                                <option value="pendiente" <?php echo $status == 'pendiente' ? 'selected' : ''; ?>>
                                    Pendiente</option>
                                <option value="pagado" <?php echo $status == 'pagado' ? 'selected' : ''; ?>>Pagado
                                </option>
                                <option value="cancelado" <?php echo $status == 'cancelado' ? 'selected' : ''; ?>>
                                    Cancelado</option>
                                <option value="vencido" <?php echo $status == 'vencido' ? 'selected' : ''; ?>>Vencido
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <a href="facturas.php" class="btn btn-outline-secondary w-100">Limpiar</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de Facturas -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Factura</th>
                                    <th>Envío</th>
                                    <th>Cliente</th>
                                    <th>Monto</th>
                                    <th>Fecha Emisión</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($facturas) > 0): ?>
                                <?php foreach ($facturas as $factura): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($factura['numero_factura']); ?></td>
                                    <td><?php echo htmlspecialchars($factura['tracking_number']); ?></td>
                                    <td><?php echo htmlspecialchars($factura['cliente_nombre']); ?></td>
                                    <td>$<?php echo number_format($factura['monto'], 2); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($factura['fecha_emision'])); ?></td>
                                    <td>
                                        <span class="badge <?php
                                                                    echo match ($factura['status']) {
                                                                        'pendiente' => 'bg-warning',
                                                                        'pagado' => 'bg-success',
                                                                        'cancelado' => 'bg-danger',
                                                                        'vencido' => 'bg-secondary',
                                                                        default => 'bg-info'
                                                                    };
                                                                    ?>">
                                            <?php echo ucfirst(htmlspecialchars($factura['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-primary"
                                                onclick="verFactura(<?php echo $factura['id']; ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-success"
                                                onclick="registrarPago(<?php echo $factura['id']; ?>)">
                                                <i class="bi bi-cash"></i>
                                            </button>
                                            <a href="factura_pdf.php?id=<?php echo $factura['id']; ?>"
                                                class="btn btn-sm btn-outline-secondary" target="_blank">
                                                <i class="bi bi-file-pdf"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-info"
                                                onclick="enviarPorEmail(<?php echo $factura['id']; ?>)">
                                                <i class="bi bi-envelope"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">No se encontraron facturas</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link"
                                    href="?page=<?php echo $page - 1; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>">Anterior</a>
                            </li>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link"
                                    href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>

                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link"
                                    href="?page=<?php echo $page + 1; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>">Siguiente</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nueva Factura -->
    <div class="modal fade" id="nuevaFacturaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Generar Nueva Factura</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="procesar_factura.php" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="envio_id" class="form-label">Seleccionar Envío</label>
                            <select name="envio_id" id="envio_id" class="form-select" required>
                                <option value="">-- Selecciona un envío --</option>
                                <?php
                                $stmt = $conn->query("SELECT id, tracking_number, name FROM envios WHERE status = 'Entregado' AND id NOT IN (SELECT envio_id FROM facturas)");
                                if ($stmt) {
                                    while ($envio = $stmt->fetch_assoc()):
                                ?>
                                <option value="<?php echo $envio['id']; ?>">
                                    <?php echo $envio['tracking_number'] . ' - ' . $envio['name']; ?>
                                </option>
                                <?php endwhile;
                                }
                                ?>
                            </select>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Scripts específicos de la página -->
    <script>
    function verFactura(id) {
        window.location.href = 'detalle_factura.php?id=' + id;
    }

    function registrarPago(id) {
        window.location.href = 'registrar_pago.php?id=' + id;
    }

    function enviarPorEmail(id) {
        // Confirmación antes de enviar
        if (confirm('¿Desea enviar esta factura por correo electrónico?')) {
            fetch('enviar_factura_email.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al enviar el correo electrónico');
                });
        }
    }
    </script>

    <!-- Agregar script para manejar el sidebar -->
    <script>
    // Asegúrate de que este script esté al final de tu archivo o incluido en footer.php
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle sidebar en móvil
        const toggleButton = document.getElementById('toggleSidebar');
        const sidebar = document.querySelector('.sidebar');

        toggleButton.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            // Añadir animación sutil al botón
            this.classList.add('clicked');
            setTimeout(() => {
                this.classList.remove('clicked');
            }, 300);
        });

        // Cerrar sidebar al hacer clic en el contenido principal en móvil
        document.querySelector('.main-content').addEventListener('click', function() {
            if (window.innerWidth < 992 && sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });
    });
    </script>
</body>

</html>