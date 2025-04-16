// Hacer que las alertas desaparezcan después de 5 segundos
document.addEventListener("DOMContentLoaded", function () {
  // Auto-ocultar alertas después de 5 segundos
  setTimeout(function () {
    const alerts = document.querySelectorAll(".alert");
    alerts.forEach(function (alert) {
      alert.style.transition = "opacity 1s ease";
      alert.style.opacity = "0";
      setTimeout(function () {
        alert.style.display = "none";
      }, 1000);
    });
  }, 5000);
});
