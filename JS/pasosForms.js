// Control de pasos del formulario
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("shipping-form");
  const progressBar = document.querySelector(".progress-bar");
  const sections = document.querySelectorAll(".form-section");
  const nextButtons = document.querySelectorAll(".next-step");
  const prevButtons = document.querySelectorAll(".prev-step");
  const totalSteps = sections.length;

  // Controlar botones "Siguiente"
  nextButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const currentSection = button.closest(".form-section");
      const currentStep = parseInt(currentSection.dataset.step);
      const nextStep = currentStep + 1;

      // Validar campos requeridos en la sección actual
      const requiredFields = currentSection.querySelectorAll(
        "input[required], select[required], textarea[required]"
      );
      let isValid = true;

      requiredFields.forEach((field) => {
        if (!field.value.trim()) {
          field.classList.add("is-invalid");
          isValid = false;
        } else {
          field.classList.remove("is-invalid");
        }
      });

      if (!isValid) {
        return;
      }

      // Avanzar al siguiente paso
      currentSection.classList.add("d-none");
      const nextSection = document.querySelector(
        `.form-section[data-step="${nextStep}"]`
      );
      if (nextSection) {
        nextSection.classList.remove("d-none");
        const progress = (nextStep / totalSteps) * 100;
        progressBar.style.width = `${progress}%`;
        progressBar.setAttribute("aria-valuenow", progress);
      }
    });
  });

  // Controlar botones "Anterior"
  prevButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const currentSection = button.closest(".form-section");
      const currentStep = parseInt(currentSection.dataset.step);
      const prevStep = currentStep - 1;

      currentSection.classList.add("d-none");
      const prevSection = document.querySelector(
        `.form-section[data-step="${prevStep}"]`
      );
      if (prevSection) {
        prevSection.classList.remove("d-none");
        const progress = (prevStep / totalSteps) * 100;
        progressBar.style.width = `${progress}%`;
        progressBar.setAttribute("aria-valuenow", progress);
      }
    });
  });
});
