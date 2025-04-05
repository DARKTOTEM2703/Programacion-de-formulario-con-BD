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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>