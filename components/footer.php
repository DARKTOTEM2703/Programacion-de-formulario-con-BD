<?php
// Detectar el protocolo (http o https)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';

// Usar HTTP_HOST que incluye el nombre del servidor y el puerto si existe
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

// Construir la URL base del proyecto de manera robusta
$project_folder = 'Programacion-de-formulario-con-BD';

// Construir la URL base completa
$base_url = $protocol . $host . '/' . $project_folder;

// IP actual del servidor para acceso directo por IP
$server_ip = $_SERVER['SERVER_ADDR'] ?? '';

// Si estamos en localhost, detectar la IP real para acceso desde otros dispositivos
if ($host === 'localhost' || $host === '127.0.0.1' || strpos($host, 'localhost:') === 0) {
    // Intentar obtener IP real para acceso desde la red local
    $possible_ip = getHostByName(getHostName());
    if ($possible_ip && $possible_ip !== '127.0.0.1') {
        $server_ip = $possible_ip;
    }
}

// URL alternativa para dispositivos móviles (cuando se accede desde la misma red WiFi)
$mobile_url = '';
if (!empty($server_ip)) {
    $mobile_url = $protocol . $server_ip;

    // Añadir puerto si no es el estándar
    if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443') {
        $mobile_url .= ':' . $_SERVER['SERVER_PORT'];
    }

    $mobile_url .= '/' . $project_folder;
}
?>
<link rel="stylesheet" href="<?php echo $base_url; ?>/css/footer.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
<footer class="footer">
    <div class="footer__container">
        <!-- Trabaja con nosotros -->
        <div class="footer__section">
            <h4>Trabaja con nosotros</h4>
            <ul class="footer__job-list">
                <li><a href="../php/careers/operator.php" target="_blank">Operador</a></li>
                <li>
                    <!-- Enlace principal usando la URL actual -->
                    <a href="<?php echo $base_url; ?>/pwa/login.php" target="_blank">Repartidor</a>
                    <p class="footer__job-info">¡Únete a nuestro equipo y crece profesionalmente!</p>
        </div>

        <!-- Contacto -->
        <div class="footer__section">
            <h4>Contacto</h4>
            <p>Email: <a href="mailto:Jafethgamboa27@gmail.com">Jafethgamboa27@gmail.com</a></p>
            <p>Teléfono: +52 123 456 7890</p>
            <p>Dirección: Calle Ejemplo #123, Ciudad, País</p>
        </div>
        <!-- Síguenos -->
        <div class="footer__section">
            <h4>Síguenos</h4>
            <div class="footer__social">
                <a href="https://facebook.com" target="_blank"><i class="fab fa-facebook-f"></i></a>
                <a href="https://twitter.com" target="_blank"><i class="fab fa-twitter"></i></a>
                <a href="https://instagram.com" target="_blank"><i class="fab fa-instagram"></i></a>
                <a href="https://linkedin.com" target="_blank"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </div>
        <!-- Mapa -->
        <div class="footer__section footer__map">
            <h4>Encuéntranos</h4>
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3153.835434509374!2d-122.4194154846819!3d37.77492927975959!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x80858064f0f0f0f%3A0x0!2zMzfCsDQ2JzI5LjgiTiAxMjLCsDI1JzA3LjkiVw!5e0!3m2!1ses!2smx!4v1681234567890!5m2!1ses!2smx"
                allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </div>

    <div class="footer__bottom">
        <p>&copy; 2025 Jafet Daniel Gamboa Baas. Todos los derechos reservados.</p>
    </div>
</footer>