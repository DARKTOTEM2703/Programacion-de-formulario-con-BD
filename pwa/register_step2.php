<?php
session_start();
require_once '../components/db_connection.php';

// Verificar si el usuario está autenticado pero falta completar su perfil
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['repartidor_pendiente'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// Procesar el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Datos personales
    $edad = filter_var($_POST['edad'], FILTER_VALIDATE_INT);
    $tipo_licencia = htmlspecialchars($_POST['tipo_licencia']);
    $num_licencia = htmlspecialchars($_POST['num_licencia']);
    $exp_vigencia = htmlspecialchars($_POST['exp_vigencia']);
    $anos_experiencia = filter_var($_POST['anos_experiencia'], FILTER_VALIDATE_INT);

    // Datos del vehículo
    $tipo_vehiculo = htmlspecialchars($_POST['tipo_vehiculo']);
    $placa = htmlspecialchars($_POST['placa']);
    $capacidad_carga = filter_var($_POST['capacidad_carga'], FILTER_VALIDATE_FLOAT);

    // Documentos y certificaciones
    $certificacion_medica = isset($_POST['certificacion_medica']) ? 1 : 0;
    $conocimiento_rutas = isset($_POST['conocimiento_rutas']) ? 1 : 0;
    $certificacion_carga = isset($_POST['certificacion_carga']) ? 1 : 0;
    $antecedentes_penales = isset($_POST['antecedentes_penales']) ? 1 : 0;

    // Validaciones
    $errores = [];

    // Verificar edad mínima (21 años para camiones pesados)
    if ($edad < 21) {
        $errores[] = "Debes tener al menos 21 años para conducir camiones de alto peso.";
    }

    // Verificar tipo de licencia (Tipo E para carga pesada en México)
    if ($tipo_licencia != "E" && $tipo_licencia != "Federal E") {
        $errores[] = "Se requiere licencia tipo E o Federal E para conducir camiones de alto peso.";
    }

    // Verificar experiencia mínima (2 años)
    if ($anos_experiencia < 2) {
        $errores[] = "Se requieren al menos 2 años de experiencia para este puesto.";
    }

    // Si hay errores, mostrarlos
    if (!empty($errores)) {
        $error = implode("<br>", $errores);
    } else {
        // Actualizar información en la base de datos
        try {
            $conn->begin_transaction();

            $usuario_id = $_SESSION['usuario_id'];

            // Actualizar datos del repartidor
            $stmt = $conn->prepare("UPDATE repartidores SET 
                edad = ?, tipo_licencia = ?, num_licencia = ?, exp_vigencia = ?, 
                anos_experiencia = ?, vehiculo = ?, placa = ?, capacidad_carga = ?,
                certificacion_medica = ?, conocimiento_rutas = ?, certificacion_carga = ?, 
                antecedentes_penales = ?, status = 'pendiente' 
                WHERE usuario_id = ?");

            $stmt->bind_param(
                "isssiisdiiiii",
                $edad,
                $tipo_licencia,
                $num_licencia,
                $exp_vigencia,
                $anos_experiencia,
                $tipo_vehiculo,
                $placa,
                $capacidad_carga,
                $certificacion_medica,
                $conocimiento_rutas,
                $certificacion_carga,
                $antecedentes_penales,
                $usuario_id
            );

            $stmt->execute();

            // Confirmar cambios
            $conn->commit();

            // Actualizar sesión
            unset($_SESSION['repartidor_pendiente']);
            $_SESSION['perfil_completado'] = true;

            $success = "¡Perfil completado correctamente! Tu información será revisada por un administrador.";

            // Redireccionar después de 3 segundos
            header("refresh:3;url=pending_approval.php");
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error al guardar los datos: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completar Perfil de Repartidor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <style>
        .form-section {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        .section-title {
            margin-bottom: 15px;
            color: #0d6efd;
            font-weight: bold;
        }

        .required-field::after {
            content: " *";
            color: red;
        }

        .alert {
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>

<body>
    <div class="login-container" style="max-width: 600px;">
        <div class="card-content">
            <div class="logo-container">
                <img src="assets/icons/logo.png" alt="MENDEZ Logo">
            </div>

            <h2>Completar Perfil de Repartidor</h2>
            <p>Por favor, completa la siguiente información para verificar que cumples con los requisitos para ser
                repartidor de camiones de alto peso.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="post" action="">
                <!-- Datos personales -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-user"></i> Datos Personales</h3>

                    <div class="form-group">
                        <label for="edad" class="required-field">Edad</label>
                        <div class="input-with-icon">
                            <i class="fas fa-birthday-cake"></i>
                            <input type="number" id="edad" name="edad" min="18" max="65" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="tipo_licencia" class="required-field">Tipo de Licencia</label>
                        <div class="input-with-icon">
                            <i class="fas fa-id-card"></i>
                            <select id="tipo_licencia" name="tipo_licencia" required>
                                <option value="">Selecciona el tipo de licencia</option>
                                <option value="A">Tipo A (Particular)</option>
                                <option value="B">Tipo B (Taxi)</option>
                                <option value="C">Tipo C (Camiones pequeños)</option>
                                <option value="D">Tipo D (Autobuses)</option>
                                <option value="E">Tipo E (Camiones pesados)</option>
                                <option value="Federal E">Federal Tipo E (Carga)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="num_licencia" class="required-field">Número de Licencia</label>
                        <div class="input-with-icon">
                            <i class="fas fa-id-card"></i>
                            <input type="text" id="num_licencia" name="num_licencia" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="exp_vigencia" class="required-field">Fecha de Vencimiento</label>
                        <div class="input-with-icon">
                            <i class="fas fa-calendar"></i>
                            <input type="date" id="exp_vigencia" name="exp_vigencia" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="anos_experiencia" class="required-field">Años de Experiencia</label>
                        <div class="input-with-icon">
                            <i class="fas fa-business-time"></i>
                            <input type="number" id="anos_experiencia" name="anos_experiencia" min="0" max="40"
                                required>
                        </div>
                    </div>
                </div>

                <!-- Datos del vehículo -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-truck"></i> Datos del Vehículo</h3>

                    <div class="form-group">
                        <label for="tipo_vehiculo" class="required-field">Tipo de Vehículo</label>
                        <div class="input-with-icon">
                            <i class="fas fa-truck-moving"></i>
                            <select id="tipo_vehiculo" name="tipo_vehiculo" required>
                                <option value="">Selecciona el tipo de vehículo</option>
                                <option value="Camion 5 Toneladas">Camión 5 Toneladas</option>
                                <option value="Camion Torton">Camión Torton</option>
                                <option value="Trailer 48 pies">Tráiler 48 pies</option>
                                <option value="Camioneta 3.5 Toneladas">Camioneta 3.5 Toneladas</option>
                                <option value="Camion Refrigerado">Camión Refrigerado</option>
                                <option value="Plataforma">Plataforma</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="placa" class="required-field">Placa del Vehículo</label>
                        <div class="input-with-icon">
                            <i class="fas fa-car"></i>
                            <input type="text" id="placa" name="placa" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="capacidad_carga" class="required-field">Capacidad de Carga (toneladas)</label>
                        <div class="input-with-icon">
                            <i class="fas fa-weight"></i>
                            <input type="number" id="capacidad_carga" name="capacidad_carga" step="0.1" min="0.5"
                                max="40" required>
                        </div>
                    </div>
                </div>

                <!-- Certificaciones y Documentos -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-certificate"></i> Certificaciones y Documentos</h3>

                    <div class="form-check">
                        <input type="checkbox" id="certificacion_medica" name="certificacion_medica" value="1">
                        <label for="certificacion_medica">Tengo certificación médica vigente</label>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" id="conocimiento_rutas" name="conocimiento_rutas" value="1">
                        <label for="conocimiento_rutas">Tengo conocimiento de rutas nacionales</label>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" id="certificacion_carga" name="certificacion_carga" value="1">
                        <label for="certificacion_carga">Tengo certificación para manejo de cargas especiales</label>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" id="antecedentes_penales" name="antecedentes_penales" value="1">
                        <label for="antecedentes_penales">Cuento con carta de antecedentes no penales</label>
                    </div>
                </div>

                <button type="submit" name="complete_profile">Enviar Solicitud</button>
            </form>
        </div>
    </div>
</body>

</html>