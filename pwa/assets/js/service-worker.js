// Nombre de la caché
const CACHE_NAME = "repartidor-app-v1";

// Archivos a cachear para funcionamiento offline
const urlsToCache = [
  "/",
  "/login.php",
  "/offline.html",
  "/assets/css/mobile.css",
  "/assets/css/offline.css",
  "/assets/js/pwa-init.js",
  "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css",
  "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js",
  "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css",
  "/assets/icons/icon-192x192.png",
  "/assets/icons/icon-512x512.png",
];

// Instalación del service worker
self.addEventListener("install", (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log("Caché abierta");
      return cache.addAll(urlsToCache);
    })
  );
});

// Interceptar peticiones y servir desde caché cuando sea posible
self.addEventListener("fetch", (event) => {
  event.respondWith(fetch(event.request));
});

// Actualizar caché cuando hay una nueva versión del service worker
self.addEventListener("activate", (event) => {
  const cacheWhitelist = [CACHE_NAME];

  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Sincronizar datos cuando se recupera conexión
self.addEventListener("sync", (event) => {
  if (event.tag === "sync-envios") {
    event.waitUntil(syncEnvios());
  }
});

// Función para sincronizar datos pendientes
function syncEnvios() {
  return new Promise((resolve, reject) => {
    // Aquí se implementaría la sincronización con IndexedDB
    // donde guardaste los datos mientras estaba offline
    resolve();
  });
}
