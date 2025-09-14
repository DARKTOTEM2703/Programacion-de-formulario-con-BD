<?php

function base_url(): string {
    // Preferir variable de entorno (útil en producción)
    $envUrl = getenv('APP_URL');
    if ($envUrl && trim($envUrl) !== '') {
        return rtrim($envUrl, '/');
    }

    // Fallback: construir dinámicamente
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
    $port = $_SERVER['SERVER_PORT'] ?? '';
    // Evitar puerto estándar en la URL
    if (($scheme === 'http' && $port == 80) || ($scheme === 'https' && $port == 443)) {
        $port = '';
    } else if ($port) {
        $port = ':' . $port;
    }
    // Obtener carpeta del proyecto desde SCRIPT_NAME (soporta subcarpetas)
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $projectPath = rtrim(dirname($script), '/\\');
    // Si está en la raíz del host, dirname devuelve '.', evitar eso
    if ($projectPath === '.' || $projectPath === DIRECTORY_SEPARATOR) $projectPath = '';

    return $scheme . '://' . $host . $port . $projectPath;
}

function asset(string $path): string {
    return rtrim(base_url(), '/') . '/' . ltrim($path, '/');
}

function route(string $path): string {
    // Útil para construir enlaces a páginas (puede extenderse)
    return asset($path);
}
?>