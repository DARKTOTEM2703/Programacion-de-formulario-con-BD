<?php
header('Content-Type: text/html; charset=UTF-8');
require_once 'components/db_connection.php';

echo "<h1>Reparación de codificación</h1>";

// 1. Mostrar HEX actual
$result = $conn->query("SELECT id, status, HEX(status) as hex_status FROM envios WHERE status LIKE '%tr%nsit%'");
echo "<h2>Registros con 'tránsito' actual:</h2>";
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Estado actual</th><th>HEX</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>{$row['hex_status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No se encontraron registros con 'tránsito'</p>";
}

// 2. Hacer backup de los datos
$conn->query("CREATE TABLE IF NOT EXISTS envios_backup LIKE envios");
$conn->query("INSERT IGNORE INTO envios_backup SELECT * FROM envios");
echo "<p>✓ Backup creado en tabla envios_backup</p>";

// 3. Corregir todos los registros
$conn->query("UPDATE envios SET status = 'En tránsito' WHERE status LIKE '%tr%nsit%'");
$affected = $conn->affected_rows;
echo "<p>✓ Registros actualizados: $affected</p>";

// 4. Verificar resultado
$result = $conn->query("SELECT id, status, HEX(status) as hex_status FROM envios WHERE status = 'En tránsito'");
echo "<h2>Registros después de la corrección:</h2>";
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Estado correcto</th><th>HEX</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>{$row['hex_status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No se encontraron registros con 'En tránsito'</p>";
}

echo "<p>✓ ¡Corrección completada!</p>";
echo "<p><a href='admin/envios.php'>Volver a envíos</a></p>";
?>