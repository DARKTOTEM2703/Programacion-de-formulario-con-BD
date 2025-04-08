document.querySelector("form").addEventListener("submit", function (e) {
  const email = document.getElementById("email").value;
  const phone = document.getElementById("phone").value;
  const value = document.getElementById("value").value;

  if (!email.includes("@")) {
    alert("Por favor, ingresa un email válido.");
    e.preventDefault();
  }

  if (isNaN(phone) || phone.length < 10) {
    alert("Por favor, ingresa un número de teléfono válido.");
    e.preventDefault();
  }

  if (isNaN(value) || value < 0) {
    alert("El valor de la mercancía debe ser un número positivo.");
    e.preventDefault();
  }
});
