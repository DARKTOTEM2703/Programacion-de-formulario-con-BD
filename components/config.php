<?php
// filepath: c:\xampp\htdocs\Programacion-de-formulario-con-BD\components\config.php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
