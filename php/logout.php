<!-- filepath: c:\xampp\htdocs\Programacion de formulario con BD\logout.php -->
<?php
session_start();
session_destroy(); // Destruye todas las variables de sesión
header("Location: login.php"); // Redirige al formulario de login
exit();
?>