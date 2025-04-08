<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Iniciar Sesión</title>
</head>

<body class="bg-light">
    <div class="container d-flex flex-column align-items-center justify-content-center vh-100">
        <!-- Logo de la empresa -->
        <div class="mb-4">
            <img src="img/logo.png" alt="Logo de la empresa" class="img-fluid" style="max-width: 200px;">
        </div>

        <!-- Contenedor del formulario -->
        <div class="card shadow-sm p-4" style="width: 100%; max-width: 400px;">
            <h2 class="text-center mb-4">Iniciar Sesión</h2>

            <?php
            session_start();
            if (isset($_SESSION['error'])) {
                echo '<div class="alert alert-danger text-center" role="alert">' . $_SESSION['error'] . '</div>';
                unset($_SESSION['error']); // Elimina el mensaje después de mostrarlo
            }
            ?>

            <form action="components/login_handler.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña:</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
            </form>

            <div class="text-center mt-3">
                <p>¿No tienes una cuenta? <a href="register.php" class="text-decoration-none">Regístrate aquí</a></p>
            </div>

            <!-- Botón de Google Sign-In -->
            <div class="text-center mt-4">
                <p>O inicia sesión con:</p>
                <div id="g_id_onload" data-client_id="TU_CLIENT_ID" data-context="signin" data-ux_mode="popup"
                    data-callback="handleCredentialResponse" data-auto_prompt="false">
                </div>
                <div class="g_id_signin" data-type="standard"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/dark-mode.js"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script>
    function handleCredentialResponse(response) {
        // Decodificar el token JWT de Google
        const user = parseJwt(response.credential);
        console.log("Usuario autenticado:", user);

        // Enviar el token al servidor para validación
        fetch("components/google_login_handler.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    token: response.credential
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(`Bienvenido, ${user.name}`);
                    window.location.href = "dashboard.php"; // Redirige al usuario
                } else {
                    alert("Error al autenticar con Google.");
                }
            })
            .catch(err => console.error("Error:", err));
    }

    function parseJwt(token) {
        const base64Url = token.split('.')[1];
        const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        const jsonPayload = decodeURIComponent(atob(base64).split('').map(c => {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));
        return JSON.parse(jsonPayload);
    }
    </script>
</body>

</html>