<?php
session_start();
require_once '../vendor/autoload.php';
require_once '../components/db_connection.php';

// Verificar si hay un ID de sesión
$session_id = $_GET['session_id'] ?? null;

if ($session_id) {
    try {
        // Configurar Stripe
        \Stripe\Stripe::setApiKey('sk_test_TU_CLAVE_DE_STRIPE');

        // Verificar el estado del pago
        $session = \Stripe\Checkout\Session::retrieve($session_id);

        if ($session && $session->payment_status === 'paid') {
            // Obtener datos del metadata
            $tracking_number = $session->metadata->tracking_number ?? '';

            // Para asegurar que el estado se actualice, incluso si el webhook falla
            if ($tracking_number) {
                $stmt = $conn->prepare("UPDATE envios SET estado_pago = 'pagado', fecha_pago = NOW() WHERE tracking_number = ?");
                $stmt->bind_param("s", $tracking_number);
                $stmt->execute();

                $_SESSION['success'] = "¡Pago recibido correctamente! Tu envío será procesado en breve.";
                $_SESSION['tracking_number'] = $tracking_number;
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Hubo un problema al verificar tu pago: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago Exitoso - MENDEZ</title>
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
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="bi bi-check-circle-fill me-2"></i>¡Pago Exitoso!</h4>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-4">
                            <i class="bi bi-check-circle text-success" style="font-size: 5rem;"></i>
                        </div>

                        <h3>¡Gracias por tu pago!</h3>
                        <p class="mb-4">Tu pago ha sido procesado correctamente y tu envío será procesado en breve.</p>

                        <?php if (isset($_SESSION['tracking_number'])): ?>
                            <div class="alert alert-info">
                                <p>Número de seguimiento:
                                    <strong><?php echo htmlspecialchars($_SESSION['tracking_number']); ?></strong>
                                </p>
                                <p>Puedes usar este número para dar seguimiento a tu envío.</p>
                            </div>
                        <?php endif; ?>

                        <div class="mt-4">
                            <a href="dashboard.php" class="btn btn-primary me-2">
                                <i class="bi bi-house-fill me-1"></i> Ir al Dashboard
                            </a>

                            <?php if (isset($_SESSION['tracking_number'])): ?>
                                <a href="tracking.php?tracking=<?php echo urlencode($_SESSION['tracking_number']); ?>"
                                    class="btn btn-outline-primary">
                                    <i class="bi bi-search me-1"></i> Ver Seguimiento
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>