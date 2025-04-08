document.querySelector("form").addEventListener("submit", function (e) {
  const email = document.getElementById("email").value;
  const password = document.getElementById("password").value;

  if (!email.includes("@")) {
    alert("Por favor, ingresa un email válido.");
    e.preventDefault();
  }

  if (password.length < 6) {
    alert("La contraseña debe tener al menos 6 caracteres.");
    e.preventDefault();
  }
});
