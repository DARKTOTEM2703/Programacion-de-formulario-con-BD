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

    <style>
    :root {
        /* Variables para modo claro */
        --primary-color: #0057B8;
        --secondary-color: #FF9500;
        --accent-color: #f1c40f;
        --warning-color: #f39c12;
        --danger-color: #e74c3c;
        --border-radius: 12px;
        --box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        --transition: all 0.3s ease;
    }

    body {
        background-color: #f0f2f5;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .cancel-container {
        max-width: 800px;
        margin: 2rem auto;
        padding: 0;
        animation: fadeIn 0.5s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .logo-container {
        margin-bottom: 15px;
        text-align: center;
    }

    .logo-container img {
        max-height: 80px;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
    }

    .card {
        border: none;
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: var(--box-shadow);
        transition: var(--transition);
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
    }

    .card-header {
        background: linear-gradient(135deg, var(--warning-color), #e67e22);
        padding: 1.5rem;
        border-bottom: none;
    }

    .card-body {
        padding: 2rem;
    }

    .alert-info {
        background-color: rgba(243, 156, 18, 0.1);
        border-left: 4px solid var(--warning-color);
        border-top: none;
        border-right: none;
        border-bottom: none;
        color: #8a6d3b;
        padding: 1.2rem;
        border-radius: 8px;
        margin-top: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .cancel-icon {
        font-size: 5rem;
        color: var(--warning-color);
        display: block;
        margin: 20px auto;
        animation: bounce 2s ease infinite;
    }

    @keyframes bounce {

        0%,
        20%,
        50%,
        80%,
        100% {
            transform: translateY(0);
        }

        40% {
            transform: translateY(-20px);
        }

        60% {
            transform: translateY(-10px);
        }
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary-color), #0046a1);
        border: none;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        border-radius: 50px;
        box-shadow: 0 4px 10px rgba(0, 87, 184, 0.3);
        transition: var(--transition);
    }

    .btn-primary:hover,
    .btn-primary:focus {
        transform: translateY(-3px);
        box-shadow: 0 8px 15px rgba(0, 87, 184, 0.4);
        background: linear-gradient(135deg, #0046a1, var(--primary-color));
    }

    .btn-outline-primary {
        color: var(--primary-color);
        border-color: var(--primary-color);
        background: transparent;
        border-radius: 50px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        transition: var(--transition);
    }

    .btn-outline-primary:hover,
    .btn-outline-primary:focus {
        background-color: var(--primary-color);
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 4px 10px rgba(0, 87, 184, 0.2);
    }

    /* Modo oscuro */
    @media (prefers-color-scheme: dark) {
        body {
            background-color: #121212;
        }

        .card {
            background-color: #1e1e1e;
        }

        .card-body {
            color: #e0e0e0;
        }

        .alert-info {
            background-color: rgba(243, 156, 18, 0.15);
            color: #f8d775;
        }

        h3 {
            color: #e0e0e0;
        }

        p {
            color: #adb5bd;
        }
    }
    </style>
</head>

<body>
    <!-- Logo en la parte superior -->
    <div class="logo-container mt-4">
        <img src="../img/logo.png" alt="MENDEZ Transportes" class="img-fluid">
    </div>

    <div class="container cancel-container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0"><i class="bi bi-exclamation-triangle-fill me-2"></i>Pago Cancelado</h4>
                    </div>
                    <div class="card-body text-center">
                        <i class="bi bi-x-circle cancel-icon"></i>

                        <h3 class="mb-3">El proceso de pago ha sido cancelado</h3>
                        <p class="mb-4">No te preocupes, puedes intentarlo nuevamente cuando estés listo.</p>

                        <div class="alert alert-info">
                            <p class="mb-0">Tu envío permanecerá en estado "pendiente de pago" hasta que completes la
                                transacción.</p>
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

    <?php include '../components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>