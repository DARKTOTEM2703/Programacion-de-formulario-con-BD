<?php
require_once 'config.php';

$servername = $_ENV['DB_SERVER'] ?? 'localhost';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';
$dbname = $_ENV['DB_NAME'] ?? 'gestion_envios';

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    error_log("❌ Conexión fallida: " . $conn->connect_error);
    die("Conexión fallida: " . $conn->connect_error);
}

// Establecer charset UTF-8
mysqli_set_charset($conn, "utf8mb4");

// Log de conexión exitosa para debugging (opcional)
error_log("✅ Conexión DB establecida correctamente");
