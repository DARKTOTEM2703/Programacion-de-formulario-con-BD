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
                <a href="php/login.php" class="btn btn-outline-warning ms-3" target="_blank">Iniciar Sesión</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-overlay"></div>
        <div class="container text-center hero-content">
            <h1 class="display-3 fw-bold">MENDEZ</h1>
            <p class="lead">Transportes logisticos mendez</p>
            <a href="https://www.youtube.com/watch?v=I56LwrvY_lk" class="btn btn-light btn-lg" target="_blank"
                rel="noopener noreferrer">Ver Video ▶</a>
        </div>
    </section>

    <?php
    include 'components/footer.php';
    ?>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

    <body>

</html>