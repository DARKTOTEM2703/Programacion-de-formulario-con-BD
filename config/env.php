<?php

// Cargar variables desde .env
$envFile = __DIR__ . '../.env';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorar comentarios
        if (strpos(trim($line), '//') === 0) {
            continue;
        }

        // Extraer variables de entorno
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Quitar comillas si existen
            if (preg_match('/^"(.*)"$/', $value, $matches)) {
                $value = $matches[1];
            }

            // Definir constante
            if (!defined($name)) {
                define($name, $value);
            }
        }
    }
}
