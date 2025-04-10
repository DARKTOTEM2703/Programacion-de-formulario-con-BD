<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/dashboard.css">

    <title>Formulario con Base de Datos</title>
</head>

<body>
    <div class="content">
        <?php include '../components/header.php'; ?>
        <div class="header">
            <img src="../img/foto.jpg" alt="Transporte" class="header__image">
        </div>
        <div class="main">
            <h2>Servicio especializado en mudanzas y carga en general a todo México</h2>
            <div class="buttons">
                <button class="btn btn-primary" onclick="window.location.href='forms.php'">Agendar servicio</button>
                <button class="btn btn-secondary" onclick="window.location.href='WatchData.php'">Ver servicios</button>
            </div>
            <div class="logout">
                <button class="btn btn-logout" onclick="window.location.href='logout.php'">Cerrar sesión</button>
            </div>
        </div>
    </div>
    <?php include '../components/footer.php'; ?>


</body>
<script src="../js/dark-mode.js"></script>

</html>