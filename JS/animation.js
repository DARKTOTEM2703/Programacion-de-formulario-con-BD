// Inicialización de AOS (Animate On Scroll)
document.addEventListener("DOMContentLoaded", function () {
  // Configuración de AOS
  AOS.init({
    duration: 1000,
    once: false,
    mirror: true,
    offset: 120,
    easing: "ease-in-out",
  });

  // Inicializar animaciones personalizadas
  initScrollAnimations();
  initNavbarAnimations();
  initHeroAnimations();
  initServicesAnimations();
  initPaqueteriaAnimations();
  initGaleriaAnimations();
  initAboutAnimations();
});

// Animaciones para elementos al hacer scroll
function initScrollAnimations() {
  // Aplicar animaciones a elementos que no tienen atributos AOS
  gsap.utils.toArray(".service-card").forEach((card, i) => {
    gsap.from(card, {
      scrollTrigger: {
        trigger: card,
        start: "top 80%",
        toggleActions: "play none none reverse",
      },
      y: 100,
      opacity: 0,
      duration: 0.8,
      delay: i * 0.1,
    });
  });

  // Animación para la sección "Acerca de"
  gsap.from("#acerca-de .section-title", {
    scrollTrigger: {
      trigger: "#acerca-de",
      start: "top 70%",
    },
    opacity: 0,
    x: -50,
    duration: 1,
  });
}

// Animaciones para la barra de navegación
function initNavbarAnimations() {
  const navbar = document.querySelector(".navbar");

  window.addEventListener("scroll", () => {
    if (window.scrollY > 100) {
      navbar.classList.add("navbar-scrolled");
    } else {
      navbar.classList.remove("navbar-scrolled");
    }
  });

  // Efecto de entrada al cargar la página
  gsap.from(".navbar", {
    y: -100,
    opacity: 0,
    duration: 1,
    ease: "power3.out",
  });

  // Efecto de resaltado para elementos del menú
  const navLinks = document.querySelectorAll(".nav-link");
  navLinks.forEach((link) => {
    link.addEventListener("mouseenter", function () {
      gsap.to(this, { scale: 1.1, color: "#ffe600", duration: 0.3 });
    });

    link.addEventListener("mouseleave", function () {
      gsap.to(this, { scale: 1, color: "", duration: 0.3 });
    });
  });
}

// Animaciones para la sección Hero
function initHeroAnimations() {
  const tl = gsap.timeline({ defaults: { ease: "power3.out" } });

  tl.from(".hero-overlay", { opacity: 0, duration: 1.5 })
    .from(".hero-content h1", { y: 100, opacity: 0, duration: 1 }, "-=1")
    .from(".hero-content p", { y: 50, opacity: 0, duration: 0.8 }, "-=0.6")
    .from(
      ".hero-content .btn",
      {
        y: 30,
        opacity: 0,
        duration: 0.5,
        onComplete: () => {
          // Añadir pulsación al botón
          gsap.to(".hero-content .btn", {
            scale: 1.05,
            repeat: -1,
            yoyo: true,
            duration: 1.2,
            ease: "sine.inOut",
          });
        },
      },
      "-=0.4"
    );
}

// Animaciones para la sección de servicios
function initServicesAnimations() {
  const serviceCards = document.querySelectorAll(".service-card");

  serviceCards.forEach((card) => {
    // Efecto hover
    card.addEventListener("mouseenter", () => {
      gsap.to(card, {
        y: -15,
        boxShadow: "0 15px 30px rgba(255, 230, 0, 0.3)",
        duration: 0.3,
      });
      gsap.to(card.querySelector(".service-icon i"), {
        rotation: 15,
        scale: 1.2,
        color: "#ffe600",
        duration: 0.5,
        ease: "elastic.out(1.2, 0.5)",
      });
    });

    card.addEventListener("mouseleave", () => {
      gsap.to(card, {
        y: 0,
        boxShadow: "0 5px 15px rgba(0, 0, 0, 0.1)",
        duration: 0.3,
      });
      gsap.to(card.querySelector(".service-icon i"), {
        rotation: 0,
        scale: 1,
        color: "#ffe600",
        duration: 0.5,
      });
    });
  });
}

// Animaciones para la sección de paquetería
function initPaqueteriaAnimations() {
  const paqueteriaSection = document.getElementById("paqueteria");
  if (!paqueteriaSection) return;

  gsap.from("#paqueteria .section-title", {
    scrollTrigger: {
      trigger: "#paqueteria",
      start: "top 70%",
    },
    opacity: 0,
    y: 50,
    duration: 1,
  });

  gsap.from("#paqueteria .lead", {
    scrollTrigger: {
      trigger: "#paqueteria",
      start: "top 70%",
    },
    opacity: 0,
    y: 30,
    duration: 0.8,
    delay: 0.3,
  });

  gsap.from("#paqueteria img", {
    scrollTrigger: {
      trigger: "#paqueteria",
      start: "top 60%",
    },
    opacity: 0,
    scale: 0.8,
    duration: 1,
    delay: 0.5,
  });

  // Animación para los elementos de la lista
  gsap.utils.toArray(".paqueteria-list li").forEach((item, i) => {
    gsap.from(item, {
      scrollTrigger: {
        trigger: item,
        start: "top 80%",
      },
      x: -50,
      opacity: 0,
      duration: 0.8,
      delay: 0.7 + i * 0.2,
    });
  });
}

// Animaciones para la galería
function initGaleriaAnimations() {
  const galeriaSection = document.getElementById("galeria-reparaciones");
  if (!galeriaSection) return;

  gsap.from("#galeria-reparaciones .section-title", {
    scrollTrigger: {
      trigger: "#galeria-reparaciones",
      start: "top 70%",
    },
    opacity: 0,
    y: 50,
    duration: 0.8,
  });

  gsap.from("#galeria-reparaciones .carousel", {
    scrollTrigger: {
      trigger: "#galeria-reparaciones .carousel",
      start: "top 70%",
    },
    opacity: 0,
    y: 50,
    duration: 1,
    delay: 0.3,
  });
}

// Animaciones para la sección Acerca de
function initAboutAnimations() {
  gsap.utils.toArray("#acerca-de h4").forEach((heading, i) => {
    gsap.from(heading, {
      scrollTrigger: {
        trigger: heading,
        start: "top 80%",
      },
      opacity: 0,
      x: -30,
      duration: 0.6,
      delay: i * 0.2,
    });
  });

  gsap.from("#acerca-de .valores-container", {
    scrollTrigger: {
      trigger: "#acerca-de .valores-container",
      start: "top 70%",
    },
    opacity: 0,
    y: 50,
    duration: 0.8,
  });

  // Animación para los valores
  gsap.utils.toArray(".valor-item").forEach((item, i) => {
    gsap.from(item, {
      scrollTrigger: {
        trigger: item,
        start: "top 85%",
      },
      opacity: 0,
      y: 30,
      duration: 0.6,
      delay: 0.5 + i * 0.15,
    });
  });
}

// Botón "Volver arriba" con animación mejorada
window.addEventListener("scroll", function () {
  if (window.scrollY > 500) {
    if (!document.getElementById("backToTop")) {
      const backToTop = document.createElement("button");
      backToTop.id = "backToTop";
      backToTop.innerHTML = '<i class="fas fa-arrow-up"></i>';
      backToTop.className = "back-to-top";
      backToTop.addEventListener("click", () => {
        gsap.to(window, { duration: 1.5, scrollTo: 0, ease: "power4.inOut" });
      });
      document.body.appendChild(backToTop);

      // Animar entrada
      gsap.from(backToTop, { opacity: 0, y: 50, duration: 0.5 });
    }
  } else {
    const backToTop = document.getElementById("backToTop");
    if (backToTop) {
      gsap.to(backToTop, {
        opacity: 0,
        y: 50,
        duration: 0.5,
        onComplete: () => {
          if (backToTop.parentNode) {
            backToTop.parentNode.removeChild(backToTop);
          }
        },
      });
    }
  }
});
// Garantiza que AOS se inicialice cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", function () {
  // Inicializar AOS con opciones básicas
  AOS.init({
    duration: 1000,
    once: false,
    mirror: true,
    offset: 50,
    easing: "ease-in-out",
    disable: "mobile", // Habilita en móviles también
  });

  // Animación manual para elementos del hero (respaldo)
  setTimeout(function () {
    // Verificar si los elementos ya fueron animados por AOS
    const heroTitle = document.getElementById("heroTitle");
    const heroText = document.getElementById("heroText");
    const heroButton = document.getElementById("heroButton");

    if (heroTitle && !heroTitle.classList.contains("aos-animate")) {
      heroTitle.classList.add("aos-animate");
    }

    if (heroText && !heroText.classList.contains("aos-animate")) {
      heroText.classList.add("aos-animate");
    }

    if (heroButton && !heroButton.classList.contains("aos-animate")) {
      heroButton.classList.add("aos-animate");

      // Añadir pulsación al botón
      gsap.to(heroButton, {
        scale: 1.05,
        repeat: -1,
        yoyo: true,
        duration: 1.2,
        ease: "sine.inOut",
      });
    }
  }, 300);
});

// También activar en caso de scroll inmediato
window.addEventListener("scroll", function () {
  if (typeof AOS !== "undefined") {
    AOS.refresh();
  }
});
