<?php
header('Content-Type: application/javascript');
// Cargar helper para obtener base_url (evita salida HTML)
require_once __DIR__ . '/../components/url_helper.php';
$base = base_url(); // ejemplo: https://mi-dominio.com/mi_proyecto
$cacheUrls = [
    $base . '/pwa/',
    $base . '/pwa/login.php',
    $base . '/pwa/dashboard.php',
    $base . '/pwa/escanear.php',
    $base . '/pwa/mapa.php',
    $base . '/pwa/perfil.php',
    $base . '/pwa/offline.html',
    $base . '/pwa/assets/css/mobile.css',
    $base . '/pwa/assets/css/offline.css',
    $base . '/pwa/assets/icons/logo.png',
    $base . '/pwa/pwa-init.js',
    // Agregar más rutas si es necesario
];
?>
const CACHE_NAME = 'mendez-transportes-v1';
const urlsToCache = <?php echo json_encode($cacheUrls, JSON_UNESCAPED_SLASHES); ?>;

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(urlsToCache))
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request).then(resp => {
      return resp || fetch(event.request);
    })
  );
});