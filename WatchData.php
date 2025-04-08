<!-- filepath: c:\xampp\htdocs\Programacion de formulario con BD\WatchData.php -->
<?php
session_start();
include 'components/db_connection.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

$stmt = $conn->prepare("SELECT * FROM envios WHERE usuario_id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/WatchData.css">
    <title>Ver Datos de Envíos</title>
</head>

<body>
    <?php include 'components/header.php'; ?>

    <div class="data-container">
        <button class="btn-back" onclick="window.location.href='index.php'">
            << Regresar</button>
                <h2>Datos de los Formularios Enviados</h2>

                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '<div class="data-entry">';
                        echo '<p><strong>Nombre:</strong> ' . htmlspecialchars($row['name']) . '</p>';
                        echo '<p><strong>Email:</strong> ' . htmlspecialchars($row['email']) . '</p>';
                        echo '<p><strong>Celular:</strong> ' . htmlspecialchars($row['phone']) . '</p>';
                        echo '<p><strong>Origen:</strong> ' . htmlspecialchars($row['origin']) . '</p>';
                        echo '<p><strong>Destino:</strong> ' . htmlspecialchars($row['destination']) . '</p>';
                        echo '<p><strong>Descripción:</strong> ' . htmlspecialchars($row['description']) . '</p>';
                        echo '<p><strong>Valor:</strong> $' . number_format($row['value'], 2) . '</p>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>No tienes envíos registrados.</p>';
                }
                ?>
    </div>
</body>

<footer>
    <?php include 'components/footer.php'; ?>
</footer>
<script src="js/dark-mode.js"></script>

</html>