<!-- filepath: c:\xampp\htdocs\Programacion-de-formulario-con-BD\index.php -->
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MENDEZ - Transportes de Carga de México</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/chatbot.css">
    <!-- Agrega en el <head> después de tus CSS -->
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css" />
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/pace/1.2.4/themes/blue/pace-theme-flash.min.css" />
</head>

<body class="bg-light text-dark">
    <!-- Loader de carga profesional con Pace.js (no necesitas el div loader-bg) -->
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
                    <li class="nav-item"><a class="nav-link" href="#servicios">Servicios</a></li>
                    <li class="nav-item"><a class="nav-link" href="#unidades">Unidades</a></li>
                    <li class="nav-item"><a class="nav-link" href="#certificaciones">Certificaciones</a></li>
                    <li class="nav-item"><a class="nav-link" href="#acerca-de">Acerca de</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" data-aos="fade-in" data-aos-duration="1200">
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
        </div>
    </section>
    <!-- Sección de Servicios -->
    <section id="servicios" class="py-5 scroll-offset">
        <div class="container">
            <div class="row mb-5" data-aos="fade-up" data-aos-delay="100">
                <div class="col-12 text-center">
                    <h2 class="section-title mb-3">Nuestros Servicios</h2>
                    <p class="lead">Ofrecemos soluciones integrales de transporte y logística adaptadas a tus
                        necesidades</p>
                </div>
            </div>
            <div class="row g-4">
                <!-- Servicio 1 -->
                <div class="col-md-6 col-lg-4 mb-4" data-aos="zoom-in-up" data-aos-delay="200">
                    <div class="service-card h-100 shadow-sm">
                        <div class="service-img-container">
                            <img src="https://images.unsplash.com/photo-1601584115197-04ecc0da31d7?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80"
                                class="card-img-top" alt="Transporte Nacional">
                        </div>
                        <div class="card-body text-center">
                            <div class="service-icon mb-3">
                                <i class="fas fa-truck fa-3x text-warning"></i>
                            </div>
                            <h4 class="card-title">Transporte Nacional</h4>
                            <p class="card-text">Servicio de transporte de carga general y especializada a cualquier
                                punto de la República Mexicana, con seguimiento en tiempo real y garantía de entrega.
                            </p>
                            <a href="#" class="btn btn-outline-primary">Más detalles</a>
                        </div>
                    </div>
                </div>

                <!-- Servicio 2 -->
                <div class="col-md-6 col-lg-4 mb-4" data-aos="zoom-in-up" data-aos-delay="300">
                    <div class="service-card h-100 shadow-sm">
                        <div class="service-img-container">
                            <img src="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80"
                                class="card-img-top" alt="Logística Integral">
                        </div>
                        <div class="card-body text-center">
                            <div class="service-icon mb-3">
                                <i class="fas fa-cubes fa-3x text-warning"></i>
                            </div>
                            <h4 class="card-title">Logística Integral</h4>
                            <p class="card-text">Diseñamos e implementamos soluciones logísticas a medida que optimizan
                                la cadena de suministro de tu empresa, reduciendo costos y tiempos.</p>
                            <a href="#" class="btn btn-outline-primary">Más detalles</a>
                        </div>
                    </div>
                </div>

                <!-- Servicio 3 -->
                <div class="col-md-6 col-lg-4 mb-4" data-aos="zoom-in-up" data-aos-delay="400">
                    <div class="service-card h-100 shadow-sm">
                        <div class="service-img-container">
                            <img src="https://th.bing.com/th/id/R.34facd747cb4ea315dabe09e6fa553fc?rik=D5EK9wZtpdjY3A&pid=ImgRaw&r=0"
                                class="card-img-top" alt="Almacenaje y Distribución">
                        </div>
                        <div class="card-body text-center">
                            <div class="service-icon mb-3">
                                <i class="fas fa-warehouse fa-3x text-warning"></i>
                            </div>
                            <h4 class="card-title">Almacenaje y Distribución</h4>
                            <p class="card-text">Contamos con almacenes estratégicamente ubicados para el resguardo y
                                distribución de tus productos, con control de inventario en tiempo real.</p>
                            <a href="#" class="btn btn-outline-primary">Más detalles</a>
                        </div>
                    </div>
                </div>

                <!-- Servicio 4 -->
                <div class="col-md-6 col-lg-4 mb-4" data-aos="zoom-in-up" data-aos-delay="500">
                    <div class="service-card h-100 shadow-sm">
                        <div class="service-img-container">
                            <img src="https://th.bing.com/th/id/OIP.GULsSQrPGq569qsixiWYQAHaE7?rs=1&pid=ImgDetMain"
                                class="card-img-top" alt="Transporte Refrigerado">
                        </div>
                        <div class="card-body text-center">
                            <div class="service-icon mb-3">
                                <i class="fas fa-temperature-low fa-3x text-warning"></i>
                            </div>
                            <h4 class="card-title">Transporte Refrigerado</h4>
                            <p class="card-text">Especializados en el transporte de productos que requieren control de
                                temperatura, garantizando la cadena de frío durante todo el trayecto.</p>
                            <a href="#" class="btn btn-outline-primary">Más detalles</a>
                        </div>
                    </div>
                </div>

                <!-- Servicio 5 -->
                <div class="col-md-6 col-lg-4 mb-4" data-aos="zoom-in-up" data-aos-delay="600">
                    <div class="service-card h-100 shadow-sm">
                        <div class="service-img-container">
                            <img src="https://th.bing.com/th/id/OIP.G90ev_bZoyJ4B8GpuqLAswHaEK?rs=1&pid=ImgDetMain"
                                class="card-img-top" alt="Carga Especializada">
                        </div>
                        <div class="card-body text-center">
                            <div class="service-icon mb-3">
                                <i class="fas fa-dolly fa-3x text-warning"></i>
                            </div>
                            <h4 class="card-title">Carga Especializada</h4>
                            <p class="card-text">Transportamos cargas de dimensiones especiales, maquinaria pesada y
                                mercancías que requieren manejo especializado con total seguridad.</p>
                            <a href="#" class="btn btn-outline-primary">Más detalles</a>
                        </div>
                    </div>
                </div>

                <!-- Servicio 6 -->
                <div class="col-md-6 col-lg-4 mb-4" data-aos="zoom-in-up" data-aos-delay="700">
                    <div class="service-card h-100 shadow-sm">
                        <div class="service-img-container">
                            <img src="https://th.bing.com/th/id/OIP.W8_0o3G9mhAsbf3YYzCzjQHaC0?rs=1&pid=ImgDetMain"
                                class="card-img-top" alt="Paquetería Express">
                        </div>
                        <div class="card-body text-center">
                            <div class="service-icon mb-3">
                                <i class="fas fa-shipping-fast fa-3x text-warning"></i>
                            </div>
                            <h4 class="card-title">Paquetería Express</h4>
                            <p class="card-text">Entrega de paquetes y documentos en tiempo récord, ideal para envíos
                                urgentes que requieren máxima rapidez y seguridad.</p>
                            <a href="#" class="btn btn-outline-primary">Más detalles</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-12 text-center">
                    <p class="mb-4">¿Necesitas un servicio personalizado? Contáctanos para diseñar una solución a tu
                        medida.</p>
                    <a href="php/login.php" class="btn btn-warning btn-lg">Solicitar cotización</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección de Unidades -->
    <section id="unidades" class="py-5 bg-light scroll-offset">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="section-title mb-3">Nuestra Flota de Unidades</h2>
                    <p class="lead">Contamos con vehículos modernos y especializados para cada tipo de carga</p>
                </div>
            </div>

            <div class="row g-4">
                <!-- Unidad 1 -->
                <div class="col-md-6 col-lg-4 mb-4" data-aos="zoom-in" data-aos-delay="100">
                    <div class="unit-card h-100 shadow-sm">
                        <div class="unit-img-container">
                            <img src="https://fuso.gruposanblas.com/wp-content/uploads/2021/09/canter5t-thumb.png"
                                class="card-img-top" alt="Camión 5 Toneladas">
                        </div>
                        <div class="card-body text-center">
                            <div class="unit-icon mb-3">
                                <i class="fas fa-truck fa-2x text-warning"></i>
                            </div>
                            <h4 class="card-title">Camión 5 Toneladas</h4>
                            <ul class="unit-specs text-start">
                                <li><i class="fas fa-ruler-combined me-2 text-warning"></i> Caja: 5.5m largo x 2.3m
                                    ancho x 2.3m alto</li>
                                <li><i class="fas fa-weight me-2 text-warning"></i> Capacidad: hasta 5 toneladas</li>
                                <li><i class="fas fa-home me-2 text-warning"></i> Ideal para mudanzas residenciales</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Unidad 2 -->
                <div class="col-md-6 col-lg-4 mb-4" data-aos="zoom-in" data-aos-delay="200">
                    <div class="unit-card h-100 shadow-sm">
                        <div class="unit-img-container">
                            <img src="https://fletesdcdlogistica.com.mx/franquias/2/6698846/editor-html/7933709.png"
                                class="card-img-top" alt="Camión Torton">
                        </div>
                        <div class="card-body text-center">
                            <div class="unit-icon mb-3">
                                <i class="fas fa-truck-moving fa-2x text-warning"></i>
                            </div>
                            <h4 class="card-title">Camión Torton</h4>
                            <ul class="unit-specs text-start">
                                <li><i class="fas fa-ruler-combined me-2 text-warning"></i> Caja: 7m largo x 2.5m ancho
                                    x 3m alto</li>
                                <li><i class="fas fa-weight me-2 text-warning"></i> Capacidad: hasta 10 toneladas</li>
                                <li><i class="fas fa-building me-2 text-warning"></i> Ideal para mudanzas corporativas
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Unidad 3 -->
                <div class="col-md-6 col-lg-4 mb-4" data-aos="zoom-in" data-aos-delay="300">
                    <div class="unit-card h-100 shadow-sm">
                        <div class="unit-img-container">
                            <img src="https://www.carpao.com/images/trailer48.png" class="card-img-top"
                                alt="Tráiler 48 pies">
                        </div>
                        <div class="card-body text-center">
                            <div class="unit-icon mb-3">
                                <i class="fas fa-trailer fa-2x text-warning"></i>
                            </div>
                            <h4 class="card-title">Tráiler 48 pies</h4>
                            <ul class="unit-specs text-start">
                                <li><i class="fas fa-ruler-combined me-2 text-warning"></i> Caja: 15m largo x 2.6m ancho
                                    x 3m alto</li>
                                <li><i class="fas fa-weight me-2 text-warning"></i> Capacidad: hasta 30 toneladas</li>
                                <li><i class="fas fa-industry me-2 text-warning"></i> Ideal para cargas industriales
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Unidad 4 -->
                <div class="col-md-6 col-lg-4 mb-4" data-aos="zoom-in" data-aos-delay="400">
                    <div class="unit-card h-100 shadow-sm">
                        <div class="unit-img-container">
                            <img src="https://maxdrive.com.ec/wp-content/uploads/2021/10/Maxdrive-trucks-models-web-900-x-560-px-Instagram-Post-1080-x-540-px-12.png"
                                class="card-img-top" alt="Camioneta 3.5 Toneladas">
                        </div>
                        <div class="card-body text-center">
                            <div class="unit-icon mb-3">
                                <i class="fas fa-shuttle-van fa-2x text-warning"></i>
                            </div>
                            <h4 class="card-title">Camioneta 3.5 Toneladas</h4>
                            <ul class="unit-specs text-start">
                                <li><i class="fas fa-ruler-combined me-2 text-warning"></i> Caja: 4m largo x 2m ancho x
                                    2m alto</li>
                                <li><i class="fas fa-weight me-2 text-warning"></i> Capacidad: hasta 3.5 toneladas</li>
                                <li><i class="fas fa-box me-2 text-warning"></i> Ideal para entregas urbanas rápidas
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Unidad 5 -->
                <div class="col-md-6 col-lg-4 mb-4" data-aos="zoom-in" data-aos-delay="500">
                    <div class="unit-card h-100 shadow-sm">
                        <div class="unit-img-container">
                            <img src="https://www.hino.cl/hino/site/artic/20230131/imag/foto_0000000320230131183729/foto2.png"
                                class="card-img-top" alt="Camión Refrigerado">
                        </div>
                        <div class="card-body text-center">
                            <div class="unit-icon mb-3">
                                <i class="fas fa-temperature-low fa-2x text-warning"></i>
                            </div>
                            <h4 class="card-title">Camión Refrigerado</h4>
                            <ul class="unit-specs text-start">
                                <li><i class="fas fa-ruler-combined me-2 text-warning"></i> Caja: 6m largo x 2.4m ancho
                                    x 2.5m alto</li>
                                <li><i class="fas fa-thermometer-half me-2 text-warning"></i> Temperatura: -18°C a +25°C
                                </li>
                                <li><i class="fas fa-apple-alt me-2 text-warning"></i> Ideal para productos perecederos
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Unidad 6 -->
                <div class="col-md-6 col-lg-4 mb-4" data-aos="zoom-in" data-aos-delay="600">
                    <div class="unit-card h-100 shadow-sm">
                        <div class="unit-img-container">
                            <img src="https://th.bing.com/th/id/R.5221cae02befbef68e9af54a49fd299c?rik=hRpykR3hgFyl0Q&pid=ImgRaw&r=0"
                                class="card-img-top" alt="Plataforma">
                        </div>
                        <div class="card-body text-center">
                            <div class="unit-icon mb-3">
                                <i class="fas fa-truck-loading fa-2x text-warning"></i>
                            </div>
                            <h4 class="card-title">Plataforma</h4>
                            <ul class="unit-specs text-start">
                                <li><i class="fas fa-ruler-combined me-2 text-warning"></i> Dimensiones: 12m largo x
                                    2.5m ancho</li>
                                <li><i class="fas fa-weight me-2 text-warning"></i> Capacidad: hasta 25 toneladas</li>
                                <li><i class="fas fa-cogs me-2 text-warning"></i> Ideal para maquinaria y cargas
                                    sobredimensionadas</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-12 text-center">
                    <p class="mb-4">¿Necesitas una unidad especializada para tu carga? Contáctanos
                        para más información.
                    </p>
                    <a href="php/login.php" class="btn btn-warning btn-lg">Solicitar unidad</a>
                </div>
            </div>
        </div>
    </section>

    <div class="container" data-aos="fade-up" data-aos-delay="200">
        <div class="cards-container">
            <div class="card card1 formatcard1" data-aos="flip-left" data-aos-delay="300">
                <h1 class="card-title">Soluciones de Transporte</h1>
                <img src="https://th.bing.com/th/id/OIP.zXb5UndBqGx6vcM7oD1uCwHaDr?rs=1&pid=ImgDetMain"
                    alt="Soluciones de Transporte" class="img-fluid my-3">
                <p class="card-text formattxt">Ofrecemos servicios de transporte confiables y eficientes para garantizar
                    que tu carga llegue a tiempo y en perfectas condiciones.</p>
                <a class="green-button cardformat" href="#">Ver más</a>
            </div>
            <div class="card card2 formatcard1" data-aos="flip-up" data-aos-delay="400">
                <h1 class="card-title">Logística Personalizada</h1>
                <img src="https://th.bing.com/th/id/OIP.4YEWgqOa6rimX_KqiYI_RgHaE8?rs=1&pid=ImgDetMain"
                    alt="Logística Personalizada" class="img-fluid my-3">
                <p class="card-text formattxt">Diseñamos soluciones logísticas adaptadas a las necesidades específicas
                    de tu negocio, optimizando costos y tiempos.</p>
                <a class="green-button cardformat" href="#">Ver más</a>
            </div>
            <div class="card card3 formatcard1" data-aos="flip-right" data-aos-delay="500">
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
        <div id="certificaciones" class="py-5 scroll-offset">
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
        <div id="acerca-de" class="py-5 scroll-offset">
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <h1 class="section-title mb-4">Acerca de MENDEZ</h1>
                    <p class="lead">Somos una empresa líder en el sector de transporte y logística con más de 20 años de
                        experiencia.</p>

                    <h4 class="mt-4 black">Nuestra Historia</h4>
                    <p>Fundada en 2003, MENDEZ comenzó como una pequeña empresa familiar con tan solo 3 camiones. Hoy
                        contamos con una flota de más de 150 unidades y operamos en toda la República Mexicana,
                        ofreciendo soluciones integrales de transporte y logística.</p>

                    <h4 class="mt-4 black">Misión</h4>
                    <p>Brindar servicios de transporte y logística de la más alta calidad, eficiencia y seguridad,
                        superando las expectativas de nuestros clientes y contribuyendo al desarrollo económico del
                        país.</p>

                    <h4 class="mt-4 black">Visión</h4>
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
    <!-- Chatbot asistente con IA -->
    <div id="mendez-chatbot" class="chatbot-container">
        <div class="chatbot-toggle">
            <i class="fas fa-comment-alt"></i>
            <span class="notification-badge">1</span>
        </div>
        <div class="chatbot-box">
            <div class="chatbot-header">
                <img src="img/logo.png" alt="MENDEZ" class="chatbot-logo">
                <div>
                    <h4>Asistente MENDEZ</h4>
                    <p class="status"><span class="status-dot"></span> En línea</p>
                </div>
                <button class="minimize-btn"><i class="fas fa-minus"></i></button>
            </div>
            <div class="chatbot-messages" id="chatMessages">
                <div class="message bot-message">
                    <div class="message-content">
                        <p>¡Hola! Soy el asistente virtual de MENDEZ. ¿En qué puedo ayudarte hoy?</p>
                        <div class="suggestion-chips">
                            <button class="suggestion-chip">Cotizar envío</button>
                            <button class="suggestion-chip">Rastrear paquete</button>
                            <button class="suggestion-chip">Servicios</button>
                        </div>
                    </div>
                </div>
                <div class="message bot-message interactive-message">
                    <div class="message-content">
                        <p><strong>💡 Prueba estos ejemplos:</strong></p>
                        <div class="interactive-examples">
                            <div class="example-card">
                                <div class="example-icon"><i class="fas fa-truck"></i></div>
                                <div class="example-content">
                                    <h5>Cotizar envío</h5>
                                    <p>Obtén un precio estimado</p>
                                </div>
                            </div>
                            <div class="example-card">
                                <div class="example-icon"><i class="fas fa-search-location"></i></div>
                                <div class="example-content">
                                    <h5>Rastrear paquete</h5>
                                    <p>Consulta el estado de tu envío</p>
                                </div>
                            </div>
                            <div class="example-card">
                                <div class="example-icon"><i class="fas fa-hand-holding-usd"></i></div>
                                <div class="example-content">
                                    <h5>Comparar tarifas</h5>
                                    <p>Ver opciones por volumen</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="chatbot-input">
                <input type="text" id="userInput" placeholder="Escribe tu pregunta..." autocomplete="off">
                <button id="sendBtn"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>
    <?php include 'components/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Pace.js para loader profesional -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pace/1.2.4/pace.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
    <script src="js/animation.js"></script>
    <!-- Script para el chatbot -->
    <script src="js/chatbot.js"></script>
</body>

</html>