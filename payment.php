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
</head>

<body>
    <?php include 'components/header.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-credit-card me-2"></i>Pago de Envío</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Para procesar tu envío, es necesario realizar el pago correspondiente.
                        </div>

                        <h5 class="card-title mb-4">Detalles del envío
                            #<?php echo htmlspecialchars($tracking_number); ?></h5>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <p><strong>Cliente:</strong> <?php echo htmlspecialchars($envio['name']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($envio['email']); ?></p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <div class="price-tag">
                                    <span class="small">Total a pagar:</span>
                                    <span class="price">$<?php echo number_format($amount, 2); ?> MXN</span>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <button id="btn-pagar" class="btn btn-success btn-lg">
                                <i class="bi bi-lock-fill me-2"></i>Proceder al Pago Seguro
                            </button>
                        </div>

                        <div class="mt-3 text-center">
                            <small class="text-muted">Pago procesado de forma segura por Stripe</small>
                            <div class="mt-2">
                                <i class="bi bi-stripe fs-3 text-primary"></i>
                                <i class="bi bi-credit-card mx-2 text-secondary"></i>
                                <i class="bi bi-shield-lock text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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