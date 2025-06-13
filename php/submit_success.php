<?php
session_start();
require_once '../components/db_connection.php';

// Verificar que hay un número de seguimiento
if (!isset($_GET['tracking'])) {
    header("Location: dashboard.php");
    exit();
}

$tracking_number = $_GET['tracking'];

// Obtener información del envío
$stmt = $conn->prepare("SELECT * FROM envios WHERE tracking_number = ?");
$stmt->bind_param("s", $tracking_number);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard.php");
    exit();
}

$envio = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envío Registrado - MENDEZ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        :root {
            /* Variables para modo claro */
            --background-color: #f5f7fa;
            --card-bg: #ffffff;
            --text-color: #333333;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
            --header-gradient-start: #0062cc;
            --header-gradient-end: #0056b3;
            --success-color: #28a745;
            --success-bg: #e8f5e9;
            --tracking-bg-start: #f1f9fe;
            --tracking-bg-end: #e1f5fe;
            --tracking-border: #2196F3;
            --tracking-text: #01579b;
            --warning-bg-start: #fff8e1;
            --warning-bg-end: #fffde7;
            --warning-border: #FFC107;
            --warning-icon: #f57c00;
            --btn-pay-gradient-start: #28a745;
            --btn-pay-gradient-end: #20c997;
            --btn-pay-hover-start: #218838;
            --btn-pay-hover-end: #1e9d85;
            --box-shadow: rgba(0, 0, 0, 0.08);
            --box-shadow-hover: rgba(40, 167, 69, 0.3);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                /* Variables para modo oscuro */
                --background-color: #121212;
                --card-bg: #1e1e1e;
                --text-color: #e0e0e0;
                --text-muted: #adb5bd;
                --border-color: #343a40;
                --header-gradient-start: #1a4c7a;
                --header-gradient-end: #15406e;
                --success-color: #4caf50;
                --success-bg: #1b3229;
                --tracking-bg-start: #0a2537;
                --tracking-bg-end: #0a2030;
                --tracking-border: #0d47a1;
                --tracking-text: #64b5f6;
                --warning-bg-start: #332d13;
                --warning-bg-end: #332e15;
                --warning-border: #ffd54f;
                --warning-icon: #ffb74d;
                --btn-pay-gradient-start: #2e7d32;
                --btn-pay-gradient-end: #26a69a;
                --btn-pay-hover-start: #388e3c;
                --btn-pay-hover-end: #00897b;
                --box-shadow: rgba(0, 0, 0, 0.3);
                --box-shadow-hover: rgba(76, 175, 80, 0.4);
            }
        }
        
        body {
            background-color: var(--background-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .success-container {
            padding-top: 2rem;
            padding-bottom: 3rem;
        }
        
        .logo-container {
            margin-bottom: 15px;
            text-align: center;
        }
        
        .logo-container img {
            max-height: 80px;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        }
        
        .success-card {
            border: none;
            box-shadow: 0 10px 40px var(--box-shadow);
            border-radius: 18px;
            overflow: hidden;
            background-color: var(--card-bg);
            max-width: 800px;
            margin: 0 auto;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .success-header {
            background: linear-gradient(135deg, var(--header-gradient-start), var(--header-gradient-end));
            color: white;
            padding: 25px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .success-icon {
            font-size: 100px;
            color: var(--success-color);
            display: block;
            margin: 20px auto;
            position: relative;
            z-index: 2;
            animation: bounce 2s ease infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-20px);}
            60% {transform: translateY(-10px);}
        }
        
        .tracking-number-container {
            background: linear-gradient(to right, var(--tracking-bg-start), var(--tracking-bg-end));
            border-radius: 12px;
            padding: 20px;
            margin: 25px 0;
            border-left: 5px solid var(--tracking-border);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: background 0.3s ease, border-color 0.3s ease;
        }
        
        .tracking-label {
            color: var(--tracking-border);
            font-weight: bold;
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .tracking-value {
            font-size: 26px;
            font-weight: bold;
            color: var(--tracking-text);
            letter-spacing: 1px;
            font-family: 'Courier New', monospace;
        }
        
        .copy-btn {
            background: transparent;
            border: 1px solid var(--tracking-border);
            color: var(--tracking-border);
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 10px;
            transition: all 0.2s;
            padding: 0;
            cursor: pointer;
        }
        
        .copy-btn:hover {
            background: var(--tracking-border);
            color: white;
        }
        
        .warning-box {
            background: linear-gradient(to right, var(--warning-bg-start), var(--warning-bg-end));
            border-radius: 12px;
            padding: 20px;
            margin: 25px 0;
            border-left: 5px solid var(--warning-border);
            position: relative;
            transition: background 0.3s ease, border-color 0.3s ease;
        }
        
        .warning-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--warning-icon);
            font-size: 40px;
        }
        
        .warning-text {
            padding-left: 60px;
            color: var(--text-color);
        }
        
        .btn-pay-now {
            background: linear-gradient(45deg, var(--btn-pay-gradient-start), var(--btn-pay-gradient-end));
            border: none;
            color: white;
            font-weight: 600;
            padding: 15px 35px;
            border-radius: 50px;
            font-size: 18px;
            letter-spacing: 0.5px;
            box-shadow: 0 10px 15px rgba(40, 167, 69, 0.2);
            transition: all 0.3s ease;
            margin-top: 10px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-pay-now:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 20px var(--box-shadow-hover);
            background: linear-gradient(45deg, var(--btn-pay-hover-start), var(--btn-pay-hover-end));
            color: white;
        }
        
        .btn-pay-now::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(45deg);
            animation: shine 3s infinite;
        }
        
        @keyframes shine {
            0% {
                left: -50%;
                top: -50%;
            }
            100% {
                left: 100%;
                top: 100%;
            }
        }
        
        .btn-outline {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
            margin: 0 5px;
            border-color: var(--border-color);
        }
        
        .footer-text {
            color: var(--text-muted);
            font-size: 14px;
            text-align: center;
            margin-top: 40px;
        }
        
        .pulse {
            animation: pulse-animation 2s infinite;
        }

        @keyframes pulse-animation {
            0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
        }
        
        .success-content {
            padding: 40px;
        }
        
        .main-heading {
            color: var(--text-color);
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .sub-text {
            color: var(--text-muted);
            font-size: 18px;
            margin-bottom: 30px;
        }
        
        .status-badge {
            background: var(--success-bg);
            color: var(--success-color);
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        /* Estilos para el toast de notificación */
        .toast-notification {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--success-color);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .toast-notification.show {
            opacity: 1;
        }
        
        /* Mejora de accesibilidad para los botones y enlaces */
        a:focus, button:focus {
            outline: 2px solid var(--tracking-border);
            outline-offset: 2px;
        }
        
        @media (max-width: 768px) {
            .success-content {
                padding: 25px 15px;
            }
            
            .tracking-value {
                font-size: 20px;
            }
            
            .warning-icon {
                position: static;
                transform: none;
                font-size: 30px;
                margin-bottom: 10px;
                display: block;
                text-align: center;
            }
            
            .warning-text {
                padding-left: 0;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <!-- Logo en la parte superior -->
    <div class="logo-container mt-4">
        <img src="../img/logo.png" alt="MENDEZ Transportes" class="img-fluid">
    </div>
    
    <div class="container success-container">
        <div class="success-card">
            <!-- Header con gradiente -->
            <div class="success-header">
                <h2 class="mb-0"><i class="bi bi-check-circle-fill me-2"></i>¡Envío Registrado con Éxito!</h2>
            </div>
            
            <div class="success-content text-center">
                <!-- Icono animado -->
                <i class="bi bi-box-seam success-icon"></i>
                
                <!-- Status badge -->
                <div class="status-badge">
                    <i class="bi bi-check2-circle me-1"></i> Registrado correctamente
                </div>
                
                <h2 class="main-heading">Gracias por confiar en MENDEZ</h2>
                <p class="sub-text mb-4">Tu solicitud ha sido registrada correctamente y se ha enviado un correo con los detalles e instrucciones de pago.</p>
                
                <!-- Tracking number con mejor diseño -->
                <div class="tracking-number-container">
                    <span class="tracking-label">Número de seguimiento</span>
                    <div class="d-flex align-items-center justify-content-center">
                        <div class="tracking-value" id="tracking-number"><?php echo htmlspecialchars($tracking_number); ?></div>
                        <button class="copy-btn" onclick="copyToClipboard('<?php echo htmlspecialchars($tracking_number); ?>')">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                    <div class="mt-2 text-muted small">Guarda este número para realizar el seguimiento de tu envío</div>
                </div>

                <!-- Warning message con mejor diseño -->
                <div class="warning-box">
                    <i class="bi bi-exclamation-triangle-fill warning-icon"></i>
                    <div class="warning-text">
                        <strong>Importante:</strong> Para que tu envío sea procesado, es necesario completar el pago correspondiente.
                    </div>
                </div>

                <!-- Botón de pago mejorado con animación -->
                <div class="mt-4 mb-4">
                    <a href="../payment.php?tracking=<?php echo urlencode($tracking_number); ?>&amount=<?php echo $envio['estimated_cost']; ?>" 
                       class="btn btn-lg btn-pay-now pulse">
                        <i class="bi bi-credit-card-fill me-2"></i>Realizar Pago Ahora
                    </a>
                </div>

                <!-- Botones secundarios con mejor diseño -->
                <div class="d-flex justify-content-center gap-3 mt-3">
                    <a href="dashboard.php" class="btn btn-outline-primary btn-outline">
                        <i class="bi bi-house-fill me-1"></i> Ir al Dashboard
                    </a>
                    <a href="tracking.php?tracking=<?php echo urlencode($tracking_number); ?>" class="btn btn-outline-secondary btn-outline">
                        <i class="bi bi-search me-1"></i> Seguimiento
                    </a>
                </div>
                
                <!-- Footer informativo -->
                <div class="footer-text mt-5">
                    <p><i class="bi bi-info-circle me-1"></i> Si necesitas ayuda, comunícate al <strong>(999) 123-4567</strong> o escribe a <strong>soporte@mendez.com</strong></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contenedor para notificaciones toast -->
    <div id="toast-container"></div>
    
    <script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            // Crear notificación de copiado más moderna
            const toastContainer = document.getElementById('toast-container') || document.body;
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.innerHTML = '<i class="bi bi-check2-circle me-2"></i> Copiado al portapapeles';
            
            toastContainer.appendChild(toast);
            
            // Mostrar con un pequeño retraso para permitir la animación
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);
            
            // Desaparecer después de 2 segundos
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    toastContainer.removeChild(toast);
                }, 300);
            }, 2000);
            
            // Cambiar el ícono temporalmente 
            const copyBtn = document.querySelector('.copy-btn i');
            copyBtn.className = 'bi bi-check-lg';
            setTimeout(() => {
                copyBtn.className = 'bi bi-clipboard';
            }, 2000);
        });
    }
    
    // Detectar preferencia de color del sistema y aplicar clase al body
    function setColorScheme() {
        // Solo para compatibilidad con navegadores más antiguos
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }
    }
    
    // Configurar al cargar
    document.addEventListener('DOMContentLoaded', function() {
        setColorScheme();
        
        // Escuchar cambios en la preferencia de color
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', setColorScheme);
        }
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>