<div class="sidebar">
    <div class="logo-container">
        <a class="navbar-brand" href="#">
            <img src="../img/logo.png" alt="MENDEZ Logo" height="50">
        </a>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"
                href="dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'facturas.php' ? 'active' : ''; ?>"
                href="facturas.php">
                <i class="bi bi-receipt"></i> Facturas
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'finanzas.php' ? 'active' : ''; ?>"
                href="finanzas.php">
                <i class="bi bi-graph-up-arrow"></i> Finanzas
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'envios.php' ? 'active' : ''; ?>"
                href="envios.php">
                <i class="bi bi-box-seam"></i> Envíos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'clientes.php' ? 'active' : ''; ?>"
                href="clientes.php">
                <i class="bi bi-people"></i> Clientes
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'repartidores.php' ? 'active' : ''; ?>"
                href="repartidores.php">
                <i class="bi bi-truck"></i> Repartidores
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reportes.php' ? 'active' : ''; ?>"
                href="reportes.php">
                <i class="bi bi-file-earmark-text"></i> Reportes
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'configuracion.php' ? 'active' : ''; ?>"
                href="configuracion.php">
                <i class="bi bi-gear"></i> Configuración
            </a>
        </li>
        <li class="nav-item mt-auto">
            <a class="nav-link text-danger" href="../php/logout.php">
                <i class="bi bi-box-arrow-right"></i> Cerrar sesión
            </a>
        </li>
    </ul>
</div>