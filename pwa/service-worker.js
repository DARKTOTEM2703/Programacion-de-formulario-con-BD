// Nombre de la caché
const CACHE_NAME = "mendez-transportes-v1";

// Archivos a cachear para funcionamiento offline
const urlsToCache = [
  // Rutas de la aplicación
  "/Programacion-de-formulario-con-BD/pwa/",
  "/Programacion-de-formulario-con-BD/pwa/login.php",
  "/Programacion-de-formulario-con-BD/pwa/dashboard.php",
  "/Programacion-de-formulario-con-BD/pwa/escanear.php",
  "/Programacion-de-formulario-con-BD/pwa/mapa.php",
  "/Programacion-de-formulario-con-BD/pwa/perfil.php",
  "/Programacion-de-formulario-con-BD/pwa/offline.html",

  // Recursos estáticos
  "/Programacion-de-formulario-con-BD/pwa/assets/css/mobile.css",
  "/Programacion-de-formulario-con-BD/pwa/assets/css/offline.css",
  "/Programacion-de-formulario-con-BD/pwa/assets/icons/logo.png",
  "/Programacion-de-formulario-con-BD/pwa/pwa-init.js",

  // CDN recursos externos (estos pueden fallar si cambian o no están disponibles)
  "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css",
  "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css",
  "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js",
];

// Instalación del service worker - versión mejorada con manejo de errores
self.addEventListener("install", (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log("Caché abierta");

      // Método mejorado: cachear los archivos uno por uno para que si uno falla, los otros se sigan cacheando
      return Promise.all(
        urlsToCache.map((url) => {
          return cache.add(url).catch((error) => {
            console.warn(`No se pudo cachear: ${url}`, error);
            // Continuamos a pesar del error
            return Promise.resolve();
          });
        })
      );
    })
  );
});

// Estrategia de caché: Network first, fallback to cache
self.addEventListener("fetch", (event) => {
  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // Si tenemos respuesta exitosa de la red, la guardamos en caché
        if (response.ok) {
          const responseToCache = response.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, responseToCache);
          });
        }
        return response;
      })
      .catch(() => {
        // Si falla la red, intentamos servir desde caché
        return caches.match(event.request).then((cachedResponse) => {
          if (cachedResponse) {
            return cachedResponse;
          }

          // Si el recurso no está en caché y es una navegación, mostrar página offline
          if (event.request.mode === "navigate") {
            return caches.match(
              "/Programacion-de-formulario-con-BD/pwa/offline.html"
            );
          }

          // Para otros recursos que no estén en caché, retornar un error básico
          return new Response("", {
            status: 408,
            statusText: "Recurso no disponible sin conexión",
          });
        });
      })
  );
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
          return Promise.resolve();
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
  return new Promise((resolve) => {
    console.log("Sincronizando envíos pendientes...");
    resolve();
  });
}
