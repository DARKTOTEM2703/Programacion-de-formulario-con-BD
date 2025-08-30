<?php
session_start();
require_once '../components/db_connection.php';
require_once '../components/auth_helper.php';

$auth = inicializarAuth($conn);
$auth->verificarAcceso('bodega', 'inventario');

// Mostrar únicamente envíos que estén marcados como 'Recibido bodega'
$pendientes = [];
$res = $conn->query("SELECT * FROM vista_bodega_envios_completa WHERE status = 'Recibido bodega' ORDER BY urgent DESC, created_at ASC LIMIT 50");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $pendientes[] = $row;
    }
}

// Obtener estadísticas
$stats = [];
$res_stats = $conn->query("SELECT * FROM vista_dashboard_estadisticas");
if ($res_stats) {
    $stats = $res_stats->fetch_assoc();
}

// Obtener repartidores disponibles para asignación
$repartidores = [];
$res_reps = $conn->query("SELECT * FROM vista_repartidores_activos ORDER BY nombre_usuario");
// Corregir la lógica del bucle while
if ($res_reps) {
    while ($row = $res_reps->fetch_assoc()) {
        $repartidores[] = $row;
    }
}

$mensaje = $_GET['m'] ?? '';
$tipo_mensaje = $_GET['t'] ?? 'info';
?>
<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bodega | MENDEZ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .stats-card {
            border-left: 4px solid #0d6efd;
            background-color: white;
            box-shadow: 0 0.15rem 1.75rem rgba(0, 0, 0, 0.15);
        }

        .stats-icon {
            font-size: 1.5rem;
            color: #0d6efd;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.05);
        }

        .badge-urgent {
            position: relative;
        }

        .badge-urgent:before {
            content: '';
            position: absolute;
            width: 8px;
            height: 8px;
            background-color: #dc3545;
            border-radius: 50%;
            top: 50%;
            left: -12px;
            transform: translateY(-50%);
        }
        
        .qr-link {
            color: #0d6efd;
            text-decoration: none;
        }
        
        .qr-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3"><i class="bi bi-building"></i> Bodega</h1>
            <div>
                <span class="badge bg-primary me-2"><?= $auth->getRolNombre() ?></span>
                <a href="../php/logout.php" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
            </div>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card p-3">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon me-3">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div>
                            <div class="small text-muted">En bodega</div>
                            <div class="h4 mb-0"><?= number_format($stats['en_bodega'] ?? 0) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card p-3">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon me-3">
                            <i class="bi bi-truck"></i>
                        </div>
                        <div>
                            <div class="small text-muted">Cargados</div>
                            <div class="h4 mb-0"><?= number_format($stats['cargados'] ?? 0) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card p-3">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon me-3">
                            <i class="bi bi-person-badge"></i>
                        </div>
                        <div>
                            <div class="small text-muted">Repartidores activos</div>
                            <div class="h4 mb-0"><?= number_format($stats['repartidores_activos'] ?? 0) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card p-3">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon me-3">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div>
                            <div class="small text-muted">Urgentes</div>
                            <div class="h4 mb-0"><?= number_format($stats['urgentes'] ?? 0) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-qr-code-scan me-1"></i> Recepción de Paquetes
                    </div>
                    <div class="card-body">
                        <form id="frmIntake" class="mb-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-qr-code"></i></span>
                                <input type="text" name="tracking" class="form-control" placeholder="Escanea o ingresa el tracking number" required>
                                <button class="btn btn-success" type="submit">
                                    <i class="bi bi-box-arrow-in-down me-1"></i>Recibir
                                </button>
                            </div>
                        </form>
                        <div id="intakeMsg" class="mt-2 small text-muted"></div>

                        <hr>

                        <h6 class="mb-3">Paquetes recibidos hoy</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tracking</th>
                                        <th>Hora</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // En una implementación real, obtendrías los paquetes recibidos hoy
                                    // Por ahora, mostramos un mensaje si no hay datos
                                    ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No hay registros para hoy</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-warning text-dark">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-truck me-1"></i> Preparación y Carga</span>
                            <form class="d-flex">
                                <select class="form-select form-select-sm" name="filtro" onchange="this.form.submit()">
                                    <option value="">Todos los estados</option>
                                    <option value="Procesando" <?= isset($_GET['filtro']) && $_GET['filtro'] === 'Procesando' ? 'selected' : '' ?>>Procesando</option>
                                    <option value="Recibido bodega" <?= isset($_GET['filtro']) && $_GET['filtro'] === 'Recibido bodega' ? 'selected' : '' ?>>Recibido bodega</option>
                                    <option value="Cargado camión" <?= isset($_GET['filtro']) && $_GET['filtro'] === 'Cargado camión' ? 'selected' : '' ?>>Cargado camión</option>
                                </select>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tracking</th>
                                        <th>Destino</th>
                                        <th>Repartidor</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($pendientes)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">Sin pendientes</td>
                                        </tr>
                                    <?php else: foreach ($pendientes as $p): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($p['tracking_number'] ?? '') ?></code></td>
                                            <td><?= htmlspecialchars($p['destination'] ?? '') ?></td>
                                            <td>
                                                <?php if(!empty($p['repartidor_nombre'])): ?>
                                                    <?= htmlspecialchars($p['repartidor_nombre']) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Sin asignar</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <?php if(($p['status'] ?? '') == 'Procesando'): ?>
                                                        <button class="btn btn-sm btn-outline-success"
                                                            onclick="cargarCamion(<?= (int)($p['id'] ?? 0) ?>)">
                                                            <i class="bi bi-truck"></i> Cargar
                                                        </button>
                                                    <?php endif; ?>
                                                    <a href="detalle_envio.php?tracking=<?= urlencode($p['tracking_number']) ?>" class="btn btn-sm btn-outline-info">
                                                        <i class="bi bi-eye"></i> Ver Detalle
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Escaneo QR con cámara mejorado -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <i class="bi bi-camera"></i> Escanear QR con cámara
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <label for="camera-select" class="form-label">Selecciona cámara:</label>
                    <select id="camera-select" class="form-select form-select-sm" style="max-width:300px;"></select>
                </div>
                <div id="qr-reader" style="width:300px; margin-bottom:10px; border:1px solid #0d6efd; border-radius:8px;"></div>
                <div id="qr-result" class="small text-success mb-2"></div>
                <button id="btn-reiniciar" class="btn btn-outline-primary btn-sm" style="display:none;">
                    <i class="bi bi-arrow-repeat"></i> Reiniciar escaneo
                </button>
            </div>
        </div>
        <script src="https://unpkg.com/html5-qrcode"></script>
        <script>
        let qrScanner;
        let currentCameraId = null;

        function startScanner(cameraId) {
            if (qrScanner) qrScanner.stop().catch(()=>{});
            qrScanner = new Html5Qrcode("qr-reader");
            qrScanner.start(
                cameraId,
                { fps: 10, qrbox: 250 },
                onScanSuccess
            ).catch(err => {
                document.getElementById('qr-result').textContent = "Error al iniciar cámara: " + err;
            });
        }

        function onScanSuccess(decodedText, decodedResult) {
            document.getElementById('qr-result').textContent = "Tracking escaneado: " + decodedText;
            postJSON('../api/scan_event.php', {tracking: decodedText, accion: 'intake'})
                .then(res => {
                    document.getElementById('qr-result').textContent = res.ok ? 'Recepción OK' : ('Error: ' + (res.msg || ''));
                    if(res.ok) setTimeout(()=>location.reload(),700);
                });
            document.getElementById('btn-reiniciar').style.display = 'inline-block';
            qrScanner.stop();
        }

        Html5Qrcode.getCameras().then(cameras => {
            const select = document.getElementById('camera-select');
            select.innerHTML = '';
            cameras.forEach(cam => {
                const opt = document.createElement('option');
                opt.value = cam.id;
                opt.textContent = cam.label || `Cámara ${cam.id}`;
                select.appendChild(opt);
            });
            if (cameras.length > 0) {
                currentCameraId = cameras[0].id;
                startScanner(currentCameraId);
            }
            select.onchange = function() {
                currentCameraId = this.value;
                startScanner(currentCameraId);
                document.getElementById('btn-reiniciar').style.display = 'none';
                document.getElementById('qr-result').textContent = '';
            };
        }).catch(err => {
            document.getElementById('qr-result').textContent = "No se detectaron cámaras: " + err;
        });

        document.getElementById('btn-reiniciar').onclick = function() {
            document.getElementById('qr-result').textContent = '';
            startScanner(currentCameraId);
            this.style.display = 'none';
        };

        // Utilidad para POST JSON
        async function postJSON(url,payload){ const r=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)}); return r.json(); }
        </script>
    </div>
    <script>
    async function postJSON(url,payload){ const r=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)}); return r.json(); }
    document.getElementById('frmIntake').addEventListener('submit', async (e)=>{
      e.preventDefault();
      const tracking = e.target.tracking.value.trim();
      const out = document.getElementById('intakeMsg');
      out.textContent='Procesando...';
      const res = await postJSON('../api/scan_event.php',{tracking,accion:'intake'});
      out.textContent = res.ok ? 'Recepción OK' : ('Error: '+(res.msg||''));
      if(res.ok) setTimeout(()=>location.reload(),700);
    });
    async function cargarCamion(envio_id){
      const out = document.getElementById('loadMsg');
      out.textContent='Procesando...';
      const res = await postJSON('../api/cargar_camion.php',{envio_id});
      out.textContent = res.ok ? 'Cargado OK' : ('Error: '+(res.msg||''));
      if(res.ok) setTimeout(()=>location.reload(),700);
    }
    function verDetalles(envio_id) {
        // Redirigir a la página de detalles del envío
        window.location.href = `detalles_envio.php?id=${envio_id}`;
    }
    </script>
</body>
</html>