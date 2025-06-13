<?php
// Cargar variables de entorno desde .env para PHPMailer
function loadEnvVariables()
{
    $env_file = __DIR__ . '/../.env';

    if (file_exists($env_file)) {
        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '//') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Eliminar comillas si las hay
                if (preg_match('/^"(.+)"$/', $value, $matches)) {
                    $value = $matches[1];
                } elseif (preg_match("/^'(.+)'$/", $value, $matches)) {
                    $value = $matches[1];
                }

                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
        return true;
    }
    return false;
}

// Cargar las variables
loadEnvVariables();

// Función para obtener valores de entorno con fallback
function env($key, $default = null)
{
    return isset($_ENV[$key]) ? $_ENV[$key] : $default;
}