<?php
// filepath: c:\xampp\htdocs\Programacion-de-formulario-con-BD\config\stripe_config.php
require_once __DIR__ . '/load_env.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Cargar las variables de entorno
loadEnvVariables();

// Configurar Stripe con la clave secreta
$stripe_secret = env('STRIPE_SECRET_KEY', 'sk_test_TuClaveSecretaDeStripe');
\Stripe\Stripe::setApiKey($stripe_secret);

// Función auxiliar para obtener la clave pública (para el frontend)
function getStripePublishableKey()
{
    return env('STRIPE_PUBLISHABLE_KEY', 'pk_test_TuClavePublicaDeStripe');
}
