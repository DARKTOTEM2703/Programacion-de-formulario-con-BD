document.addEventListener("DOMContentLoaded", function () {
  // Registrar el Service Worker
  if ("serviceWorker" in navigator) {
    navigator.serviceWorker
      .register("/Programacion-de-formulario-con-BD/pwa/service-worker.js") // Ruta corregida
      .then(function (registration) {
        console.log("Service Worker registrado con éxito:", registration);
      })
      .catch(function (error) {
        console.log("Error al registrar el Service Worker:", error);
      });
  }

  // Variables para la interfaz
  let deferredPrompt;
  const installButton = document.getElementById("pwaInstall");
  const installBanner = document.getElementById("pwaInstallBanner");

  // Ocultar botón/banner de instalación por defecto
  if (installButton) installButton.style.display = "none";
  if (installBanner) installBanner.style.display = "none";

  // Escuchar el evento de instalación
  window.addEventListener("beforeinstallprompt", (e) => {
    // Prevenir la aparición del diálogo predeterminado
    e.preventDefault();
    // Guardar el evento para usarlo después
    deferredPrompt = e;

    // Mostrar botón/banner de instalación
    if (installButton) installButton.style.display = "block";
    if (installBanner) installBanner.style.display = "flex";

    // Lógica para el botón de instalación
    if (installButton) {
      installButton.addEventListener("click", () => {
        // Ocultar interfaz de instalación
        if (installButton) installButton.style.display = "none";
        if (installBanner) installBanner.style.display = "none";

        // Mostrar el prompt de instalación
        deferredPrompt.prompt();

        // Esperar la elección del usuario
        deferredPrompt.userChoice.then((choiceResult) => {
          if (choiceResult.outcome === "accepted") {
            console.log("Usuario aceptó la instalación");
          } else {
            console.log("Usuario rechazó la instalación");
          }
          deferredPrompt = null;
        });
      });
    }

    // Cerrar banner sin instalar
    const closeBanner = document.getElementById("closeInstallBanner");
    if (closeBanner) {
      closeBanner.addEventListener("click", () => {
        if (installBanner) installBanner.style.display = "none";
      });
    }
  });

  // Detectar si la app ya está instalada
  window.addEventListener("appinstalled", () => {
    // Ocultar la interfaz de instalación
    if (installButton) installButton.style.display = "none";
    if (installBanner) installBanner.style.display = "none";
    deferredPrompt = null;
    console.log("PWA instalada exitosamente");
  });
});
