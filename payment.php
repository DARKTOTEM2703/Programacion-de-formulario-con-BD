<?php
session_start();
require_once 'components/db_connection.php';

// Verificar parámetros necesarios
if (!isset($_GET['tracking']) || !isset($_GET['amount'])) {
    header("Location: php/error.php?message=Parámetros+incorrectos");
    exit();
}

$tracking_number = $_GET['tracking'];
$amount = floatval($_GET['amount']);

// Verificar que el envío existe
$stmt = $conn->prepare("SELECT id, name, email, estado_pago FROM envios WHERE tracking_number = ?");
$stmt->bind_param("s", $tracking_number);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: php/error.php?message=Envío+no+encontrado");
    exit();
}

$envio = $result->fetch_assoc();

// Si ya está pagado, mostrar mensaje
if ($envio['estado_pago'] == 'pagado') {
    $_SESSION['info'] = "Este envío ya ha sido pagado";
    header("Location: php/tracking.php?tracking=" . urlencode($tracking_number));
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago de Envío - MENDEZ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/header.css">
    <style>
        :root {
            --primary-color: #0057B8;
            --secondary-color: #FF9500;
            --accent-color: #f1c40f;
            --success-color: #2E8B57;
            --border-radius: 12px;
            --box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .payment-container {
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
            background: linear-gradient(135deg, var(--primary-color), #0046a1);
            padding: 1.5rem;
            border-bottom: none;
        }

        .card-body {
            padding: 2rem;
        }

        .alert-info {
            background-color: rgba(0, 87, 184, 0.1);
            border-left: 4px solid var(--primary-color);
            border-top: none;
            border-right: none;
            border-bottom: none;
            color: var(--primary-color);
            padding: 1.2rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
        }

        .alert-info i {
            font-size: 1.5rem;
            margin-right: 1rem;
        }

        .card-title {
            color: var(--primary-color);
            font-weight: 600;
            position: relative;
            padding-bottom: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .card-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .price-tag {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 1.2rem;
            border-radius: 10px;
            display: inline-block;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }

        .price-tag:hover {
            transform: scale(1.05);
        }

        .price-tag .small {
            display: block;
            color: #666;
            margin-bottom: 0.3rem;
        }

        .price-tag .price {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 700;
        }

        .btn-pagar {
            background: linear-gradient(135deg, var(--success-color), #218838);
            border: none;
            padding: 1rem 2.5rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-radius: 50px;
            box-shadow: 0 5px 15px rgba(46, 139, 87, 0.3);
            transition: var(--transition);
        }

        .btn-pagar:hover,
        .btn-pagar:focus {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(46, 139, 87, 0.4);
            background: linear-gradient(135deg, #218838, var(--success-color));
        }

        .btn-pagar:active {
            transform: translateY(1px);
        }

        .security-icons {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .security-icons i {
            font-size: 2rem;
            transition: var(--transition);
        }

        .security-icons i:hover {
            transform: scale(1.2);
        }

        .bi-stripe {
            color: #635BFF;
        }

        .bi-credit-card {
            color: #495057;
        }

        .bi-shield-lock {
            color: #20c997;
        }

        @media (max-width: 768px) {
            .card-body {
                padding: 1.5rem;
            }

            .price-tag .price {
                font-size: 1.5rem;
            }
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
                background-color: rgba(0, 87, 184, 0.15);
                color: #90caf9;
            }

            .price-tag {
                background: linear-gradient(135deg, #2c2c2c, #333);
            }

            .price-tag .small {
                color: #adb5bd;
            }

            .price-tag .price {
                color: #90caf9;
            }

            .text-muted {
                color: #adb5bd !important;
            }
        }
    </style>
</head>

<body>
    <!-- Logo en la parte superior -->
    <div class="topbar">
        <div class="topbar_logo">
            <img src="img/logo.png" alt="Logo">
        </div>
        <div class="blue-bar"></div>
        <div class="container payment-container">
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0 d-flex align-items-center">
                                <i class="bi bi-credit-card me-3"></i>
                                Pago de Envío
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info mb-4">
                                <i class="bi bi-info-circle"></i>
                                <div>Para procesar tu envío, es necesario realizar el pago correspondiente.</div>
                            </div>

                            <h5 class="card-title">Detalles del envío #<?php echo htmlspecialchars($tracking_number); ?>
                            </h5>

                            <div class="row mb-4 align-items-center">
                                <div class="col-md-7">
                                    <div class="shipping-details p-3">
                                        <p class="mb-2">
                                            <i class="bi bi-person-fill me-2 text-primary"></i>
                                            <strong>Cliente:</strong> <?php echo htmlspecialchars($envio['name']); ?>
                                        </p>
                                        <p class="mb-0">
                                            <i class="bi bi-envelope-fill me-2 text-primary"></i>
                                            <strong>Email:</strong> <?php echo htmlspecialchars($envio['email']); ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-5 text-center text-md-end mt-3 mt-md-0">
                                    <div class="price-tag">
                                        <span class="small">Total a pagar:</span>
                                        <span class="price">$<?php echo number_format($amount, 2); ?> MXN</span>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-5">
                                <button id="btn-pagar" class="btn btn-success btn-lg btn-pagar">
                                    <i class="bi bi-lock-fill me-2"></i>Proceder al Pago Seguro
                                </button>

                                <div class="mt-4">
                                    <small class="text-muted d-block mb-2">Pago procesado de forma segura por
                                        Stripe</small>
                                    <div class="security-icons">
                                        <i class="bi bi-stripe"></i>
                                        <i class="bi bi-credit-card"></i>
                                        <i class="bi bi-shield-lock"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'components/footer.php'; ?>

        <script>
            document.getElementById('btn-pagar').addEventListener('click', async function() {
                this.disabled = true;
                this.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Procesando...';

                try {
                    const response = await fetch('api/crear_enlace_pago.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            amount: <?php echo $amount; ?>,
                            tracking_number: '<?php echo $tracking_number; ?>',
                            email: '<?php echo $envio['email']; ?>',
                            name: '<?php echo $envio['name']; ?>',
                            envio_id: <?php echo $envio['id']; ?>
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        window.location.href = data.payment_url;
                    } else {
                        alert('Error: ' + data.message);
                        this.disabled = false;
                        this.innerHTML = '<i class="bi bi-lock-fill me-2"></i>Proceder al Pago Seguro';
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error al procesar la solicitud.');
                    this.disabled = false;
                    this.innerHTML = '<i class="bi bi-lock-fill me-2"></i>Proceder al Pago Seguro';
                }
            });
        </script>
</body>

</html>