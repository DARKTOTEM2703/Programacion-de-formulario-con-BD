<?php
// filepath: c:\xampp\htdocs\Programacion-de-formulario-con-BD\pwa\pending_approval.php
session_start();
require_once '../components/db_connection.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$nombre = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';

// Verificar estado del repartidor en la BD
$stmt = $conn->prepare("SELECT status FROM repartidores WHERE usuario_id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: login.php");
    exit();
}

$repartidor = $result->fetch_assoc();

// Si ya está aprobado, redirigir al dashboard
if ($repartidor['status'] === 'activo') {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuenta Pendiente de Aprobación</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <style>
        .pending-container {
            text-align: center;
            padding: 20px;
        }

        .pending-icon {
            font-size: 60px;
            color: #ffc107;
            margin-bottom: 20px;
        }

        .pending-title {
            font-size: 24px;
            margin-bottom: 15px;
        }

        .pending-message {
            margin-bottom: 20px;
            color: #6c757d;
        }

        .contact-support {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }

        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: var(--accent-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: var(--primary-color);
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="card-content">
            <div class="logo-container">
                <img src="assets/icons/logo.png" alt="MENDEZ Logo">
            </div>

            <div class="pending-container">
                <div class="pending-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h2 class="pending-title">Cuenta Pendiente de Aprobación</h2>
                <p class="pending-message">
                    Hola <strong><?php echo htmlspecialchars($nombre); ?></strong>, tu información ha sido recibida
                    correctamente y está siendo revisada por nuestro equipo.
                    Este proceso puede tomar hasta 24-48 horas hábiles.
                </p>
                <p>
                    Te notificaremos por correo electrónico cuando tu cuenta sea aprobada.
                </p>
                <div class="contact-support">
                    <p><strong>¿Tienes alguna pregunta?</strong></p>
                    <p>Contacta a nuestro equipo de soporte:</p>
                    <p><i class="fas fa-envelope"></i> soporte@mendez.com</p>
                    <p><i class="fas fa-phone"></i> (999) 123-4567</p>
                </div>
                <a href="logout.php" class="btn">Cerrar Sesión</a>
            </div>
        </div>
    </div>
</body>

</html>