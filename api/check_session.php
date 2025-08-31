<?php

session_start();
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol_id'])) {
    echo json_encode(['ok' => false]);
    exit;
}
// Solo permite bodeguista (rol_id = 4)
if ($_SESSION['rol_id'] != 4) {
    echo json_encode(['ok' => false, 'error' => 'no_bodega']);
    exit;
}
echo json_encode(['ok' => true, 'usuario' => $_SESSION['usuario_id'], 'rol' => $_SESSION['rol_id']]);