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
        <!-- Mostrar mensajes de éxito o error -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo $_SESSION['success']; ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo $_SESSION['error']; ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Nuevo Envío</li>
            </ol>
        </nav>

        <h2 class="mb-4"><i class="bi bi-box-seam me-2"></i>Solicitud de Servicio de Envío</h2>

        <!-- Indicador de progreso -->
        <div class="progress mb-4" style="height: 10px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0"
                aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>
        </div>

        <form action="../components/form_handler.php" method="POST" id="shipping-form" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="usuario_id"
                value="<?php echo isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : ''; ?>">

            <!-- Paso 1: Información del remitente -->
            <div class="form-section" data-step="1">
                <h3><i class="bi bi-person-fill me-2"></i>INFORMACIÓN DEL REMITENTE</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="name"><i class="bi bi-person me-1"></i> Nombre Completo *</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                        <small class="form-text text-muted">Nombre y apellido como aparecen en tu
                            identificación.</small>
                    </div>
                    <div class="form-group">
                        <label for="email"><i class="bi bi-envelope me-1"></i> Email *</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone"><i class="bi bi-phone me-1"></i> Celular *</label>
                        <input type="tel" id="phone" name="phone" class="form-control" placeholder="123-456-7890"
                            required>
                    </div>
                    <div class="form-group">
                        <label for="office-phone"><i class="bi bi-telephone me-1"></i> Teléfono de oficina</label>
                        <input type="tel" id="office-phone" name="office_phone" class="form-control">
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

                <div class="form-row">
                    <div class="form-group">
                        <label for="origin"><i class="bi bi-cursor-fill me-1"></i> Dirección de origen *</label>
                        <input type="text" id="origin" name="origin" class="form-control" required>
                        <small class="form-text text-muted">Incluye calle, número, colonia y código postal.</small>
                    </div>
                    <div class="form-group">
                        <label for="destination"><i class="bi bi-geo me-1"></i> Dirección Destino *</label>
                        <input type="text" id="destination" name="destination" class="form-control" required>
                        <small class="form-text text-muted">Incluye calle, número, colonia y código postal.</small>
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
                    <div class="form-group">
                        <label for="weight"><i class="bi bi-speedometer2 me-1"></i> Peso aproximado (kg) *</label>
                        <input type="number" id="weight" name="weight" min="0.1" step="0.1" class="form-control"
                            required>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label for="description"><i class="bi bi-card-text me-1"></i> Descripción de la mercancía *</label>
                    <textarea id="description" name="description" class="form-control" required></textarea>
                    <small class="form-text text-muted">Describe brevemente el contenido de tu envío.</small>
                </div>

                <div class="text-between mt-3">
                    <button type="button" class="btn btn-secondary prev-step"><i class="bi bi-arrow-left"></i>
                        Anterior</button>
                    <button type="button" class="btn btn-primary next-step">Siguiente <i
                            class="bi bi-arrow-right"></i></button>
                </div>
            </div>

            <!-- Paso 3: Información adicional -->
            <div class="form-section d-none" data-step="3">
                <h3><i class="bi bi-info-circle-fill me-2"></i>INFORMACIÓN ADICIONAL</h3>

                <div class="form-row value-row mb-3">
                    <div class="form-group value-group">
                        <label for="value"><i class="bi bi-currency-dollar me-1"></i> Valor aproximado de toda la
                            mercancía</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" id="value" name="value" step="0.01" class="form-control">
                            <span class="input-group-text">.00 M/N</span>
                        </div>
                    </div>
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
                    <label for="package_image"><i class="bi bi-image me-1"></i> Foto de la mercancía (opcional)</label>
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
    <script>
        // Control de pasos del formulario
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('shipping-form');
            const progressBar = document.querySelector('.progress-bar');
            const sections = document.querySelectorAll('.form-section');
            const nextButtons = document.querySelectorAll('.next-step');
            const prevButtons = document.querySelectorAll('.prev-step');
            const totalSteps = sections.length;

            // Controlar botones "Siguiente"
            nextButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const currentSection = button.closest('.form-section');
                    const currentStep = parseInt(currentSection.dataset.step);
                    const nextStep = currentStep + 1;

                    // Validar campos requeridos en la sección actual
                    const requiredFields = currentSection.querySelectorAll(
                        'input[required], select[required], textarea[required]');
                    let isValid = true;

                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            field.classList.add('is-invalid');
                            isValid = false;
                        } else {
                            field.classList.remove('is-invalid');
                        }
                    });

                    if (!isValid) {
                        return;
                    }

                    // Avanzar al siguiente paso
                    currentSection.classList.add('d-none');
                    const nextSection = document.querySelector(
                        `.form-section[data-step="${nextStep}"]`);
                    if (nextSection) {
                        nextSection.classList.remove('d-none');
                        const progress = (nextStep / totalSteps) * 100;
                        progressBar.style.width = `${progress}%`;
                        progressBar.setAttribute('aria-valuenow', progress);
                    }
                });
            });

            // Controlar botones "Anterior"
            prevButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const currentSection = button.closest('.form-section');
                    const currentStep = parseInt(currentSection.dataset.step);
                    const prevStep = currentStep - 1;

                    currentSection.classList.add('d-none');
                    const prevSection = document.querySelector(
                        `.form-section[data-step="${prevStep}"]`);
                    if (prevSection) {
                        prevSection.classList.remove('d-none');
                        const progress = (prevStep / totalSteps) * 100;
                        progressBar.style.width = `${progress}%`;
                        progressBar.setAttribute('aria-valuenow', progress);
                    }
                });
            });
        });
    </script>
</body>

</html>