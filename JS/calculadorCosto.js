async function generarEnlacePago(costo) {
  try {
    const response = await fetch("../api/crear_enlace_pago.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ amount: costo }),
    });

    const data = await response.json();

    if (data.success) {
      // Mostrar el enlace de pago al usuario
      const linkContainer = document.getElementById("payment-link-container");
      linkContainer.innerHTML = `
        <p>El costo total es <strong>$${costo.toFixed(2)} MXN</strong>.</p>
        <a href="${
          data.payment_url
        }" class="btn btn-success" target="_blank">Pagar con Stripe</a>
      `;
      linkContainer.style.display = "block";
    } else {
      alert("Error al generar el enlace de pago: " + data.message);
    }
  } catch (error) {
    console.error("Error al generar el enlace de pago:", error);
    alert("Ocurrió un error al generar el enlace de pago. Intenta nuevamente.");
  }
}

function construirDireccion(tipo) {
  // tipo puede ser "origin_" o "destination_"
  const street = document.getElementById(`${tipo}street`).value.trim();
  const number = document.getElementById(`${tipo}number`).value.trim();
  const colony = document.getElementById(`${tipo}colony`).value.trim();
  const postalCode = document.getElementById(`${tipo}postal_code`).value.trim();
  const city = document.getElementById(`${tipo}city`).value.trim();
  const state = document.getElementById(`${tipo}state`).value.trim();
  const country = document.getElementById(`${tipo}country`).value.trim();

  return `${street} ${number}, ${colony}, ${postalCode} ${city}, ${state}, ${country}`;
}

async function calcularCosto() {
  const origin = construirDireccion("origin_"); // Construye la dirección de origen
  const destination = construirDireccion("destination_"); // Construye la dirección de destino

  if (!origin || !destination) {
    alert("Por favor, completa todos los campos requeridos.");
    return;
  }

  try {
    const originCoords = await obtenerCoordenadas(origin);
    const destinationCoords = await obtenerCoordenadas(destination);

    if (!originCoords || !destinationCoords) {
      alert(
        "No se pudieron obtener las coordenadas de las direcciones ingresadas. Por favor, verifica que las direcciones sean válidas y completas."
      );
      return;
    }

    const distancia = await calcularDistancia(originCoords, destinationCoords);

    if (!distancia) {
      alert("No se pudo calcular la distancia entre las direcciones.");
      return;
    }

    const costo = calcularCostoEnvio(
      distancia,
      weight.value,
      packageType.value
    );
    document.getElementById("calculated_cost").value = `$${costo.toFixed(
      2
    )} MXN`;
    document.getElementById("hidden_calculated_cost").value = costo.toFixed(2);

    await generarEnlacePago(costo);
  } catch (error) {
    console.error("Error al calcular el costo:", error);
    alert(
      "Ocurrió un error al calcular el costo. Por favor, intenta nuevamente."
    );
  }
}

function normalizarDireccion(direccion) {
  return direccion
    .replace(/C\./g, "Calle") // Reemplaza "C." por "Calle"
    .replace(/#/g, "") // Elimina el símbolo "#"
    .replace(/x/g, "por") // Reemplaza "x" por "por"
    .replace(/y/g, "y") // Asegura que "y" esté bien formateado
    .replace(/\s+/g, " ") // Elimina espacios extra
    .trim(); // Elimina espacios al inicio y al final
}

async function obtenerCoordenadas(direccion) {
  direccion = normalizarDireccion(direccion);

  try {
    // OPCIÓN 1: API KEY DIRECTA (más rápido pero menos seguro)
    const apiKey = "pk.248ca72ebff86daef81bcdc801c80c10"; // API key desde tu archivo .env

    // OPCIÓN 2: Obtener del servidor (si prefieres esta opción, descomentar)
    // const keyResponse = await fetch("../api/get_api_key.php");
    // if (!keyResponse.ok) throw new Error("Error al obtener API key del servidor");
    // const keyData = await keyResponse.json();
    // if (keyData.error) throw new Error(`Error al obtener API key: ${keyData.error}`);
    // const apiKey = keyData.key;

    // Usar LocationIQ con la API key
    const url = `https://us1.locationiq.com/v1/search.php?key=${apiKey}&q=${encodeURIComponent(
      direccion
    )}&format=json`;

    console.log("Solicitando coordenadas para:", direccion);

    // Añadir método CORS proxy para evitar problemas de CORS
    const response = await fetch(url, {
      mode: "cors",
      headers: {
        Accept: "application/json",
      },
    });

    // Verificar si la respuesta es exitosa
    if (!response.ok) {
      const errorText = await response.text();
      throw new Error(
        `Error API LocationIQ: ${response.status} - ${errorText}`
      );
    }

    const data = await response.json();
    console.log("Respuesta de la API:", data);

    if (data && data.length > 0) {
      return {
        lat: data[0].lat,
        lon: data[0].lon,
      };
    } else {
      console.error(
        "No se encontraron resultados para la dirección:",
        direccion
      );
    }
  } catch (error) {
    console.error("Error al obtener coordenadas:", error);
    alert(`Error al obtener coordenadas: ${error.message}`);
  }

  return null;
}

async function calcularDistancia(originCoords, destinationCoords) {
  const url = `https://router.project-osrm.org/route/v1/driving/${originCoords.lon},${originCoords.lat};${destinationCoords.lon},${destinationCoords.lat}?overview=false`;
  const response = await fetch(url);
  const data = await response.json();

  if (data.routes && data.routes.length > 0) {
    return data.routes[0].distance / 1000; // Convertir metros a kilómetros
  }

  return null;
}

function calcularCostoEnvio(distancia, peso, tipoPaquete) {
  const tarifaBase = 50; // Costo base en MXN
  const costoPorKm = 10; // Costo por kilómetro
  const costoPorKg = 5; // Costo por kilogramo

  let factorPaquete = 1.0;
  switch (tipoPaquete) {
    case "documento":
      factorPaquete = 1.0;
      break;
    case "paquete_pequeno":
      factorPaquete = 1.2;
      break;
    case "paquete_mediano":
      factorPaquete = 1.5;
      break;
    case "paquete_grande":
      factorPaquete = 2.0;
      break;
    case "carga_voluminosa":
      factorPaquete = 3.0;
      break;
  }

  return (
    tarifaBase + distancia * costoPorKm + peso * costoPorKg * factorPaquete
  );
}

// Función para cálculo simple (sin distancia)
function calcularCostoSimple(peso, tipoPaquete) {
  const tarifaBase = 50; // Costo base en MXN
  const costoPorKg = 15; // Costo por kilogramo (más alto para compensar la falta de distancia)

  let factorPaquete = 1.0;
  switch (tipoPaquete) {
    case "documento":
      factorPaquete = 1.0;
      break;
    case "paquete_pequeno":
      factorPaquete = 1.2;
      break;
    case "paquete_mediano":
      factorPaquete = 1.5;
      break;
    case "paquete_grande":
      factorPaquete = 2.0;
      break;
    case "carga_voluminosa":
      factorPaquete = 3.0;
      break;
  }

  return tarifaBase + peso * costoPorKg * factorPaquete;
}

// Variables globales
let costoCalculado = false;
let costoBase = 0;

// Esperar a que el DOM esté listo
document.addEventListener("DOMContentLoaded", function () {
  // Referencias a elementos del DOM
  const btnCalcularCosto = document.getElementById("btn-calcular-costo");
  const btnCalcularSiguiente = document.getElementById("calcular-y-siguiente");
  const nextStepBtn = document.querySelector(
    '.form-section[data-step="2"] .next-step'
  );
  const seguroCheckbox = document.getElementById("insurance");
  const urgenteCheckbox = document.getElementById("urgent");
  const resultadoCalculo = document.getElementById("resultado-calculo");

  // Evento para el botón de calcular costo
  if (btnCalcularCosto) {
    btnCalcularCosto.addEventListener("click", async function () {
      await realizarCalculoCosto(resultadoCalculo);
    });
  }

  // Evento para el botón de siguiente en el paso 2
  if (nextStepBtn) {
    nextStepBtn.addEventListener("click", function (e) {
      if (!costoCalculado) {
        e.preventDefault(); // Detener la navegación
        realizarCalculoCosto(resultadoCalculo).then(() => {
          if (costoCalculado) {
            // Navegar al siguiente paso
            const paso2 = document.querySelector(
              '.form-section[data-step="2"]'
            );
            const paso3 = document.querySelector(
              '.form-section[data-step="3"]'
            );

            if (paso2) paso2.classList.add("d-none");
            if (paso3) paso3.classList.remove("d-none");

            // Actualizar barra de progreso
            const progressBar = document.querySelector(".progress-bar");
            if (progressBar) {
              progressBar.style.width = "100%";
              progressBar.setAttribute("aria-valuenow", "100");
            }

            // Mostrar el costo en el paso 3
            const costoTotalField = document.getElementById("calculated_cost");
            if (costoTotalField) {
              costoTotalField.value = `$${costoBase.toFixed(2)} MXN`;
            }
          }
        });
      }
    });
  }

  // Eventos para recalcular cuando cambien los checkbox
  if (seguroCheckbox) {
    seguroCheckbox.addEventListener("change", actualizarCostoMostrado);
  }

  if (urgenteCheckbox) {
    urgenteCheckbox.addEventListener("change", actualizarCostoMostrado);
  }
});

// Función central para realizar el cálculo de costo
async function realizarCalculoCosto(resultadoCalculo) {
  try {
    // Mostrar indicador de cálculo
    resultadoCalculo.classList.remove("d-none");
    resultadoCalculo.classList.add("alert-info");
    resultadoCalculo.innerHTML =
      '<i class="bi bi-hourglass-split me-2"></i>Calculando distancia y costo...';

    // Obtener direcciones
    const origin = construirDireccion("origin_");
    const destination = construirDireccion("destination_");

    if (!origin || !destination) {
      mostrarError(
        resultadoCalculo,
        "Por favor completa todas las direcciones."
      );
      return false;
    }

    // Obtener peso y tipo de paquete
    const peso = document.getElementById("weight")?.value || "1";
    const tipoPaquete =
      document.getElementById("package_type")?.value || "paquete_pequeno";

    if (!tipoPaquete) {
      mostrarError(
        resultadoCalculo,
        "Por favor selecciona el tipo de paquete."
      );
      return false;
    }

    // Declarar variable distancia aquí para que sea accesible en todo el ámbito
    let distancia = 0;

    try {
      // Obtener coordenadas
      const originCoords = await obtenerCoordenadas(origin);
      const destinationCoords = await obtenerCoordenadas(destination);

      if (!originCoords || !destinationCoords) {
        // Si falla la geolocalización, usar el cálculo simple
        costoBase = calcularCostoSimple(parseFloat(peso), tipoPaquete);
        mostrarResultadoSimple(resultadoCalculo, parseFloat(peso), tipoPaquete);
      } else {
        // Calcular distancia
        distancia = await calcularDistancia(
          originCoords,
          destinationCoords
        );

        if (!distancia) {
          // Si falla el cálculo de distancia, usar cálculo simple
          costoBase = calcularCostoSimple(parseFloat(peso), tipoPaquete);
          mostrarResultadoSimple(
            resultadoCalculo,
            parseFloat(peso),
            tipoPaquete
          );
        } else {
          // Cálculo completo con distancia
          costoBase = calcularCostoEnvio(
            distancia,
            parseFloat(peso),
            tipoPaquete
          );
          mostrarResultadoCompleto(
            resultadoCalculo,
            distancia,
            parseFloat(peso),
            tipoPaquete
          );
        }
      }

      // Ahora distancia es accesible aquí
      costoBase = distancia > 0
        ? calcularCostoEnvio(distancia, parseFloat(peso), tipoPaquete)
        : calcularCostoSimple(parseFloat(peso), tipoPaquete);

      const hiddenCost = document.getElementById("hidden_calculated_cost");
      if (hiddenCost) {
        hiddenCost.value = costoBase.toFixed(2);
        console.log("Costo calculado y guardado:", costoBase.toFixed(2));
      }

      // Marcar como calculado
      costoCalculado = true;
      return true;
    } catch (error) {
      console.error("Error en el cálculo:", error);

      // Plan de contingencia: usar cálculo simple si fallan las APIs
      costoBase = calcularCostoSimple(parseFloat(peso), tipoPaquete);
      mostrarResultadoSimple(resultadoCalculo, parseFloat(peso), tipoPaquete);

      costoCalculado = true;
      return true;
    }
  } catch (error) {
    console.error("Error general:", error);
    mostrarError(
      resultadoCalculo,
      "Ocurrió un error al calcular. Intenta de nuevo."
    );
    return false;
  }
}

// Función para mostrar errores
function mostrarError(elemento, mensaje) {
  elemento.classList.remove("alert-info", "alert-success");
  elemento.classList.add("alert-danger");
  elemento.innerHTML = `<i class="bi bi-exclamation-triangle me-2"></i>${mensaje}`;
  costoCalculado = false;
}

// Función para mostrar resultado simple (sin distancia)
function mostrarResultadoSimple(elemento, peso, tipoPaquete) {
  elemento.classList.remove("alert-info", "alert-danger");
  elemento.classList.add("alert-success");
  elemento.innerHTML = `
    <i class="bi bi-check-circle me-2"></i>
    <strong>Cálculo completado:</strong><br>
    Peso: ${peso.toFixed(2)} kg<br>
    Tipo: ${formatearTipoPaquete(tipoPaquete)}<br>
    Costo base: $${costoBase.toFixed(2)} MXN<br>
    <small>Haz clic en "Siguiente" para continuar.</small>
  `;
}

// Función para mostrar resultado completo (con distancia)
function mostrarResultadoCompleto(elemento, distancia, peso, tipoPaquete) {
  elemento.classList.remove("alert-info", "alert-danger");
  elemento.classList.add("alert-success");
  elemento.innerHTML = `
    <i class="bi bi-check-circle me-2"></i>
    <strong>Cálculo completado:</strong><br>
    Distancia: ${distancia.toFixed(2)} km<br>
    Peso: ${peso.toFixed(2)} kg<br>
    Tipo: ${formatearTipoPaquete(tipoPaquete)}<br>
    Costo base: $${costoBase.toFixed(2)} MXN<br>
    <small>Haz clic en "Siguiente" para continuar.</small>
  `;
}

// Función para formatear el tipo de paquete
function formatearTipoPaquete(tipo) {
  const formatos = {
    documento: "Documento",
    paquete_pequeno: "Paquete pequeño",
    paquete_mediano: "Paquete mediano",
    paquete_grande: "Paquete grande",
    carga_voluminosa: "Carga voluminosa",
  };
  return formatos[tipo] || tipo;
}

// Función para actualizar el costo mostrado
function actualizarCostoMostrado() {
  // Recuperar el costo base del cálculo (usar la variable global)
  let costoTotal = costoBase;

  // Recuperar checkboxes
  const seguro = document.getElementById("insurance");
  const urgente = document.getElementById("urgent");

  // Agregar costo de seguro si está seleccionado
  if (seguro && seguro.checked) {
    const valorDeclaradoField = document.getElementById("value");
    let valorDeclarado = 0;

    // Si el campo existe, obtener su valor
    if (valorDeclaradoField) {
      valorDeclarado = parseFloat(valorDeclaradoField.value) || 0;
    }

    const costoSeguro = valorDeclarado * 0.05; // 5% del valor declarado
    costoTotal += costoSeguro;
  }

  // Agregar costo por servicio urgente
  if (urgente && urgente.checked) {
    costoTotal *= 1.25; // 25% adicional por servicio urgente
  }

  // Actualizar el campo visible
  const calculatedCostField = document.getElementById("calculated_cost");
  if (calculatedCostField) {
    calculatedCostField.value = `$${costoTotal.toFixed(2)} MXN`;
  }

  // Actualizar TODOS los campos hidden de costos
  const hiddenCalculatedCostField = document.getElementById(
    "hidden_calculated_cost"
  );
  if (hiddenCalculatedCostField) {
    hiddenCalculatedCostField.value = costoTotal.toFixed(2);
    console.log("Costo actualizado:", costoTotal.toFixed(2)); // Para depuración
  }
}

// Resto del código existente (mantener las funciones como obtenerCoordenadas, calcularDistancia, etc.)
