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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/forms.css">
    <title>Formulario de Envíos</title>
</head>

<body>
    <?php include '../components/header.php'; ?>

    <div class="form-container">
        <!-- Botón para volver al dashboard -->
        <div class="dashboard-header">
            <button class="btn-back" onclick="window.location.href='dashboard.php'">
                <i class="bi bi-arrow-left me-1"></i> Regresar al Dashboard
            </button>
            <h2><i class="bi bi-box-seam me-2"></i>Historial de Envíos</h2>
        </div>

        <!-- Mensajes de éxito/error aquí si los necesitas -->

        <form action="../components/form_handler.php" method="POST" id="shipping-form" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="usuario_id"
                value="<?php echo isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : ''; ?>">

            <!-- Barra de progreso -->
            <div class="progress mb-4">
                <div class="progress-bar" role="progressbar" style="width: 33%;" aria-valuenow="33" aria-valuemin="0"
                    aria-valuemax="100"></div>
            </div>

            <!-- Paso 1: Información del remitente -->
            <div class="form-section" data-step="1">
                <h3><i class="bi bi-person-fill me-2"></i>INFORMACIÓN DEL REMITENTE</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="name"><i class="bi bi-person me-1"></i> Nombre Completo *</label>
                        <input type="text" id="name" name="name" class="form-control" required
                            placeholder="Ej: Juan Pérez Rodríguez">
                    </div>
                    <div class="form-group">
                        <label for="email"><i class="bi bi-envelope me-1"></i> Email *</label>
                        <input type="email" id="email" name="email" class="form-control" required
                            placeholder="Ej: correo@ejemplo.com">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone"><i class="bi bi-phone me-1"></i> Celular *</label>
                        <input type="tel" id="phone" name="phone" class="form-control" required
                            placeholder="Ej: 999 123 4567">
                    </div>
                    <div class="form-group">
                        <label for="office-phone"><i class="bi bi-telephone me-1"></i> Teléfono de oficina</label>
                        <input type="tel" id="office-phone" name="office_phone" class="form-control"
                            placeholder="Ej: (999) 123 4567">
                    </div>
                </div>

                <div class="text-end mt-3">
                    <button type="button" class="btn btn-primary next-step">Siguiente <i
                            class="bi bi-arrow-right"></i></button>
                </div>
            </div>

            <!-- Paso 2: Información del envío -->
            <div class="form-section d-none" data-step="2">
                <h3><i class="bi bi-geo-alt-fill me-2"></i>DATOS DE LA MERCANCÍA</h3>
                <p class="warning"><i class="bi bi-exclamation-triangle-fill me-1"></i> Especifica si el envío es para
                    algún municipio en particular dentro o fuera de la ciudad</p>

                <!-- Dirección de origen -->
                <h5>Dirección de origen</h5>
                <div class="form-row">
                    <div class="form-group">
                        <label for="origin_street"><i class="bi bi-cursor-fill me-1"></i> Calle *</label>
                        <input type="text" id="origin_street" name="origin_street" class="form-control" required
                            placeholder="Ejemplo: C. 55ᴬ">
                    </div>
                    <div class="form-group">
                        <label for="origin_number"><i class="bi bi-hash me-1"></i> Número *</label>
                        <input type="text" id="origin_number" name="origin_number" class="form-control" required
                            placeholder="Ejemplo: 359">
                    </div>
                    <div class="form-group">
                        <label for="origin_colony"><i class="bi bi-house me-1"></i> Colonia *</label>
                        <input type="text" id="origin_colony" name="origin_colony" class="form-control" required
                            placeholder="Ejemplo: Juan Pablo II">
                    </div>
                    <div class="form-group">
                        <label for="origin_postal_code"><i class="bi bi-envelope me-1"></i> Código Postal *</label>
                        <input type="text" id="origin_postal_code" name="origin_postal_code" class="form-control"
                            required placeholder="Ejemplo: 97246">
                    </div>
                    <div class="form-group">
                        <label for="origin_city"><i class="bi bi-geo me-1"></i> Ciudad *</label>
                        <input type="text" id="origin_city" name="origin_city" class="form-control" required
                            placeholder="Ejemplo: Mérida">
                    </div>
                    <div class="form-group">
                        <label for="origin_state"><i class="bi bi-map me-1"></i> Estado *</label>
                        <input type="text" id="origin_state" name="origin_state" class="form-control" required
                            placeholder="Ejemplo: Yuc.">
                    </div>
                    <div class="form-group">
                        <label for="origin_country"><i class="bi bi-globe me-1"></i> País *</label>
                        <input type="text" id="origin_country" name="origin_country" class="form-control" required
                            placeholder="Ejemplo: México">
                    </div>
                </div>

                <!-- Dirección de destino -->
                <h5>Dirección de destino</h5>
                <div class="form-row">
                    <div class="form-group">
                        <label for="destination_street"><i class="bi bi-cursor-fill me-1"></i> Calle *</label>
                        <input type="text" id="destination_street" name="destination_street" class="form-control"
                            required placeholder="Ejemplo: Av. Pallaresa">
                    </div>
                    <div class="form-group">
                        <label for="destination_number"><i class="bi bi-hash me-1"></i> Número *</label>
                        <input type="text" id="destination_number" name="destination_number" class="form-control"
                            required placeholder="Ejemplo: 103">
                    </div>
                    <div class="form-group">
                        <label for="destination_colony"><i class="bi bi-house me-1"></i> Colonia *</label>
                        <input type="text" id="destination_colony" name="destination_colony" class="form-control"
                            placeholder="Ejemplo: Santa Coloma de Gramenet">
                    </div>
                    <div class="form-group">
                        <label for="destination_postal_code"><i class="bi bi-envelope me-1"></i> Código Postal *</label>
                        <input type="text" id="destination_postal_code" name="destination_postal_code"
                            class="form-control" required placeholder="Ejemplo: 08924">
                    </div>
                    <div class="form-group">
                        <label for="destination_city"><i class="bi bi-geo me-1"></i> Ciudad *</label>
                        <input type="text" id="destination_city" name="destination_city" class="form-control" required
                            placeholder="Ejemplo: Barcelona">
                    </div>
                    <div class="form-group">
                        <label for="destination_state"><i class="bi bi-map me-1"></i> Provincia/Estado *</label>
                        <input type="text" id="destination_state" name="destination_state" class="form-control" required
                            placeholder="Ejemplo: Cataluña">
                    </div>
                    <div class="form-group">
                        <label for="destination_country"><i class="bi bi-globe me-1"></i> País *</label>
                        <input type="text" id="destination_country" name="destination_country" class="form-control"
                            required placeholder="Ejemplo: España">
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label for="delivery_date"><i class="bi bi-calendar-event me-1"></i> Fecha deseada de
                        entrega</label>
                    <input type="date" id="delivery_date" name="delivery_date" class="form-control"
                        min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="weight"><i class="bi bi-speedometer2 me-1"></i> Peso aproximado (kg) *</label>
                        <input type="number" id="weight" name="weight" min="0.1" step="0.1" class="form-control"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="package_type"><i class="bi bi-box me-1"></i> Tipo de paquete *</label>
                        <select id="package_type" name="package_type" class="form-select" required>
                            <option value="">Selecciona una opción</option>
                            <option value="documento">Documento</option>
                            <option value="paquete_pequeno">Paquete pequeño</option>
                            <option value="paquete_mediano">Paquete mediano</option>
                            <option value="paquete_grande">Paquete grande</option>
                            <option value="carga_voluminosa">Carga voluminosa</option>
                        </select>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label for="description"><i class="bi bi-card-text me-1"></i> Descripción de la mercancía
                        *</label>
                    <textarea id="description" name="description" class="form-control" required></textarea>
                    <small class="form-text text-muted">Describe brevemente el contenido de tu envío.</small>
                </div>

                <!-- Contenedor para el enlace de pago -->
                <div id="payment-link-container" style="display: none; margin-top: 20px;"></div>

                <!-- Agregar este botón al final del paso 2, justo antes de los botones de navegación -->
                <div class="form-group mt-4">
                    <div id="resultado-calculo" class="alert alert-info d-none">
                        Calculando...
                    </div>
                </div>

                <div class="text-between mt-3">
                    <button type="button" class="btn btn-secondary prev-step"><i class="bi bi-arrow-left"></i>
                        Anterior</button>
                    <button type="button" class="btn btn-primary next-step" id="calcular-y-siguiente">
                        <i class="bi bi-calculator me-2"></i>Calcular y Continuar <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- Paso 3: Información adicional -->
            <div class="form-section d-none" data-step="3">
                <h3><i class="bi bi-info-circle-fill me-2"></i>INFORMACIÓN ADICIONAL</h3>
                <!-- Nuevo campo para mostrar el costo total actualizado -->
                <div class="form-group mb-3">
                    <label for="calculated_cost">
                        <i class="bi bi-currency-dollar me-1"></i> Costo Total del Envío
                    </label>
                    <input type="text" id="calculated_cost" class="form-control" readonly>
                    <input type="hidden" id="hidden_calculated_cost" name="hidden_calculated_cost">
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="insurance" name="insurance" value="1">
                    <label class="form-check-label" for="insurance">
                        <i class="bi bi-shield-check me-1"></i> Deseo agregar seguro a mi envío
                    </label>
                    <small class="form-text text-muted d-block">El seguro cubre hasta el 100% del valor
                        declarado.</small>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="urgent" name="urgent" value="1">
                    <label class="form-check-label" for="urgent">
                        <i class="bi bi-lightning-charge me-1"></i> Servicio urgente (costo adicional)
                    </label>
                </div>

                <div class="form-group mb-3">
                    <label for="additional_notes"><i class="bi bi-pencil-square me-1"></i> Instrucciones
                        especiales</label>
                    <textarea id="additional_notes" name="additional_notes" class="form-control" rows="3"></textarea>
                </div>

                <div class="form-group mb-3">
                    <label for="package_image"><i class="bi bi-image me-1"></i> Foto de la mercancía
                        (opcional)</label>
                    <input type="file" id="package_image" name="package_image" class="form-control" accept="image">
                    <small class="form-text text-muted">Formato: JPG, PNG (máx. 2MB)</small>
                </div>

                <div class="text-between mt-3">
                    <button type="button" class="btn btn-secondary prev-step"><i class="bi bi-arrow-left"></i>
                        Anterior</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-circle"></i> Confirmar
                        envío</button>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/formenvioalert.js"></script>
    <script src="../js/form_validation.js"></script>
    <script src="../js/pasosForms.js"></script>
    <script src="../js/calculadorCosto.js"></script>
</body>

</html>