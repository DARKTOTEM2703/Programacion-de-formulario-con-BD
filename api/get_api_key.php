<?php
// Cabeceras para permitir solicitudes desde JavaScript
header('Content-Type: application/json');

// Cargar variables de entorno si usas un archivo .env
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Eliminar comillas si existen
            $value = trim($value, '"');
            $value = trim($value, "'");
            $_ENV[$key] = $value;
        }
    }
}

// Devolver API key
if (isset($_ENV['LOCATIONIQ_API_KEY'])) {
    echo json_encode(['key' => $_ENV['LOCATIONIQ_API_KEY']]);
} else {
    echo json_encode(['error' => 'API key no configurada']);
}
// Fin del script