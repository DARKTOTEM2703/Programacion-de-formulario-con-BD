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
                    <li class="nav-item"><a class="nav-link" href="#certificaciones">Certificaciones</a></li>
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
                <img src="https://th.bing.com/th/id/OIP.zXb5UndBqGx6vcM7oD1uCwHaDr?rs=1&pid=ImgDetMain"
                    alt="Soluciones de Transporte" class="img-fluid my-3">
                <p class="card-text formattxt">Ofrecemos servicios de transporte confiables y eficientes para garantizar
                    que tu carga llegue a tiempo y en perfectas condiciones.</p>
                <a class="green-button cardformat" href="#">Ver más</a>
            </div>
            <div class="card card2 formatcard1">
                <h1 class="card-title">Logística Personalizada</h1>
                <img src="https://th.bing.com/th/id/OIP.4YEWgqOa6rimX_KqiYI_RgHaE8?rs=1&pid=ImgDetMain"
                    alt="Logística Personalizada" class="img-fluid my-3">
                <p class="card-text formattxt">Diseñamos soluciones logísticas adaptadas a las necesidades específicas
                    de tu negocio, optimizando costos y tiempos.</p>
                <a class="green-button cardformat" href="#">Ver más</a>
            </div>
            <div class="card card3 formatcard1">
                <h1 class="card-title">Seguridad Garantizada</h1>
                <img src="https://www.k8logistica.com/wp-content/uploads/2020/02/trailer-seguridad-768x517.jpg"
                    alt="Seguridad Garantizada" class="img-fluid my-3">
                <p class="card-text formattxt">Tu carga está protegida con los más altos estándares de seguridad,
                    asegurando tranquilidad en cada envío.</p>
                <a class="green-button cardformat" href="#">Ver más</a>
            </div>
        </div>
    </div>
    <div class="containerservice">

    </div>
    <div class="container_2">
        <div id="certificaciones" class="py-5">
            <h1 class="text-center">Certificaciones</h1>
            <p class="text-center">Contamos con certificaciones que avalan la calidad y seguridad de nuestros servicios.
            </p>
            <div class="row text-center">
                <div class="col-md-4">
                    <img src="https://www.primafrio.com/wp-content/uploads/2025/03/sgs-9001-100x100-1.webp"
                        alt="Certificación ISO 9001" class="img-fluid mb-3">
                    <h5>ISO 9001</h5>
                    <p>Certificación en gestión de calidad.</p>
                </div>
                <div class="col-md-4">
                    <img src="https://www.primafrio.com/wp-content/uploads/2025/03/sgs-14001-100x100-1.webp"
                        alt="Certificación ISO 14001" class="img-fluid mb-3">
                    <h5>ISO 14001</h5>
                    <p>Certificación en gestión ambiental.</p>
                </div>
                <div class="col-md-4">
                    <img src="https://logodix.com/logo/625627.png" alt="Certificación OHSAS 18001"
                        class="img-fluid mb-3">
                    <h5>OHSAS 18001</h5>
                    <p>Certificación en seguridad y salud ocupacional.</p>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div id="acerca-de" class="py-5">
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <h1 class="section-title mb-4">Acerca de MENDEZ</h1>
                    <p class="lead">Somos una empresa líder en el sector de transporte y logística con más de 20 años de
                        experiencia.</p>

                    <h4 class="mt-4">Nuestra Historia</h4>
                    <p>Fundada en 2003, MENDEZ comenzó como una pequeña empresa familiar con tan solo 3 camiones. Hoy
                        contamos con una flota de más de 150 unidades y operamos en toda la República Mexicana,
                        ofreciendo soluciones integrales de transporte y logística.</p>

                    <h4 class="mt-4">Misión</h4>
                    <p>Brindar servicios de transporte y logística de la más alta calidad, eficiencia y seguridad,
                        superando las expectativas de nuestros clientes y contribuyendo al desarrollo económico del
                        país.</p>

                    <h4 class="mt-4">Visión</h4>
                    <p>Ser la empresa líder en transporte y logística en México, reconocida por la excelencia en el
                        servicio, la innovación continua y el compromiso con nuestros clientes, colaboradores y el medio
                        ambiente.</p>
                </div>
                <div class="col-lg-6">
                    <div class="about-video-container">
                        <iframe src="https://www.youtube.com/embed/CHirjmiIyhs?si=Bx0JLrV189ruW_mt"
                            title="YouTube video player" frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                    </div>

                    <div class="valores-container p-4">
                        <h4 class="text-center mb-3">Nuestros Valores</h4>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <div class="valor-item">
                                    <h5><i class="fas fa-check-circle me-2"></i> Integridad</h5>
                                    <p>Actuamos con honestidad y transparencia en todo momento.</p>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="valor-item">
                                    <h5><i class="fas fa-users me-2"></i> Compromiso</h5>
                                    <p>Nos dedicamos a cumplir lo prometido y superar expectativas.</p>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="valor-item">
                                    <h5><i class="fas fa-leaf me-2"></i> Sustentabilidad</h5>
                                    <p>Operamos con responsabilidad hacia el medio ambiente.</p>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="valor-item">
                                    <h5><i class="fas fa-cog me-2"></i> Innovación</h5>
                                    <p>Buscamos constantemente mejorar nuestros procesos y servicios.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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