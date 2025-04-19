<?php

/**
 * Configuración global del panel administrativo
 */

// Título del panel
define('ADMIN_TITLE', 'MENDEZ Transportes - Panel Administrativo');

// URLs base
define('ADMIN_URL', '/Programacion-de-formulario-con-BD/admin');
define('BASE_URL', '/Programacion-de-formulario-con-BD');

// Rutas de archivos
define('LOGS_DIR', dirname(__DIR__, 2) . '/logs');
define('UPLOADS_DIR', dirname(__DIR__, 2) . '/uploads');

// Valores por defecto
define('ITEMS_PER_PAGE', 20);
define('CSRF_TIMEOUT', 3600); // 1 hora

// Información de la empresa
define('COMPANY_NAME', 'MENDEZ Transportes');
define('COMPANY_EMAIL', 'info@mendez-transportes.com');
define('COMPANY_PHONE', '+52 123 456 7890');

// Asegurar que exista el directorio de logs
if (!file_exists(LOGS_DIR)) {
    mkdir(LOGS_DIR, 0755, true);
}

// Cargar configuración específica del entorno si existe
$env_config = dirname(__DIR__) . '/config.local.php';
if (file_exists($env_config)) {
    include $env_config;
}

// Configuración de reportes de errores
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores al usuario
ini_set('log_errors', 1);
ini_set('error_log', LOGS_DIR . '/admin_errors.log');
