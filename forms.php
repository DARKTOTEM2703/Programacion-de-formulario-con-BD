<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/forms.css">
    <title>Formulario de Envíos</title>
</head>

<body>
    <?php include 'components/header.php'; ?>

    <div class="form-container">
        <button class="btn-back" onclick="window.location.href='dashboard.php'">
            << Regresar</button>
                <form action="components/form_handler.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="usuario_id"
                        value="<?php echo isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : ''; ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">* Nombre Completo</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">* Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">* Celular</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label for="office-phone">Teléfono de oficina</label>
                            <input type="tel" id="office-phone" name="office-phone">
                        </div>
                    </div>

                    <h3>DATOS DE LA MERCANCÍA QUE DESEAS ENVIAR</h3>
                    <p class="warning">* Especifica si el envío es para algún municipio en particular dentro o fuera de
                        la ciudad</p>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="origin">* Dirección de origen</label>
                            <input type="text" id="origin" name="origin" required>
                        </div>
                        <div class="form-group">
                            <label for="destination">* Dirección Destino</label>
                            <input type="text" id="destination" name="destination" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="description">¿Qué tipo de objetos quieres enviar?</label>
                        <textarea id="description" name="description"></textarea>
                    </div>
                    <div class="form-row value-row">
                        <div class="form-group value-group">
                            <label for="value">Valor aproximado de toda la mercancía</label>
                            <div class="value-input">
                                <span>$</span>
                                <input type="number" id="value" name="value" step="0.01">
                                <span>.00 M/N</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-buttons">
                        <button type="submit" class="btn-submit">Guardar</button>
                    </div>
                </form>
    </div>
</body>

</html>