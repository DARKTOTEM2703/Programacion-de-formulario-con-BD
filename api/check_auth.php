<?php
// filepath: c:\xampp\htdocs\Programacion-de-formulario-con-BD\api\check_auth.php
session_start();
header('Content-Type: application/json');

// Verificar si hay un usuario autenticado
if (isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'authenticated' => true,
        'name' => $_SESSION['nombre_usuario'] ?? '',
        'rol' => $_SESSION['rol'] ?? 'cliente'
    ]);
} else {
    echo json_encode([
        'authenticated' => false
    ]);
}