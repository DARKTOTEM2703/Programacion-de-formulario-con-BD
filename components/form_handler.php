<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'db_connection.php';

    // Recibir datos del formulario
    $nombre_completo = $_POST['name'];
    $email = $_POST['email'];
    $celular = $_POST['phone'];
    $telefono_oficina = $_POST['office-phone'];
    $direccion_origen = $_POST['origin'];
    $direccion_destino = $_POST['destination'];
    $descripcion = $_POST['description'];
    $valor_aproximado = $_POST['value'];

    // Insertar datos en la base de datos
    $sql = "INSERT INTO solicitudes_envios (nombre_completo, email, celular, telefono_oficina, direccion_origen, direccion_destino, descripcion, valor_aproximado)
            VALUES ('$nombre_completo', '$email', '$celular', '$telefono_oficina', '$direccion_origen', '$direccion_destino', '$descripcion', '$valor_aproximado')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Datos guardados correctamente');</script>";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    $conn->close();
}
?>