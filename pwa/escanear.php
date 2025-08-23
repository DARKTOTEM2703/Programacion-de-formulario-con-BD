<?php
session_start();
require_once '../components/db_connection.php';
require_once '../components/email_envio_en_camino.php'; // Importar la función para enviar correos

// Verificar autenticación
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'repartidor') {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Procesamiento del código escaneado (si se envía)
$mensaje = "";
$tipo_mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tracking_code'])) {
    $tracking_code = trim($_POST['tracking_code']);

    // Buscar el envío en la base de datos
    $stmt = $conn->prepare("
        SELECT e.*, re.usuario_id 
        FROM envios e 
        LEFT JOIN repartidores_envios re ON e.id = re.envio_id 
        WHERE e.tracking_number = ?
    ");
    $stmt->bind_param("s", $tracking_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $envio = $result->fetch_assoc();

        // Mapear las claves faltantes
        $envio['recipient_name'] = $envio['name'];
        $envio['recipient_address'] = $envio['destination'];

        // Verificar si el envío está asignado al repartidor
        if ($envio['usuario_id'] !== $usuario_id) {
            // Asignar automáticamente el envío al repartidor
            $stmt = $conn->prepare("INSERT INTO repartidores_envios (envio_id, usuario_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $envio['id'], $usuario_id);
            $stmt->execute();
        }

        // Cambiar el estado a "En tránsito"
        $nuevo_estado = "En tránsito";
        if ($nuevo_estado === 'En tránsito') {
            // Generar PIN seguro
            $pin_seguro = random_int(100000, 999999);

            // Actualizar estado y PIN en la tabla `envios`
            $update = $conn->prepare("UPDATE envios SET status = ?, pin_seguro = ?, updated_at = NOW() WHERE id = ?");
            $update->bind_param("sii", $nuevo_estado, $pin_seguro, $envio['id']);
            $update->execute();
        }

        if ($update->execute()) {
            // Enviar correo al cliente con el PIN
            $correo_enviado = enviarCorreoEnvioEnCamino($envio['email'], $envio['name'], $tracking_code, $pin_seguro);

            if ($correo_enviado === true) {
                $mensaje = "El estado del envío #" . $tracking_code . " ha cambiado a: " . $nuevo_estado . ". PIN generado y correo enviado.";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "El estado del envío #" . $tracking_code . " ha cambiado a: " . $nuevo_estado . ". PIN generado, pero no se pudo enviar el correo.";
                $tipo_mensaje = "warning";
            }
        } else {
            $mensaje = "Error al actualizar el estado del envío.";
            $tipo_mensaje = "danger";
        }
    } else {
        $mensaje = "El código escaneado no corresponde a ningún envío.";
        $tipo_mensaje = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Envíos - MENDEZ Transportes</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        :root {
            --primary-color: #0057B8;
            /* Color azul corporativo MENDEZ */
            --secondary-color: #FF9500;
            /* Color naranja/amarillo de acento MENDEZ */
            --accent-color: #FF9500;
            --success-color: #2E8B57;
            --danger-color: #D32F2F;
            --warning-color: #F9A826;
            --light-bg: #F8F9FA;
            --dark-text: #212529;
        }

        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 80px;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar-brand img {
            height: 36px;
            width: auto;
        }

        .company-logo {
            width: 120px;
            height: auto;
            margin-bottom: 20px;
        }

        .scanner-container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            padding: 25px;
            margin-bottom: 25px;
        }

        #reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            border-radius: 15px;
            overflow: hidden;
            border: 2px solid #e9ecef;
        }

        .scan-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 15px;
        }

        .scan-instructions {
            color: #6c757d;
            font-size: 0.95rem;
            max-width: 400px;
            margin: 0 auto 25px;
        }

        .scan-frame {
            position: relative;
            width: 100%;
            max-width: 350px;
            height: 350px;
            margin: 0 auto;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><path d="M10,0 L0,0 L0,10 M90,0 L100,0 L100,10 M0,90 L0,100 L10,100 M100,90 L100,100 L90,100" stroke="%230057B8" stroke-width="5" fill="none"/></svg>');
            background-position: center;
            background-size: 110% 110%;
            background-repeat: no-repeat;
        }

        .btn-custom-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-custom-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 83, 184, 0.2);
        }

        .btn-custom-outline {
            color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-custom-outline:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 83, 184, 0.15);
        }

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 25px;
            justify-content: center;
        }

        .action-btn {
            flex: 1;
            min-width: 130px;
            border-radius: 8px;
            padding: 12px 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .input-group {
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            overflow: hidden;
        }

        .input-group .form-control {
            border-right: none;
            padding: 12px;
            font-size: 1rem;
        }

        .input-group .btn {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            padding-left: 20px;
            padding-right: 20px;
        }

        .card {
            border: none;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            border-radius: 15px;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 15px 20px;
        }

        .nav-link {
            color: #fff !important;
            opacity: 0.8;
            transition: all 0.3s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            opacity: 1;
            transform: translateY(-2px);
        }

        .navbar.fixed-bottom {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
            overflow: hidden;
        }

        .alert {
            border-radius: 10px;
            border-left: 5px solid;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }

        .alert-success {
            border-left-color: var(--success-color);
        }

        .alert-danger {
            border-left-color: var(--danger-color);
        }

        .alert-info {
            border-left-color: var(--secondary-color);
        }

        .badge {
            padding: 6px 10px;
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        /* Animación para scanner */
        .scanner-animation {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--primary-color), transparent);
            animation: scan 2s linear infinite;
            z-index: 5;
        }

        @keyframes scan {
            0% {
                top: 0;
            }

            50% {
                top: 100%;
            }

            100% {
                top: 0;
            }
        }
    </style>
    <link rel="manifest" href="manifest.json">
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('service-worker.js');
        }
    </script>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <!-- Logo de MENDEZ Transportes -->
                <img src="assets/icons/logo.png" alt="MENDEZ Logo">
                <span>Control de Envíos</span>
            </a>
        </div>
    </nav>

    <div class="container mt-4 mb-5 pb-5">
        <!-- Mensaje de respuesta -->
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
                <div class="d-flex align-items-center">
                    <?php if ($tipo_mensaje == "success"): ?>
                        <i class="bi bi-check-circle-fill me-2 fs-4"></i>
                    <?php elseif ($tipo_mensaje == "danger"): ?>
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
                    <?php else: ?>
                        <i class="bi bi-info-circle-fill me-2 fs-4"></i>
                    <?php endif; ?>
                    <div><?php echo $mensaje; ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Logo centrado -->
        <div class="text-center mb-4">
            <!-- Logo de MENDEZ -->
            <img src="../img/logo.png" alt="MENDEZ Transportes" class="company-logo">
        </div>

        <!-- Scanner -->
        <div class="scanner-container text-center">
            <h3 class="scan-title"><i class="bi bi-qr-code-scan me-2"></i>Escanea el código de seguimiento</h3>
            <p class="scan-instructions">Posiciona el código QR o código de barras del envío dentro del recuadro para
                escanearlo automáticamente.</p>

            <div id="reader-container" class="mb-4 position-relative">
                <div id="reader"></div>
                <div class="scanner-animation d-none"></div>
            </div>

            <div class="mt-4">
                <div class="d-flex justify-content-center gap-3 mb-4">
                    <button id="startButton" class="btn btn-custom-primary">
                        <i class="bi bi-camera-video-fill me-2"></i>Iniciar cámara
                    </button>
                    <button id="switchCameraButton" class="btn btn-custom-outline" disabled>
                        <i class="bi bi-arrow-repeat me-2"></i>Cambiar cámara
                    </button>
                </div>

                <div class="text-center mt-4">
                    <p class="mb-3" style="color: #6c757d; font-weight: 500;">O ingresa el código manualmente:</p>
                    <form method="post" class="d-flex justify-content-center">
                        <div class="input-group" style="max-width: 400px;">
                            <input type="text" name="tracking_code" class="form-control" placeholder="Tracking number"
                                required>
                            <button type="submit" class="btn btn-custom-primary">
                                <i class="bi bi-search me-1"></i> Buscar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Acciones de envío -->
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tracking_code']) && $result->num_rows > 0): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0 text-white">Acciones para envío #<?php echo htmlspecialchars($tracking_code); ?></h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-person-fill me-3 text-primary fs-4"></i>
                            <h5 class="mb-0"><?php echo htmlspecialchars($envio['recipient_name']); ?></h5>
                        </div>

                        <div class="d-flex mb-2">
                            <i class="bi bi-geo-alt-fill me-3 text-primary"></i>
                            <p class="mb-0"><?php echo htmlspecialchars($envio['recipient_address']); ?></p>
                        </div>

                        <div class="d-flex align-items-center">
                            <i class="bi bi-info-circle-fill me-3 text-primary"></i>
                            <div>
                                <span class="fw-bold me-2">Estado:</span>
                                <span class="badge bg-<?php echo getStatusClass($envio['status']); ?>">
                                    <?php echo htmlspecialchars($envio['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Barra de navegación inferior -->
    <nav class="navbar fixed-bottom navbar-dark p-0 shadow">
        <div class="container-fluid p-0">
            <div class="d-flex flex-row justify-content-around w-100">
                <a href="dashboard.php" class="nav-link text-center py-3 flex-fill">
                    <div class="d-flex flex-column align-items-center">
                        <i class="bi bi-house-door-fill fs-4"></i>
                        <span class="small">Inicio</span>
                    </div>
                </a>
                <a href="escanear.php" class="nav-link text-center py-3 flex-fill active">
                    <div class="d-flex flex-column align-items-center">
                        <i class="bi bi-qr-code-scan fs-4"></i>
                        <span class="small">Escanear</span>
                    </div>
                </a>
                <a href="mapa.php" class="nav-link text-center py-3 flex-fill">
                    <div class="d-flex flex-column align-items-center">
                        <i class="bi bi-geo-alt-fill fs-4"></i>
                        <span class="small">Mapa</span>
                    </div>
                </a>
                <a href="perfil.php" class="nav-link text-center py-3 flex-fill">
                    <div class="d-flex flex-column align-items-center">
                        <i class="bi bi-person-fill fs-4"></i>
                        <span class="small">Perfil</span>
                    </div>
                </a>
            </div>
        </div>
    </nav>

    <!-- Función helper para clases de estados -->
    <?php
    function getStatusClass($status)
    {
        switch ($status) {
            case 'Procesando':
                return 'secondary';
            case 'En camino':
                return 'primary';
            case 'En ruta':
                return 'info';
            case 'Entregado':
                return 'success';
            case 'Cancelado':
            case 'Intento fallido':
                return 'danger';
            default:
                return 'secondary';
        }
    }
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const html5QrCode = new Html5Qrcode("reader");
            const startButton = document.getElementById('startButton');
            const switchCameraButton = document.getElementById('switchCameraButton');
            const scannerAnimation = document.querySelector('.scanner-animation');
            let currentCamera = null;
            let cameras = [];
            let isScanning = false;

            // Mejorar aspecto del lector
            const readerElement = document.getElementById('reader');
            if (readerElement) {
                // Eliminar el mensaje por defecto del escáner
                const removeDefaultMessage = () => {
                    const defaultHeader = readerElement.querySelector('div:first-child');
                    if (defaultHeader) {
                        defaultHeader.style.display = 'none';
                    }
                };

                // Intentar varias veces porque el componente puede tardar en renderizarse
                setTimeout(removeDefaultMessage, 100);
                setTimeout(removeDefaultMessage, 500);
                setTimeout(removeDefaultMessage, 1000);
            }

            // Acceder a las cámaras disponibles
            Html5Qrcode.getCameras().then(devices => {
                if (devices && devices.length) {
                    cameras = devices;
                    currentCamera = devices[0].id;
                    startButton.disabled = false;
                    if (devices.length > 1) {
                        switchCameraButton.disabled = false;
                    }
                } else {
                    startButton.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>No hay cámaras';
                    startButton.disabled = true;
                }
            }).catch(err => {
                console.error('Error al acceder a las cámaras: ', err);
                startButton.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Error cámara';
                startButton.disabled = true;
            });

            // Iniciar o detener escaneo
            startButton.addEventListener('click', function() {
                if (!isScanning) {
                    // Configuración del escáner
                    const config = {
                        fps: 10,
                        qrbox: {
                            width: 250,
                            height: 250
                        },
                        aspectRatio: 1.0
                    };

                    html5QrCode.start(currentCamera, config, onScanSuccess, onScanFailure)
                        .then(() => {
                            isScanning = true;
                            startButton.innerHTML =
                                '<i class="bi bi-stop-circle-fill me-2"></i>Detener';
                            startButton.classList.replace('btn-custom-primary', 'btn-danger');
                            switchCameraButton.disabled = true;

                            // Mostrar animación de escaneo
                            scannerAnimation.classList.remove('d-none');
                        })
                        .catch((err) => {
                            console.error('Error al iniciar el escáner: ', err);
                            alert(
                                'No se pudo iniciar la cámara. Permite el acceso a la cámara e inténtalo de nuevo.'
                            );
                        });
                } else {
                    html5QrCode.stop().then(() => {
                        isScanning = false;
                        startButton.innerHTML =
                            '<i class="bi bi-camera-video-fill me-2"></i>Iniciar cámara';
                        startButton.classList.replace('btn-danger', 'btn-custom-primary');
                        if (cameras.length > 1) {
                            switchCameraButton.disabled = false;
                        }

                        // Ocultar animación de escaneo
                        scannerAnimation.classList.add('d-none');
                    }).catch((err) => {
                        console.error('Error al detener el escáner: ', err);
                    });
                }
            });

            // Cambiar de cámara
            switchCameraButton.addEventListener('click', function() {
                if (cameras.length <= 1) return;

                // Buscar la siguiente cámara
                const currentIndex = cameras.findIndex(camera => camera.id === currentCamera);
                const nextIndex = (currentIndex + 1) % cameras.length;
                currentCamera = cameras[nextIndex].id;

                // Mostrar nombre de cámara (opcional)
                const cameraName = cameras[nextIndex].label || `Cámara ${nextIndex + 1}`;
                switchCameraButton.innerHTML = `<i class="bi bi-arrow-repeat me-2"></i>${cameraName}`;

                // Avisar al usuario
                const toastElement = document.createElement('div');
                toastElement.className = 'alert alert-info alert-dismissible fade show';
                toastElement.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="bi bi-info-circle-fill me-2 fs-4"></i>
                        <div>Cámara cambiada a: ${cameraName}. Presiona "Iniciar cámara" para usarla.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;

                document.querySelector('.container').insertBefore(toastElement, document.querySelector(
                    '.container').firstChild);

                setTimeout(() => {
                    toastElement.classList.remove('show');
                    setTimeout(() => toastElement.remove(), 300);
                }, 3000);
            });

            // Función cuando se escanea un código exitosamente
            function onScanSuccess(decodedText, decodedResult) {
                // Sonido de éxito (opcional)
                const successAudio = new Audio(
                    'data:audio/mp3;base64,//uQxAAAAAAAAAAAAAAAAAAAAAAAWGluZwAAAA8AAAAFAAAGhgBVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVWqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqr///////////////////8AAAA8TEFNRTMuOTlyAc0AAAAAAAAAABSAJAJAQgAAgAAABoZCpNLfAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA//sQxAADwAABpAAAACAAADSAAAAETEFNRTMuOTkuNVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVV//sQxBsAAAGkAAAAIAAANIAAAARVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVV'
                );
                successAudio.play();

                // Efecto visual de éxito
                const readerContainer = document.getElementById('reader-container');
                readerContainer.style.boxShadow = '0 0 0 3px rgba(46, 139, 87, 0.5)';
                setTimeout(() => {
                    readerContainer.style.boxShadow = '';
                }, 1000);

                // Detener el escaneo después de un resultado exitoso
                html5QrCode.stop().then(() => {
                    isScanning = false;
                    startButton.innerHTML = '<i class="bi bi-camera-video-fill me-2"></i>Iniciar cámara';
                    startButton.classList.replace('btn-danger', 'btn-custom-primary');
                    if (cameras.length > 1) {
                        switchCameraButton.disabled = false;
                    }

                    // Ocultar animación de escaneo
                    scannerAnimation.classList.add('d-none');

                    // Enviar el código escaneado al servidor
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.style.display = 'none';

                    const input = document.createElement('input');
                    input.type = 'text';
                    input.name = 'tracking_code';
                    input.value = decodedText;

                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                });
            }

            // Función cuando falla el escaneo
            function onScanFailure(error) {
                // No hacer nada, seguir escaneando
            }
        });
    </script>
</body>

</html>