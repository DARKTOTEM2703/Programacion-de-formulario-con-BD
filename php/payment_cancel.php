<?php
session_start(); ?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago Cancelado - MENDEZ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>

<body>
    <?php include '../components/header.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0"><i class="bi bi-exclamation-triangle-fill me-2"></i>Pago Cancelado</h4>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-4">
                            <i class="bi bi-x-circle text-warning" style="font-size: 5rem;"></i>
                        </div>

                        <h3>El proceso de pago ha sido cancelado</h3>
                        <p class="mb-4">No te preocupes, puedes intentarlo nuevamente cuando estés listo.</p>

                        <div class="alert alert-info">
                            <p>Tu envío permanecerá en estado "pendiente de pago" hasta que completes la transacción.
                            </p>
                        </div>

                        <div class="mt-4">
                            <a href="dashboard.php" class="btn btn-primary me-2">
                                <i class="bi bi-house-fill me-1"></i> Ir al Dashboard
                            </a>
                            <a href="../index.php" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-left me-1"></i> Volver al Inicio
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>