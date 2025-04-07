const prefersDarkScheme = window.matchMedia("(prefers-color-scheme: dark)");

// Aplicar el modo oscuro automáticamente si el sistema lo prefiere
if (prefersDarkScheme.matches) {
  document.body.classList.add("bg-dark", "text-light");
  document.querySelector(".card").classList.add("bg-dark", "text-light");
}

// Alternar modo oscuro manualmente
const toggle = document.getElementById("darkModeToggle");
toggle.addEventListener("click", () => {
  document.body.classList.toggle("bg-dark");
  document.body.classList.toggle("text-light");
  const card = document.querySelector(".card");
  card.classList.toggle("bg-dark");
  card.classList.toggle("text-light");
  toggle.textContent = document.body.classList.contains("bg-dark")
    ? "Modo Claro"
    : "Modo Oscuro";
});
