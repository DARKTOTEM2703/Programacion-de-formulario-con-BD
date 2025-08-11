<?php
session_start();
require_once '../components/db_connection.php';

/**
 * Mapa de redirecciones por rol
 * 1=Admin,2=Cliente,3=Repartidor,4=Bodeguista,5=Soporte,6=Supervisor,7=Contador,8=SuperAdmin
 */
$roleRedirects = [
    1 => '../admin/dashboard.php',
    2 => 'dashboard.php',
    3 => '../pwa/dashboard.php',                 // ajusta si cambiaste carpeta a /repartidor/
    4 => '../bodega/dashboard_bodega.php',
    5 => '../soporte/dashboard_soporte.php',
    6 => '../supervisor/dashboard_supervisor.php',
    7 => '../contador/dashboard_contador.php',
    8 => '../admin/dashboard.php',
];

function redirectIfLogged($map) {
    if (isset($_SESSION['usuario_id'], $_SESSION['rol_id'])) {
        $dest = $map[$_SESSION['rol_id']] ?? 'dashboard.php';
        header("Location: $dest");
        exit();
    }
}

redirectIfLogged($roleRedirects);

$error   = '';
$success = '';

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    // No redirigimos aquí con header para no romper el diseño; lo hacemos con JS (mantiene tu UI)
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Iniciar Sesión</title>
    <style>
        /* === Selector de modo/rol === */
        .role-tabs {
            display:flex;
            gap:.25rem;
            background:var(--bs-light,#f8f9fa);
            border-radius:10px;
            padding:.35rem .5rem;
            margin-bottom:1rem;
            justify-content:space-between;
        }
        .role-tab {
            flex:1;
            border:none;
            background:transparent;
            font-size:.85rem;
            font-weight:500;
            color:#6c757d;
            padding:.55rem .4rem;
            border-radius:8px;
            display:flex;
            align-items:center;
            justify-content:center;
            gap:.40rem;
            cursor:pointer;
            transition:.25s;
            position:relative;
        }
        .role-tab i{
            font-size:1rem;
            opacity:.85;
        }
        .role-tab.active {
            background:#0d6efd;
            color:#fff;
            box-shadow:0 4px 10px -2px rgba(13,110,253,.35);
        }
        .role-tab.active i{
            opacity:1;
        }
        .role-hint {
            font-size:.70rem;
            letter-spacing:.5px;
            text-transform:uppercase;
            color:#6c757d;
            margin-top:-.25rem;
            margin-bottom:.75rem;
            text-align:center;
        }
        .role-info {
            display:none;
            font-size:.75rem;
            text-align:center;
            color:#6c757d;
            background:#f1f3f5;
            border:1px solid #e4e7ea;
            padding:.55rem .75rem;
            border-radius:8px;
            margin-top:.35rem;
        }
        .role-info.active {
            display:block;
        }
        /* Dark mode friendly */
        @media (prefers-color-scheme: dark){
            .role-tabs{background:#2d2f33;}
            .role-tab{color:#adb5bd;}
            .role-tab.active{background:#0d6efd;color:#fff;}
            .role-info{background:#2d2f33;border-color:#3a3d41;color:#adb5bd;}
            body.bg-light{background:#1f2225 !important;}
            .card{background:#26292c;color:#e1e5ea;}
        }
    </style>
</head>
<body class="bg-light">
    <div class="container d-flex flex-column align-items-center justify-content-center vh-100">
        <div class="mb-4">
            <img src="../img/logo.png" alt="Logo de la empresa" class="img-fluid" style="max-width: 200px;">
        </div>

        <div class="card shadow-sm p-4" style="width:100%;max-width:400px;">
            <h2 class="text-center mb-3">Iniciar Sesión</h2>

            <!-- Selector de rol -->
            <div class="role-tabs" id="roleTabs">
                <button type="button" class="role-tab active" data-mode="general">
                    <i class="bi bi-person-circle"></i>
                    General
                </button>
                <button type="button" class="role-tab" data-mode="repartidor" data-redirect="../pwa/login.php">
                    <i class="bi bi-truck"></i>
                    Repartidor
                </button>
                <button type="button" class="role-tab" data-mode="bodega">
                    <i class="bi bi-building"></i>
                    Bodega
                </button>
            </div>
            <div class="role-hint">Selecciona el tipo de acceso (visual)</div>
            <div id="info-general" class="role-info active">Acceso para clientes / cuenta normal.</div>
            <div id="info-repartidor" class="role-info">Solo repartidores aprobados (app de entregas).</div>
            <div id="info-bodega" class="role-info">Personal interno de bodega autorizado.</div>

            <?php if ($error): ?>
                <div class="alert alert-danger text-center" role="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success text-center" role="alert">
                    <?= htmlspecialchars($success) ?><br>
                    <small>Redirigiendo...</small>
                </div>
            <?php endif; ?>

            <form action="../components/login_handler.php" method="POST">
                <input type="hidden" name="login_mode" id="login_mode" value="general">
                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" id="email" name="email" class="form-control" required autofocus placeholder="tu@correo.com">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña:</label>
                    <input type="password" id="password" name="password" class="form-control" required placeholder="Tu contraseña">
                </div>
                <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
            </form>

            <div class="text-center mt-3">
                <p>¿No tienes una cuenta? <a href="register.php" class="text-decoration-none">Regístrate aquí</a></p>
            </div>

            <div class="text-center mt-4">
                <p>O inicia sesión con:</p>
                <?php
                include '../components/config.php';
                $client_id = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
                if (empty($client_id)) {
                    echo '<div class="alert alert-warning">Google Client ID no configurado.</div>';
                }
                ?>
                <div id="g_id_onload"
                     data-client_id="<?= htmlspecialchars($client_id) ?>"
                     data-context="signin"
                     data-ux_mode="redirect"
                     data-login_uri="http://localhost/Programacion-de-formulario-con-BD/components/google_login_handler.php"
                     data-auto_prompt="false"></div>
                <div class="g_id_signin"
                     data-type="standard"
                     data-shape="rectangular"
                     data-theme="outline"
                     data-text="signin_with"
                     data-size="large"
                     data-logo_alignment="left"></div>
            </div>
        </div>
    </div>

    <?php if ($success && isset($_SESSION['rol_id'])): ?>
        <script>
            const roleRedirects = <?= json_encode($roleRedirects, JSON_UNESCAPED_SLASHES) ?>;
            const rol = <?= (int)$_SESSION['rol_id'] ?>;
            const destino = roleRedirects[rol] ?? 'dashboard.php';
            setTimeout(() => { window.location.href = destino; }, 1200);
        </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script>
        (function(){
            const tabs = document.querySelectorAll('.role-tab');
            const email = document.getElementById('email');
            const pass = document.getElementById('password');
            const modeInput = document.getElementById('login_mode');
            const infos = {
                general: document.getElementById('info-general'),
                repartidor: document.getElementById('info-repartidor'),
                bodega: document.getElementById('info-bodega')
            };
            const placeholders = {
                general: {email:'tu@correo.com', pass:'Tu contraseña'},
                bodega: {email:'bodega@empresa.com', pass:'Clave bodega'}
            };
            tabs.forEach(t=>{
                t.addEventListener('click', ()=>{
                    // Redirección directa si tiene data-redirect
                    const redirect = t.dataset.redirect;
                    if (redirect){
                        window.location.href = redirect;
                        return;
                    }
                    tabs.forEach(x=>x.classList.remove('active'));
                    t.classList.add('active');
                    const mode = t.dataset.mode;
                    modeInput.value = mode;
                    Object.keys(infos).forEach(k=>infos[k].classList.remove('active'));
                    if(infos[mode]) infos[mode].classList.add('active');
                    if(placeholders[mode]){
                        email.placeholder = placeholders[mode].email;
                        pass.placeholder  = placeholders[mode].pass;
                    } else {
                        email.placeholder = 'tu@correo.com';
                        pass.placeholder = 'Tu contraseña';
                    }
                    email.focus();
                });
            });
        })();
    </script>
</body>
</html>