document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector("form");
  const inputs = form.querySelectorAll("input[required], textarea[required]");

  // Validación en tiempo real
  inputs.forEach((input) => {
    input.addEventListener("blur", function () {
      validateInput(this);
    });

    input.addEventListener("input", function () {
      if (this.classList.contains("is-invalid")) {
        validateInput(this);
      }
    });
  });

  // Validación de email
  const emailInput = document.getElementById("email");
  if (emailInput) {
    emailInput.addEventListener("blur", function () {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(this.value) && this.value.trim() !== "") {
        setInvalid(this, "Por favor ingresa un correo electrónico válido");
      }
    });
  }

  // Validación de teléfono
  const phoneInput = document.getElementById("phone");
  if (phoneInput) {
    phoneInput.addEventListener("blur", function () {
      const phoneRegex = /^[0-9]{10}$/;
      if (
        !phoneRegex.test(this.value.replace(/\D/g, "")) &&
        this.value.trim() !== ""
      ) {
        setInvalid(this, "El número debe tener 10 dígitos");
      }
    });

    // Formato automático
    phoneInput.addEventListener("input", function () {
      let number = this.value.replace(/\D/g, "");
      if (number.length > 0) {
        if (number.length <= 3) {
          this.value = number;
        } else if (number.length <= 6) {
          this.value = number.slice(0, 3) + "-" + number.slice(3);
        } else {
          this.value =
            number.slice(0, 3) +
            "-" +
            number.slice(3, 6) +
            "-" +
            number.slice(6, 10);
        }
      }
    });
  }

  function validateInput(input) {
    if (input.value.trim() === "" && input.hasAttribute("required")) {
      setInvalid(input, "Este campo es obligatorio");
    } else {
      setValid(input);
    }
  }

  function setInvalid(input, message) {
    input.classList.add("is-invalid");
    input.classList.remove("is-valid");

    let feedbackDiv = input.nextElementSibling;
    if (!feedbackDiv || !feedbackDiv.classList.contains("invalid-feedback")) {
      feedbackDiv = document.createElement("div");
      feedbackDiv.className = "invalid-feedback";
      input.parentNode.insertBefore(feedbackDiv, input.nextSibling);
    }
    feedbackDiv.textContent = message;
  }

  function setValid(input) {
    input.classList.remove("is-invalid");
    input.classList.add("is-valid");

    const feedbackDiv = input.nextElementSibling;
    if (feedbackDiv && feedbackDiv.classList.contains("invalid-feedback")) {
      feedbackDiv.textContent = "";
    }
  }

  // Validación al enviar
  form.addEventListener("submit", function (e) {
    let isValid = true;

    inputs.forEach((input) => {
      validateInput(input);
      if (input.classList.contains("is-invalid")) {
        isValid = false;
      }
    });

    if (!isValid) {
      e.preventDefault();
      document.querySelector(".is-invalid").focus();
    }
  });

  document.getElementById('shipping-form').addEventListener('submit', function(e) {
    // Verificar si el costo está establecido antes de enviar
    const hiddenCost = document.getElementById('hidden_calculated_cost');
    if (!hiddenCost || !hiddenCost.value || parseFloat(hiddenCost.value) <= 0) {
        e.preventDefault();
        alert('No se ha calculado el costo del envío. Por favor, vuelva al paso anterior y calcule el costo.');
        return false;
    }
    
    // Registrar el costo para depuración
    console.log('Enviando formulario con costo:', hiddenCost.value);
    return true;
});
});
