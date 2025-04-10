<!-- filepath: c:\xampp\htdocs\Programacion-de-formulario-con-BD\index.php -->
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MENDEZ - Transportes de Carga de México</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/footer.css">
</head>

<body class="bg-light text-dark">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="img/logo.png" alt="MENDEZ Logo" height="50">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="#">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Servicios</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Unidades</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Certificaciones</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Acerca de</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container text-center py-5">
            <div class="hero-overlay"></div>
            <div class="container text-center hero-content">
                <h1 class="display-3 fw-bold">Llevamos tu carga, impulsamos tu éxito</h1>
                <p>
                    Somos la mejor opción de servicios integrales de logística y distribución de paquetería y carga.
                    Hemos consolidado una red de logística, servicio y comunicación de la más alta calidad que nos
                    permite ofrecer siempre el mejor servicio a nuestros clientes.
                </p>
                <a href="https://www.youtube.com/watch?v=I56LwrvY_lk" class="btn btn-light btn-lg" target="_blank"
                    rel="noopener noreferrer">Ver Video ▶</a>
                <a href="php/login.php" class="btn btn-warning btn-lg" target="_blank">Iniciar Sesión</a>
            </div>
    </section>
    <div class="container">
        <div class="cards-container">
            <div class="card card1 formatcard1">
                <h1 class="card-title">Soluciones de Transporte</h1>
                <p class="card-text formattxt">Ofrecemos servicios de transporte confiables y eficientes para garantizar
                    que tu carga llegue a tiempo y en perfectas condiciones.</p>
                <a class="green-button cardformat" href="#">Ver más</a>
            </div>
            <div class="card card2 formatcard1">
                <h1 class="card-title">Logística Personalizada</h1>
                <p class="card-text formattxt">Diseñamos soluciones logísticas adaptadas a las necesidades específicas
                    de tu negocio, optimizando costos y tiempos.</p>
                <a class="green-button cardformat" href="#">Ver más</a>
            </div>
            <div class="card card3 formatcard1">
                <h1 class="card-title">Seguridad Garantizada</h1>
                <p class="card-text formattxt">Tu carga está protegida con los más altos estándares de seguridad,
                    asegurando tranquilidad en cada envío.</p>
                <a class="green-button cardformat" href="#">Ver más</a>
            </div>
        </div>
    </div>
    <?php
    include 'components/footer.php';
    ?>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/animation.js"></script>

    <body>

</html>