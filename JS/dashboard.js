document.addEventListener("DOMContentLoaded", function () {
  // Toggle sidebar
  const toggleBtn = document.getElementById("toggleSidebar");
  const sidebar = document.getElementById("sidebar");
  const mainContent = document.getElementById("main-content");

  if (toggleBtn) {
    toggleBtn.addEventListener("click", function () {
      sidebar.classList.toggle("active");
      mainContent.classList.toggle("sidebar-active");
    });
  }

  // Gráfico de envíos - Obtenemos los datos del PHP
  const datosEnvios = document.getElementById("datosEnvios").dataset;

  // Inicialización del gráfico
  const ctx = document.getElementById("enviosChart").getContext("2d");
  new Chart(ctx, {
    type: "line",
    data: {
      labels: JSON.parse(datosEnvios.meses),
      datasets: [
        {
          label: "Envíos realizados",
          data: JSON.parse(datosEnvios.envios),
          backgroundColor: "rgba(0, 51, 102, 0.1)",
          borderColor: "#003366",
          borderWidth: 2,
          tension: 0.2,
          fill: true,
          pointBackgroundColor: "#003366",
          pointRadius: 4,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: "top",
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0,
          },
        },
      },
    },
  });
});
