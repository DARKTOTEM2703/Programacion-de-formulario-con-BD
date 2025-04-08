function handleCredentialResponse(response) {
  const user = parseJwt(response.credential);
  console.log("Usuario autenticado:", user);

  fetch("components/google_login_handler.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      token: response.credential,
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      console.log("Respuesta del servidor:", data);
      if (data.success) {
        alert(`Bienvenido, ${user.name}`);
        window.location.href = "index.php";
      } else {
        alert("Error al autenticar con Google.");
      }
    })
    .catch((err) => console.error("Error:", err));
}

function parseJwt(token) {
  const base64Url = token.split(".")[1];
  const base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
  const jsonPayload = decodeURIComponent(
    atob(base64)
      .split("")
      .map((c) => {
        return "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2);
      })
      .join("")
  );
  return JSON.parse(jsonPayload);
}
