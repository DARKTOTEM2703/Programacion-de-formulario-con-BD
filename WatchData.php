<!-- filepath: c:\xampp\htdocs\Programacion de formulario con BD\WatchData.php -->
<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

include 'components/db_connection.php';
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
        // Obtén el ID del usuario autenticado desde la sesión
        $usuario_id = $_SESSION['usuario_id'];

        // Consulta para obtener los datos del usuario autenticado
        $sql = "SELECT * FROM solicitudes_envios WHERE usuario_id = $usuario_id";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            // Mostrar cada entrada
            while ($row = $result->fetch_assoc()) {
                echo '<div class="data-entry">';
                echo '<div class="data-row">';
                echo '<div class="data-column">';
                echo '<p><strong>Nombre:</strong> ' . $row['nombre_completo'] . '</p>';
                echo '<p><strong>Email:</strong> ' . $row['email'] . '</p>';
                echo '<p><strong>Celular:</strong> ' . $row['celular'] . '</p>';
                echo '<p><strong>Teléfono oficina:</strong> ' . $row['telefono_oficina'] . '</p>';
                echo '</div>';
                echo '<div class="data-column">';
                echo '<p><strong>Dirección origen:</strong> ' . $row['direccion_origen'] . '</p>';
                echo '<p><strong>Dirección destino:</strong> ' . $row['direccion_destino'] . '</p>';
                echo '<p><strong>Valor aproximado:</strong> $' . number_format($row['valor_aproximado'], 2) . '</p>';
                echo '<p><strong>¿Qué objetos quiere enviar?:</strong> ' . $row['descripcion'] . '</p>';
                echo '</div>';
                echo '</div>';
                echo '<hr>'; // Línea divisoria entre entradas
                echo '</div>';
            }
        } else {
            echo '<p>No hay datos disponibles.</p>';
        }

        $conn->close();
        ?>
    </div>
</body>

<footer>
    <?php include 'components/footer.php'; ?>
</footer>
<script src="js/dark-mode.js"></script>

</html>