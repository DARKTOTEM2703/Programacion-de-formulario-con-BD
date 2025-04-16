<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/register.css">
    <title>Registro de Usuario</title>
</head>

<body class="bg-light text-dark">
    <div class="container py-5">
        <!-- Logo -->
        <div class="logo-container">
            <img src="../img/logo.png" alt="Logo de la empresa">
        </div>
        <!-- Mensajes de sesión -->
        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger" role="alert">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success" role="alert">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
            echo '<script>
                    setTimeout(function() {
                        window.location.href = "login.php";
                    }, 3000);
                  </script>';
        }
        ?>

        <!-- Formulario de registro -->
        <div class="card shadow-sm p-4">
            <h3 class="text-center text-primary mb-4">Crea tu cuenta</h3>
            <form action="../components/register_handler.php" method="POST">
                <div class="mb-3">
                    <label for="nombre_usuario" class="form-label">Nombre de Usuario:</label>
                    <input type="text" id="nombre_usuario" name="nombre_usuario" class="form-control"
                        placeholder="Ingresa tu nombre de usuario" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" id="email" name="email" class="form-control"
                        placeholder="Ingresa tu correo electrónico" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña:</label>
                    <input type="password" id="password" name="password" class="form-control"
                        placeholder="Crea una contraseña segura" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirmar Contraseña:</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                        placeholder="Repite tu contraseña" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 mb-3">Registrar</button>
            </form>

            <!-- Botón para ir al login -->
            <div class="text-center">
                <p>¿Ya tienes una cuenta?</p>
                <a href="login.php" class="btn btn-outline-secondary w-100">Ir al Login</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/dark-mode.js"></script>
</body>

</html>