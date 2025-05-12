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

function construirDireccion() {
  const street = document.getElementById("street").value.trim();
  const number = document.getElementById("number").value.trim();
  const colony = document.getElementById("colony").value.trim();
  const postalCode = document.getElementById("postal_code").value.trim();
  const city = document.getElementById("city").value.trim();
  const state = document.getElementById("state").value.trim();

  return `${street} ${number}, ${colony}, ${postalCode} ${city}, ${state}`;
}

async function calcularCosto() {
  const origin = construirDireccion(); // Construye la dirección de origen
  const destination = construirDireccion(); // Construye la dirección de destino

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
  const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(
    direccion
  )}`;
  try {
    console.log("Solicitando coordenadas para:", direccion);
    console.log("URL generada:", url);

    const response = await fetch(url);
    const data = await response.json();

    console.log("Respuesta de la API:", data);

    if (data.length > 0) {
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
