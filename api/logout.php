<?php
session_start();
session_unset();
session_destroy();
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
echo json_encode(['ok' => true]);