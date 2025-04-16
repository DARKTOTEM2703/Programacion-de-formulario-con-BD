<!-- filepath: c:\xampp\htdocs\Programacion de formulario con BD\WatchData.php -->
<?php
session_start();
include '../components/db_connection.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';

// Paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 5;
$offset = ($page - 1) * $records_per_page;

// Construir la consulta base
$query = "SELECT * FROM envios WHERE usuario_id = ?";
$count_query = "SELECT COUNT(*) as total FROM envios WHERE usuario_id = ?";

// Preparar arrays para los parámetros
$types = 'i'; // Tipo para usuario_id
$params = array($usuario_id);

// Aplicar filtros de búsqueda
if (!empty($search)) {
    $query .= " AND (name LIKE ? OR tracking_number LIKE ? OR destination LIKE ? OR origin LIKE ?)";
    $count_query .= " AND (name LIKE ? OR tracking_number LIKE ? OR destination LIKE ? OR origin LIKE ?)";
    $search_param = "%$search%";
    $types .= 'ssss'; // 4 strings para los LIKE
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Aplicar filtros de estado
if ($filter !== 'all') {
    $query .= " AND status = ?";
    $count_query .= " AND status = ?";
    $types .= 's';
    $params[] = $filter;
}

// Aplicar ordenamiento
switch ($sort) {
    case 'date_asc':
        $query .= " ORDER BY created_at ASC";
        break;
    case 'value_desc':
        $query .= " ORDER BY value DESC";
        break;
    case 'value_asc':
        $query .= " ORDER BY value ASC";
        break;
    default:
        $query .= " ORDER BY created_at DESC";
}

// Preparar los parámetros para la consulta de conteo
$count_types = $types;
$count_params = $params;

// Aplicar paginación a la consulta principal
$query .= " LIMIT ? OFFSET ?";
$types .= 'ii';
$params[] = $records_per_page;
$params[] = $offset;

// Preparar y ejecutar la consulta principal
$stmt = $conn->prepare($query);

// Vincular parámetros correctamente usando referencias
if (!empty($params)) {
    $bind_params = array($types);
    foreach ($params as &$param) {
        $bind_params[] = &$param;
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_params);
}

$stmt->execute();
$result = $stmt->get_result();

// Contar el total de registros para la paginación
$count_stmt = $conn->prepare($count_query);

// Vincular parámetros para la consulta de conteo
if (!empty($count_params)) {
    $bind_count_params = array($count_types);
    foreach ($count_params as &$param) {
        $bind_count_params[] = &$param;
    }
    call_user_func_array([$count_stmt, 'bind_param'], $bind_count_params);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result()->fetch_assoc();
$total_records = $count_result['total'];
$total_pages = ceil($total_records / $records_per_page);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/WatchData.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Historial de Envíos</title>
</head>

<body>
    <?php include '../components/header.php'; ?>

    <div class="data-container">
        <div class="dashboard-header">
            <button class="btn-back" onclick="window.location.href='dashboard.php'">
                <i class="bi bi-arrow-left me-1"></i> Regresar al Dashboard
            </button>
            <h2><i class="bi bi-box-seam me-2"></i>Historial de Envíos</h2>
        </div>

        <!-- Filtros y búsqueda -->
        <div class="filters-bar">
            <div class="search-box">
                <form action="" method="GET" id="search-form">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control"
                            placeholder="Buscar por nombre, tracking, origen o destino..."
                            value="<?php echo htmlspecialchars($search); ?>">
                        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div class="filter-options">
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle shadow-sm" type="button" id="filterDropdown"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-funnel-fill"></i> Filtrar por estado
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                        <li><a class="dropdown-item <?php echo $filter === 'all' ? 'active' : ''; ?>"
                                href="?search=<?php echo urlencode($search); ?>&filter=all&sort=<?php echo $sort; ?>">Todos</a>
                        </li>
                        <li><a class="dropdown-item <?php echo $filter === 'Procesando' ? 'active' : ''; ?>"
                                href="?search=<?php echo urlencode($search); ?>&filter=Procesando&sort=<?php echo $sort; ?>">Procesando</a>
                        </li>
                        <li><a class="dropdown-item <?php echo $filter === 'En tránsito' ? 'active' : ''; ?>"
                                href="?search=<?php echo urlencode($search); ?>&filter=En+tránsito&sort=<?php echo $sort; ?>">En
                                tránsito</a></li>
                        <li><a class="dropdown-item <?php echo $filter === 'Entregado' ? 'active' : ''; ?>"
                                href="?search=<?php echo urlencode($search); ?>&filter=Entregado&sort=<?php echo $sort; ?>">Entregado</a>
                        </li>
                    </ul>
                </div>

                <div class="dropdown ms-2">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="sortDropdown"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-sort-down"></i> Ordenar
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                        <li><a class="dropdown-item <?php echo $sort === 'date_desc' ? 'active' : ''; ?>"
                                href="?search=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>&sort=date_desc">Más
                                recientes primero</a></li>
                        <li><a class="dropdown-item <?php echo $sort === 'date_asc' ? 'active' : ''; ?>"
                                href="?search=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>&sort=date_asc">Más
                                antiguos primero</a></li>
                        <li><a class="dropdown-item <?php echo $sort === 'value_desc' ? 'active' : ''; ?>"
                                href="?search=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>&sort=value_desc">Mayor
                                valor primero</a></li>
                        <li><a class="dropdown-item <?php echo $sort === 'value_asc' ? 'active' : ''; ?>"
                                href="?search=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>&sort=value_asc">Menor
                                valor primero</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Contador de resultados -->
        <div class="results-count">
            <p><i class="bi bi-box2"></i> <?php echo $total_records; ?> envíos encontrados</p>
        </div>

        <?php if ($result->num_rows > 0) : ?>
            <div class="shipments-cards">
                <?php while ($row = $result->fetch_assoc()) : ?>
                    <div class="shipment-card">
                        <div class="shipment-header">
                            <div class="tracking-info">
                                <span class="tracking-label">Tracking</span>
                                <span
                                    class="tracking-number"><?php echo htmlspecialchars($row['tracking_number'] ?? 'N/A'); ?></span>
                            </div>
                            <div
                                class="status-badge <?php echo strtolower(str_replace(' ', '-', $row['status'] ?? 'processing')); ?>">
                                <i class="bi <?php
                                                switch ($row['status'] ?? '') {
                                                    case 'Entregado':
                                                        echo 'bi-check-circle-fill';
                                                        break;
                                                    case 'En tránsito':
                                                        echo 'bi-truck';
                                                        break;
                                                    default:
                                                        echo 'bi-hourglass-split';
                                                        break;
                                                }
                                                ?>"></i>
                                <?php echo htmlspecialchars($row['status'] ?? 'Procesando'); ?>
                            </div>
                        </div>

                        <div class="shipment-info">
                            <div class="info-row">
                                <div class="info-label"><i class="bi bi-calendar3"></i> Fecha:</div>
                                <div class="info-value">
                                    <?php echo isset($row['created_at']) ? date('d/m/Y H:i', strtotime($row['created_at'])) : 'N/A'; ?>
                                </div>
                            </div>

                            <div class="route-info">
                                <div class="origin">
                                    <span class="icon"><i class="bi bi-geo-alt-fill"></i></span>
                                    <span class="location"><?php echo htmlspecialchars($row['origin'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="route-line">
                                    <span class="dot"></span>
                                    <span class="line"></span>
                                    <span class="dot"></span>
                                </div>
                                <div class="destination">
                                    <span class="icon"><i class="bi bi-geo"></i></span>
                                    <span class="location"><?php echo htmlspecialchars($row['destination'] ?? 'N/A'); ?></span>
                                </div>
                            </div>

                            <div class="info-row">
                                <div class="info-label"><i class="bi bi-person"></i> Remitente:</div>
                                <div class="info-value"><?php echo htmlspecialchars($row['name'] ?? 'N/A'); ?></div>
                            </div>

                            <div class="info-row">
                                <div class="info-label"><i class="bi bi-box2"></i> Paquete:</div>
                                <div class="info-value">
                                    <?php echo htmlspecialchars($row['package_type'] ?? 'N/A'); ?>
                                    (<?php echo number_format($row['weight'] ?? 0, 1); ?> kg)
                                </div>
                            </div>

                            <?php if (isset($row['delivery_date']) && !empty($row['delivery_date'])) : ?>
                                <div class="info-row">
                                    <div class="info-label"><i class="bi bi-calendar-check"></i> Entrega estimada:</div>
                                    <div class="info-value"><?php echo date('d/m/Y', strtotime($row['delivery_date'])); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="shipment-footer">
                            <div class="cost-info">
                                <span class="cost-label">Costo total:</span>
                                <span class="cost-value">$<?php echo number_format($row['estimated_cost'] ?? 0, 2); ?></span>
                            </div>

                            <button class="btn-details" data-bs-toggle="modal"
                                data-bs-target="#shipmentModal-<?php echo $row['id']; ?>">
                                Ver detalles <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Modal con detalles completos -->
                    <div class="modal fade" id="shipmentModal-<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        <i class="bi bi-box-seam me-2"></i>
                                        Detalles del Envío #<?php echo htmlspecialchars($row['tracking_number'] ?? 'N/A'); ?>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="details-header">
                                        <div class="status-timeline">
                                            <div
                                                class="status-step <?php echo ($row['status'] ?? '') == 'Procesando' ? 'active' : ''; ?>">
                                                <div class="status-icon"><i class="bi bi-file-earmark-check"></i></div>
                                                <div class="status-text">Procesando</div>
                                            </div>
                                            <div class="status-line"></div>
                                            <div
                                                class="status-step <?php echo ($row['status'] ?? '') == 'En tránsito' ? 'active' : ''; ?>">
                                                <div class="status-icon"><i class="bi bi-truck"></i></div>
                                                <div class="status-text">En tránsito</div>
                                            </div>
                                            <div class="status-line"></div>
                                            <div
                                                class="status-step <?php echo ($row['status'] ?? '') == 'Entregado' ? 'active' : ''; ?>">
                                                <div class="status-icon"><i class="bi bi-check-circle"></i></div>
                                                <div class="status-text">Entregado</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row details-section">
                                        <div class="col-md-6">
                                            <h6><i class="bi bi-person-fill me-2"></i>Información del Remitente</h6>
                                            <table class="table details-table">
                                                <tbody>
                                                    <tr>
                                                        <td>Nombre:</td>
                                                        <td><?php echo htmlspecialchars($row['name'] ?? 'N/A'); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Email:</td>
                                                        <td><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Celular:</td>
                                                        <td><?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
                                                    </tr>
                                                    <?php if (isset($row['office_phone']) && !empty($row['office_phone'])) : ?>
                                                        <tr>
                                                            <td>Teléfono oficina:</td>
                                                            <td><?php echo htmlspecialchars($row['office_phone']); ?></td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="col-md-6">
                                            <h6><i class="bi bi-box2-fill me-2"></i>Información del Paquete</h6>
                                            <table class="table details-table">
                                                <tbody>
                                                    <tr>
                                                        <td>Tipo:</td>
                                                        <td><?php echo htmlspecialchars($row['package_type'] ?? 'N/A'); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Peso:</td>
                                                        <td><?php echo number_format($row['weight'] ?? 0, 1); ?> kg</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Descripción:</td>
                                                        <td><?php echo htmlspecialchars($row['description'] ?? 'N/A'); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Valor declarado:</td>
                                                        <td>$<?php echo number_format($row['value'] ?? 0, 2); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="row details-section">
                                        <div class="col-md-6">
                                            <h6><i class="bi bi-geo-alt-fill me-2"></i>Dirección de Origen</h6>
                                            <div class="address-box">
                                                <?php echo htmlspecialchars($row['origin'] ?? 'N/A'); ?>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <h6><i class="bi bi-geo-fill me-2"></i>Dirección de Destino</h6>
                                            <div class="address-box">
                                                <?php echo htmlspecialchars($row['destination'] ?? 'N/A'); ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row details-section">
                                        <div class="col-md-6">
                                            <h6><i class="bi bi-info-circle-fill me-2"></i>Detalles Adicionales</h6>
                                            <table class="table details-table">
                                                <tbody>
                                                    <tr>
                                                        <td>Servicio urgente:</td>
                                                        <td><?php echo ($row['urgent'] ?? 0) ? '<span class="badge bg-danger">Sí</span>' : 'No'; ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Seguro:</td>
                                                        <td><?php echo ($row['insurance'] ?? 0) ? '<span class="badge bg-success">Sí</span>' : 'No'; ?>
                                                        </td>
                                                    </tr>
                                                    <?php if (isset($row['delivery_date']) && !empty($row['delivery_date'])) : ?>
                                                        <tr>
                                                            <td>Fecha de entrega estimada:</td>
                                                            <td><?php echo date('d/m/Y', strtotime($row['delivery_date'])); ?></td>
                                                        </tr>
                                                    <?php endif; ?>
                                                    <tr>
                                                        <td>Fecha de registro:</td>
                                                        <td><?php echo isset($row['created_at']) ? date('d/m/Y H:i', strtotime($row['created_at'])) : 'N/A'; ?>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="col-md-6">
                                            <h6><i class="bi bi-credit-card-fill me-2"></i>Información de Cobro</h6>
                                            <table class="table details-table">
                                                <tbody>
                                                    <tr>
                                                        <td>Costo base:</td>
                                                        <td>$100.00</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Costo por peso (<?php echo number_format($row['weight'] ?? 0, 1); ?>
                                                            kg):</td>
                                                        <td>$<?php echo number_format(($row['weight'] ?? 0) * 10, 2); ?></td>
                                                    </tr>
                                                    <?php if (($row['urgent'] ?? 0)) : ?>
                                                        <tr>
                                                            <td>Cargo por servicio urgente:</td>
                                                            <td>$200.00</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                    <?php if (($row['insurance'] ?? 0)) : ?>
                                                        <tr>
                                                            <td>Seguro (5% del valor):</td>
                                                            <td>$<?php echo number_format(($row['value'] ?? 0) * 0.05, 2); ?></td>
                                                        </tr>
                                                    <?php endif; ?>
                                                    <tr class="total-row">
                                                        <td>Total:</td>
                                                        <td>$<?php echo number_format($row['estimated_cost'] ?? 0, 2); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <?php if (isset($row['additional_notes']) && !empty($row['additional_notes'])) : ?>
                                        <div class="row details-section">
                                            <div class="col-12">
                                                <h6><i class="bi bi-journal-text me-2"></i>Notas Adicionales</h6>
                                                <div class="notes-box">
                                                    <?php echo htmlspecialchars($row['additional_notes']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($row['package_image']) && !empty($row['package_image'])) : ?>
                                        <div class="row details-section">
                                            <div class="col-12">
                                                <h6><i class="bi bi-image me-2"></i>Imagen del Paquete</h6>
                                                <div class="image-box">
                                                    <img src="../<?php echo htmlspecialchars($row['package_image']); ?>"
                                                        alt="Imagen del paquete" class="img-fluid package-image">
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                    <button type="button" class="btn btn-success"
                                        onclick="printShipmentDetails(<?php echo $row['id']; ?>)">
                                        <i class="bi bi-printer"></i> Imprimir
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Paginación -->
            <?php if ($total_pages > 1) : ?>
                <nav aria-label="Paginación de envíos">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link"
                                href="?search=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>&sort=<?php echo $sort; ?>&page=<?php echo $page - 1; ?>">Anterior</a>
                        </li>

                        <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link"
                                    href="?search=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>&sort=<?php echo $sort; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link"
                                href="?search=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>&sort=<?php echo $sort; ?>&page=<?php echo $page + 1; ?>">Siguiente</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>

        <?php else : ?>
            <div class="no-data">
                <div class="no-data-icon">
                    <i class="bi bi-box2"></i>
                </div>
                <h3>No hay envíos para mostrar</h3>
                <p>No se encontraron envíos que coincidan con tu búsqueda o aún no has registrado ningún envío.</p>
                <a href="forms.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Crear nuevo envío
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function printShipmentDetails(id) {
            const modalContent = document.querySelector(`#shipmentModal-${id} .modal-content`).innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Detalles de Envío</title>
                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
                    <link rel="stylesheet" href="../css/WatchData.css">
                    <style>
                        body { padding: 20px; }
                        .btn-close, .modal-footer { display: none; }
                        @media print {
                            .modal-header { border-bottom: 1px solid #dee2e6; }
                        }
                    </style>
                </head>
                <body>
                    <div class="container">
                        ${modalContent}
                    </div>
                    <script>window.onload = function() { window.print(); }<\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
        }
    </script>
</body>

</html>