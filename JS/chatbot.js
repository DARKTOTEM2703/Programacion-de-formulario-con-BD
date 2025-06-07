document.addEventListener("DOMContentLoaded", function () {
  const chatbotToggle = document.querySelector(".chatbot-toggle");
  const chatbotBox = document.querySelector(".chatbot-box");
  const minimizeBtn = document.querySelector(".minimize-btn");
  const sendBtn = document.getElementById("sendBtn");
  const userInput = document.getElementById("userInput");
  const chatMessages = document.getElementById("chatMessages");
  const suggestionChips = document.querySelectorAll(".suggestion-chip");

  // Almacenar el historial de conversación
  let conversationHistory = [];

  // Mostrar/ocultar el chatbot
  chatbotToggle.addEventListener("click", function () {
    chatbotBox.style.display = "flex";
    this.style.display = "none";
    document.querySelector(".notification-badge").style.display = "none";
  });

  // Minimizar el chatbot
  minimizeBtn.addEventListener("click", function () {
    chatbotBox.style.display = "none";
    chatbotToggle.style.display = "flex";
  });

  // Manejar envío de mensajes
  function sendMessage() {
    const message = userInput.value.trim();
    if (message === "") return;

    // Añadir mensaje del usuario a la conversación
    addMessage(message, "user");
    userInput.value = "";

    // Guardar mensaje del usuario en historial
    conversationHistory.push({
      role: "user",
      content: message,
    });

    // Simular "escribiendo..."
    const typingIndicator = document.createElement("div");
    typingIndicator.className = "message bot-message";
    typingIndicator.id = "typingIndicator";
    typingIndicator.innerHTML = `
            <div class="message-content">
                <p>Escribiendo<span class="typing-dots">...</span></p>
            </div>
        `;
    chatMessages.appendChild(typingIndicator);
    chatMessages.scrollTop = chatMessages.scrollHeight;

    // Enviar a la API y procesar respuesta
    fetch("api/meta-ai.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        query: message,
        history: conversationHistory.slice(-6), // Limitar a las últimas 6 interacciones
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        // Eliminar indicador de escritura
        document.getElementById("typingIndicator").remove();

        // Guardar respuesta del bot en historial
        conversationHistory.push({
          role: "assistant",
          content: data.response,
        });

        // Añadir respuesta del bot
        const responseDiv = document.createElement("div");
        responseDiv.className = "message bot-message";

        // Construir HTML para sugerencias
        let suggestionsHTML = "";
        if (data.suggestions && data.suggestions.length > 0) {
          suggestionsHTML = '<div class="suggestion-chips">';
          data.suggestions.forEach((suggestion) => {
            suggestionsHTML += `<button class="suggestion-chip">${suggestion}</button>`;
          });
          suggestionsHTML += "</div>";
        }

        responseDiv.innerHTML = `
                <div class="message-content">
                    <p>${data.response}</p>
                    ${suggestionsHTML}
                </div>
            `;

        chatMessages.appendChild(responseDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;

        // Agregar evento a los nuevos chips de sugerencia
        responseDiv.querySelectorAll(".suggestion-chip").forEach((chip) => {
          chip.addEventListener("click", function () {
            const chipText = this.textContent;

            // Manejar acciones especiales
            if (
              chipText === "Iniciar sesión" ||
              chipText === "Registrarse" ||
              chipText === "Iniciar sesión para rastrear" ||
              chipText === "Registrarse para cotizar"
            ) {
              window.location.href = "php/login.php";
              return;
            }

            addMessage(chipText, "user");

            // Guardar mensaje del usuario en historial
            conversationHistory.push({
              role: "user",
              content: chipText,
            });

            // Simular "escribiendo..." nuevamente
            const typingIndicator = document.createElement("div");
            typingIndicator.className = "message bot-message";
            typingIndicator.id = "typingIndicator";
            typingIndicator.innerHTML = `
                        <div class="message-content">
                            <p>Escribiendo<span class="typing-dots">...</span></p>
                        </div>
                    `;
            chatMessages.appendChild(typingIndicator);
            chatMessages.scrollTop = chatMessages.scrollHeight;

            // Enviar a la API
            fetch("api/meta-ai.php", {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({
                query: chipText,
                history: conversationHistory.slice(-6),
              }),
            })
              .then((response) => response.json())
              .then((data) => {
                document.getElementById("typingIndicator").remove();

                conversationHistory.push({
                  role: "assistant",
                  content: data.response,
                });

                const responseDiv = document.createElement("div");
                responseDiv.className = "message bot-message";

                let suggestionsHTML = "";
                if (data.suggestions && data.suggestions.length > 0) {
                  suggestionsHTML = '<div class="suggestion-chips">';
                  data.suggestions.forEach((suggestion) => {
                    suggestionsHTML += `<button class="suggestion-chip">${suggestion}</button>`;
                  });
                  suggestionsHTML += "</div>";
                }

                responseDiv.innerHTML = `
                            <div class="message-content">
                                <p>${data.response}</p>
                                ${suggestionsHTML}
                            </div>
                        `;

                chatMessages.appendChild(responseDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;

                responseDiv
                  .querySelectorAll(".suggestion-chip")
                  .forEach((chip) => {
                    chip.addEventListener("click", function () {
                      const chipText = this.textContent;

                      if (
                        chipText === "Iniciar sesión" ||
                        chipText === "Registrarse" ||
                        chipText === "Iniciar sesión para rastrear" ||
                        chipText === "Registrarse para cotizar"
                      ) {
                        window.location.href = "php/login.php";
                        return;
                      }

                      addMessage(chipText, "user");
                      // Y aquí continua el proceso de forma similar
                    });
                  });
              })
              .catch((error) => {
                document.getElementById("typingIndicator").remove();
                console.error("Error:", error);
                addBotMessage(
                  "Lo siento, estoy teniendo problemas para procesar tu solicitud. Por favor, intenta más tarde."
                );
              });
          });
        });
      })
      .catch((error) => {
        document.getElementById("typingIndicator").remove();
        console.error("Error:", error);
        addBotMessage(
          "Lo siento, estoy teniendo problemas para procesar tu solicitud. Por favor, intenta más tarde."
        );
      });
  }

  // Añadir mensaje a la conversación
  function addMessage(content, sender) {
    const messageDiv = document.createElement("div");
    messageDiv.className = `message ${sender}-message`;

    messageDiv.innerHTML = `
            <div class="message-content">
                <p>${content}</p>
            </div>
        `;

    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  // Función auxiliar para añadir mensaje del bot
  function addBotMessage(content) {
    const messageDiv = document.createElement("div");
    messageDiv.className = "message bot-message";

    messageDiv.innerHTML = `
            <div class="message-content">
                <p>${content}</p>
            </div>
        `;

    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  // Agregar eventos a chips de sugerencia iniciales
  suggestionChips.forEach((chip) => {
    chip.addEventListener("click", function () {
      const chipText = this.textContent;
      addMessage(chipText, "user");

      conversationHistory.push({
        role: "user",
        content: chipText,
      });

      // Simular "escribiendo..."
      const typingIndicator = document.createElement("div");
      typingIndicator.className = "message bot-message";
      typingIndicator.id = "typingIndicator";
      typingIndicator.innerHTML = `
                <div class="message-content">
                    <p>Escribiendo<span class="typing-dots">...</span></p>
                </div>
            `;
      chatMessages.appendChild(typingIndicator);
      chatMessages.scrollTop = chatMessages.scrollHeight;

      // Enviar a la API
      fetch("api/meta-ai.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          query: chipText,
          history: conversationHistory,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          document.getElementById("typingIndicator").remove();

          conversationHistory.push({
            role: "assistant",
            content: data.response,
          });

          const responseDiv = document.createElement("div");
          responseDiv.className = "message bot-message";

          let suggestionsHTML = "";
          if (data.suggestions && data.suggestions.length > 0) {
            suggestionsHTML = '<div class="suggestion-chips">';
            data.suggestions.forEach((suggestion) => {
              suggestionsHTML += `<button class="suggestion-chip">${suggestion}</button>`;
            });
            suggestionsHTML += "</div>";
          }

          responseDiv.innerHTML = `
                    <div class="message-content">
                        <p>${data.response}</p>
                        ${suggestionsHTML}
                    </div>
                `;

          chatMessages.appendChild(responseDiv);
          chatMessages.scrollTop = chatMessages.scrollHeight;

          responseDiv.querySelectorAll(".suggestion-chip").forEach((chip) => {
            chip.addEventListener("click", function () {
              const chipText = this.textContent;

              if (
                chipText === "Iniciar sesión" ||
                chipText === "Registrarse" ||
                chipText === "Iniciar sesión para rastrear" ||
                chipText === "Registrarse para cotizar"
              ) {
                window.location.href = "php/login.php";
                return;
              }

              addMessage(chipText, "user");
              // Continuar proceso similar
            });
          });
        })
        .catch((error) => {
          document.getElementById("typingIndicator").remove();
          console.error("Error:", error);
          addBotMessage(
            "Lo siento, estoy teniendo problemas para procesar tu solicitud. Por favor, intenta más tarde."
          );
        });
    });
  });

  // Enviar mensaje con botón
  sendBtn.addEventListener("click", sendMessage);

  // Enviar mensaje con Enter
  userInput.addEventListener("keypress", function (e) {
    if (e.key === "Enter") {
      sendMessage();
    }
  });

  // Añadir animación de puntos suspensivos
  document.head.insertAdjacentHTML(
    "beforeend",
    `
        <style>
            @keyframes ellipsis {
                0% { content: ''; }
                25% { content: '.'; }
                50% { content: '..'; }
                75% { content: '...'; }
                100% { content: ''; }
            }
            .typing-dots {
                display: inline-block;
                width: 20px;
                overflow: hidden;
            }
            .typing-dots::after {
                content: '';
                animation: ellipsis 1.5s infinite;
            }
        </style>
    `
  );

  // Mostrar mensajes proactivos después de cierto tiempo
  setTimeout(function () {
    if (document.querySelectorAll(".user-message").length === 0) {
      // Si el usuario aún no ha interactuado
      const suggestedQuestion = "¿Necesitas ayuda para cotizar un envío?";
      uración;
      addBotMessage(
        "Veo que estás explorando nuestra página. " + suggestedQuestion
      );
    }
  }, 30000); // 30 segundos

  // Detectar intención de salida (mover el ratón hacia la parte superior)
  document.addEventListener("mouseleave", function (e) {
    if (
      e.clientY < 5 &&
      document.querySelectorAll(".user-message").length === 0
    ) {
      addBotMessage(
        "¡No te vayas! ¿Puedo ayudarte a encontrar información sobre nuestros servicios?"
      );
    }
  });
});

// Función para conectar con la API de Meta AI
async function fetchMetaAIResponse(query) {
  try {
    console.log("Enviando consulta:", query); // Para depuración
    const response = await fetch("api/meta-ai.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        query: query,
        history: [], // Aseguramos enviar un array aunque esté vacío
      }),
    });

    if (!response.ok) {
      throw new Error(`Error HTTP: ${response.status}`);
    }

    const data = await response.json();
    console.log("Respuesta recibida:", data); // Para depuración
    return {
      response:
        data.response || "Lo siento, no puedo responder en este momento.",
      suggestions: data.suggestions || [
        "Cotización",
        "Rastrear envío",
        "Servicios",
      ],
    };
  } catch (error) {
    console.error("Error al conectar con la API:", error);
    return {
      response:
        "Lo siento, estoy teniendo problemas para procesar tu solicitud. Por favor, intenta más tarde.",
      suggestions: ["Contactar asesor", "Intentar de nuevo"],
    };
  }
}
