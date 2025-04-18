<?php
// Al inicio de tu script, ajusta estos límites si es necesario
ini_set('upload_max_filesize', '16M');
ini_set('post_max_size', '16M');
ini_set('memory_limit', '128M');

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

    // Procesar las imágenes
    $profile_photo = $_POST['profile_photo'] ?? '';
    $id_photo = $_POST['id_photo'] ?? '';

    // Convertir imágenes base64 para almacenamiento
    if (!empty($profile_photo)) {
        $profile_photo = str_replace('data:image/jpeg;base64,', '', $profile_photo);
        $profile_photo = str_replace(' ', '+', $profile_photo);
        $profile_photo_binary = base64_decode($profile_photo);

        if (!empty($profile_photo_binary)) {
            // Crear una imagen desde los datos binarios
            $img = imagecreatefromstring($profile_photo_binary);

            // Redimensionar si es necesario (máximo 800px de ancho)
            $width = imagesx($img);
            $height = imagesy($img);

            if ($width > 800) {
                $new_width = 800;
                $new_height = floor($height * ($new_width / $width));
                $tmp_img = imagecreatetruecolor($new_width, $new_height);
                imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                $img = $tmp_img;
            }

            // Guardar en buffer con calidad reducida (75%)
            ob_start();
            imagejpeg($img, null, 75);
            $profile_photo_binary = ob_get_clean();

            // Liberar memoria
            imagedestroy($img);
        }
    }

    if (!empty($id_photo)) {
        $id_photo = str_replace('data:image/jpeg;base64,', '', $id_photo);
        $id_photo = str_replace(' ', '+', $id_photo);
        $id_photo_binary = base64_decode($id_photo);

        if (!empty($id_photo_binary)) {
            // Crear una imagen desde los datos binarios
            $img = imagecreatefromstring($id_photo_binary);

            // Redimensionar si es necesario (máximo 800px de ancho)
            $width = imagesx($img);
            $height = imagesy($img);

            if ($width > 800) {
                $new_width = 800;
                $new_height = floor($height * ($new_width / $width));
                $tmp_img = imagecreatetruecolor($new_width, $new_height);
                imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                $img = $tmp_img;
            }

            // Guardar en buffer con calidad reducida (75%)
            ob_start();
            imagejpeg($img, null, 75);
            $id_photo_binary = ob_get_clean();

            // Liberar memoria
            imagedestroy($img);
        }
    }

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

    // Validar que las fotos estén presentes
    if (empty($profile_photo)) {
        $errores[] = "La foto de perfil es obligatoria.";
    }

    if (empty($id_photo)) {
        $errores[] = "La foto de identificación oficial es obligatoria.";
    }

    // Si hay errores, mostrarlos
    if (!empty($errores)) {
        $error = implode("<br>", $errores);
    } else {
        // Actualizar información en la base de datos
        try {
            $conn->begin_transaction();

            $usuario_id = $_SESSION['usuario_id'];

            // Alternativa: Guardar archivos en el sistema de archivos
            $upload_dir = '../uploads/repartidores/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Generar nombres de archivo únicos
            $profile_photo_filename = 'profile_' . $usuario_id . '_' . time() . '.jpg';
            $id_photo_filename = 'id_' . $usuario_id . '_' . time() . '.jpg';

            // Guardar las imágenes como archivos
            file_put_contents($upload_dir . $profile_photo_filename, $profile_photo_binary);
            file_put_contents($upload_dir . $id_photo_filename, $id_photo_binary);

            // Actualizar datos del repartidor
            $stmt = $conn->prepare("UPDATE repartidores SET 
                edad = ?, tipo_licencia = ?, num_licencia = ?, exp_vigencia = ?, 
                anos_experiencia = ?, vehiculo = ?, placa = ?, capacidad_carga = ?,
                certificacion_medica = ?, conocimiento_rutas = ?, certificacion_carga = ?, 
                antecedentes_penales = ?, profile_photo = ?, id_photo = ?, status = 'pendiente' 
                WHERE usuario_id = ?");

            $stmt->bind_param(
                "isssiisdiiiibbi",  // Notar los 'b' para los datos binarios
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
                $profile_photo_binary,
                $id_photo_binary,
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
        :root {
            --primary-color: #0A2647;
            --secondary-color: #144272;
            --accent-color: #2C74B3;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --danger-color: #dc3545;
            --success-color: #28a745;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--dark-color);
            color: #fff;
            line-height: 1.6;
        }

        .login-container {
            max-width: 800px !important;
            margin: 20px auto;
            padding: 0;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            overflow: hidden;
        }

        .card-content {
            background-color: var(--dark-color);
            padding: 0;
        }

        .logo-container {
            background-color: var(--primary-color);
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }

        .logo-container img {
            max-width: 180px;
            background-color: white;
            padding: 10px;
            border-radius: 10px;
        }

        h2 {
            color: white;
            text-align: center;
            margin-bottom: 5px;
            font-size: 24px;
            font-weight: 600;
        }

        p {
            color: #adb5bd;
            text-align: center;
            margin-bottom: 25px;
            padding: 0 20px;
            font-size: 15px;
        }

        .form-section {
            margin: 15px;
            padding: 20px;
            background-color: #232d36;
            border-radius: 10px;
            border-left: 4px solid var(--accent-color);
            margin-bottom: 25px;
        }

        .section-title {
            margin-bottom: 20px;
            color: white;
            font-weight: 600;
            font-size: 18px;
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 10px;
            color: var(--accent-color);
            font-size: 22px;
        }

        .form-group {
            margin-bottom: 22px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #e9ecef;
            font-weight: 500;
            font-size: 15px;
        }

        .required-field::after {
            content: " *";
            color: var(--danger-color);
            font-weight: bold;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        input[type="text"],
        input[type="number"],
        input[type="date"],
        select {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 1px solid #495057;
            border-radius: 6px;
            font-size: 16px;
            background-color: #2c3640;
            color: white;
            transition: all 0.3s ease;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(44, 116, 179, 0.25);
        }

        select {
            appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="%23fff" viewBox="0 0 16 16"><path d="M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z"/></svg>');
            background-position: calc(100% - 12px) center;
            background-repeat: no-repeat;
            background-size: 12px;
            padding-right: 30px;
        }

        .form-check {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .form-check input[type="checkbox"] {
            margin-right: 10px;
            width: 18px;
            height: 18px;
            accent-color: var(--accent-color);
        }

        .form-check label {
            font-size: 15px;
            color: #e9ecef;
            cursor: pointer;
        }

        button[type="submit"] {
            display: block;
            width: calc(100% - 30px);
            margin: 30px 15px;
            padding: 14px;
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button[type="submit"]:hover {
            background-color: var(--secondary-color);
        }

        .alert {
            margin: 0 15px 20px;
            padding: 15px;
            border-radius: 6px;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.2);
            color: #f8d7da;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.2);
            color: #d4edda;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        /* Estilo específico para dispositivos más grandes */
        @media (min-width: 768px) {
            .login-container {
                margin: 40px auto;
            }
        }

        /* Estilos para el sistema de cámara */
        .camera-container {
            margin-top: 15px;
            background-color: #1a242f;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #495057;
        }

        .video-wrapper {
            position: relative;
            width: 100%;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .video-wrapper video,
        .video-wrapper canvas {
            width: 100%;
            max-height: 300px;
            background-color: #000;
            object-fit: cover;
            border-radius: 6px;
        }

        .captured-image {
            width: 100%;
            margin-bottom: 15px;
        }

        .captured-image img {
            width: 100%;
            max-height: 300px;
            object-fit: contain;
            border-radius: 6px;
            border: 2px solid var(--accent-color);
        }

        .camera-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        .camera-btn {
            flex: 1;
            min-width: 120px;
            padding: 10px;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: background-color 0.3s;
        }

        .camera-btn:hover:not(:disabled) {
            background-color: var(--accent-color);
        }

        .camera-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .camera-instructions {
            background-color: rgba(44, 116, 179, 0.1);
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            border-left: 3px solid var(--accent-color);
        }

        .camera-instructions p {
            color: #adb5bd;
            margin: 0;
            font-size: 14px;
            text-align: left;
        }
    </style>
</head>

<body>
    <div class="login-container">
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
                            <input type="number" id="edad" name="edad" min="18" max="65" placeholder="Tu edad" required>
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
                            <input type="text" id="num_licencia" name="num_licencia" placeholder="Número de tu licencia"
                                required>
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
                                placeholder="Años conduciendo vehículos pesados" required>
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
                            <input type="text" id="placa" name="placa" placeholder="Ej: ABC-1234" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="capacidad_carga" class="required-field">Capacidad de Carga (toneladas)</label>
                        <div class="input-with-icon">
                            <i class="fas fa-weight"></i>
                            <input type="number" id="capacidad_carga" name="capacidad_carga" step="0.1" min="0.5"
                                max="40" placeholder="Ej: 10.5" required>
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

                <!-- Captura de Foto de Perfil -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-camera"></i> Foto de Perfil</h3>

                    <div class="camera-container">
                        <div class="video-wrapper">
                            <video id="video" width="100%" height="auto" autoplay playsinline></video>
                            <canvas id="canvas" style="display:none;"></canvas>
                        </div>

                        <div class="captured-image" style="display:none;">
                            <img id="captured-photo" src="" alt="Foto capturada">
                        </div>

                        <div class="camera-buttons">
                            <button type="button" id="startCamera" class="camera-btn">
                                <i class="fas fa-camera"></i> Iniciar Cámara
                            </button>
                            <button type="button" id="capturePhoto" class="camera-btn" disabled>
                                <i class="fas fa-camera-retro"></i> Capturar
                            </button>
                            <button type="button" id="retakePhoto" class="camera-btn" style="display:none;">
                                <i class="fas fa-redo"></i> Volver a Tomar
                            </button>
                        </div>

                        <div class="camera-instructions">
                            <p><i class="fas fa-info-circle"></i> Coloca tu rostro en el centro y asegúrate de tener
                                buena iluminación.</p>
                        </div>

                        <input type="hidden" name="profile_photo" id="profile_photo_input">
                    </div>
                </div>

                <!-- Captura de Identificación Oficial -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-id-card"></i> Documento de Identidad</h3>

                    <div class="camera-container">
                        <div class="video-wrapper">
                            <video id="video-id" width="100%" height="auto" autoplay playsinline></video>
                            <canvas id="canvas-id" style="display:none;"></canvas>
                        </div>

                        <div class="captured-image" id="id-photo-container" style="display:none;">
                            <img id="captured-id" src="" alt="Identificación capturada">
                        </div>

                        <div class="camera-buttons">
                            <button type="button" id="startCameraId" class="camera-btn">
                                <i class="fas fa-id-card"></i> Capturar Identificación
                            </button>
                            <button type="button" id="captureId" class="camera-btn" disabled>
                                <i class="fas fa-camera-retro"></i> Tomar Foto
                            </button>
                            <button type="button" id="retakeId" class="camera-btn" style="display:none;">
                                <i class="fas fa-redo"></i> Volver a Tomar
                            </button>
                        </div>

                        <div class="camera-instructions">
                            <p><i class="fas fa-info-circle"></i> Coloca tu identificación sobre una superficie plana
                                con buena luz. Asegúrate que sea legible.</p>
                        </div>

                        <input type="hidden" name="id_photo" id="id_photo_input">
                    </div>
                </div>

                <button type="submit" name="complete_profile">Enviar Solicitud</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Variables para la foto de perfil
            const video = document.getElementById('video');
            const canvas = document.getElementById('canvas');
            const startCameraBtn = document.getElementById('startCamera');
            const captureBtn = document.getElementById('capturePhoto');
            const retakeBtn = document.getElementById('retakePhoto');
            const capturedPhoto = document.getElementById('captured-photo');
            const photoInput = document.getElementById('profile_photo_input');

            // Variables para la foto de identificación
            const videoId = document.getElementById('video-id');
            const canvasId = document.getElementById('canvas-id');
            const startCameraIdBtn = document.getElementById('startCameraId');
            const captureIdBtn = document.getElementById('captureId');
            const retakeIdBtn = document.getElementById('retakeId');
            const capturedId = document.getElementById('captured-id');
            const idPhotoInput = document.getElementById('id_photo_input');

            let stream = null;

            // Función para iniciar la cámara (foto de perfil)
            startCameraBtn.addEventListener('click', function() {
                // Detener cualquier stream anterior
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }

                // Opciones para la cámara frontal en móviles
                const constraints = {
                    video: {
                        facingMode: 'user',
                        width: {
                            ideal: 1280
                        },
                        height: {
                            ideal: 720
                        }
                    },
                    audio: false
                };

                // Iniciar la cámara
                navigator.mediaDevices.getUserMedia(constraints)
                    .then(function(mediaStream) {
                        stream = mediaStream;
                        video.srcObject = mediaStream;
                        video.style.display = 'block';
                        capturedPhoto.parentElement.style.display = 'none';
                        captureBtn.disabled = false;
                        startCameraBtn.disabled = true;
                        retakeBtn.style.display = 'none';
                    })
                    .catch(function(err) {
                        console.log("Error al acceder a la cámara: " + err);
                        alert(
                            "No se pudo acceder a la cámara. Asegúrate de dar permisos y usar un navegador compatible."
                        );
                    });
            });

            // Función para capturar foto de perfil
            captureBtn.addEventListener('click', function() {
                const context = canvas.getContext('2d');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                context.drawImage(video, 0, 0, canvas.width, canvas.height);

                const photoData = canvas.toDataURL('image/jpeg', 0.8);
                capturedPhoto.src = photoData;
                photoInput.value = photoData; // Guardar la imagen en formato base64

                video.style.display = 'none';
                capturedPhoto.parentElement.style.display = 'block';
                captureBtn.disabled = true;
                retakeBtn.style.display = 'inline-block';

                // Detener el streaming de video
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }
            });

            // Función para volver a tomar la foto de perfil
            retakeBtn.addEventListener('click', function() {
                startCameraBtn.click();
            });

            // Función para iniciar la cámara (foto de identificación)
            startCameraIdBtn.addEventListener('click', function() {
                // Detener cualquier stream anterior
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }

                // Opciones para la cámara trasera en móviles (mejor para documentos)
                const constraints = {
                    video: {
                        facingMode: {
                            exact: 'environment'
                        },
                        width: {
                            ideal: 1920
                        },
                        height: {
                            ideal: 1080
                        }
                    },
                    audio: false
                };

                // Intentar primero con la cámara trasera
                navigator.mediaDevices.getUserMedia(constraints)
                    .then(function(mediaStream) {
                        stream = mediaStream;
                        videoId.srcObject = mediaStream;
                        videoId.style.display = 'block';
                        capturedId.parentElement.style.display = 'none';
                        captureIdBtn.disabled = false;
                        startCameraIdBtn.disabled = true;
                        retakeIdBtn.style.display = 'none';
                    })
                    .catch(function(err) {
                        // Si falla, intentar con cualquier cámara disponible
                        navigator.mediaDevices.getUserMedia({
                                video: true,
                                audio: false
                            })
                            .then(function(mediaStream) {
                                stream = mediaStream;
                                videoId.srcObject = mediaStream;
                                videoId.style.display = 'block';
                                capturedId.parentElement.style.display = 'none';
                                captureIdBtn.disabled = false;
                                startCameraIdBtn.disabled = true;
                                retakeIdBtn.style.display = 'none';
                            })
                            .catch(function(err) {
                                console.log("Error al acceder a la cámara: " + err);
                                alert(
                                    "No se pudo acceder a la cámara. Asegúrate de dar permisos y usar un navegador compatible."
                                );
                            });
                    });
            });

            // Función para capturar foto de identificación
            captureIdBtn.addEventListener('click', function() {
                const context = canvasId.getContext('2d');
                canvasId.width = videoId.videoWidth;
                canvasId.height = videoId.videoHeight;
                context.drawImage(videoId, 0, 0, canvasId.width, canvasId.height);

                const idData = canvasId.toDataURL('image/jpeg', 0.8);
                capturedId.src = idData;
                idPhotoInput.value = idData; // Guardar la imagen en formato base64

                videoId.style.display = 'none';
                capturedId.parentElement.style.display = 'block';
                captureIdBtn.disabled = true;
                retakeIdBtn.style.display = 'inline-block';

                // Detener el streaming de video
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }
            });

            // Función para volver a tomar la foto de identificación
            retakeIdBtn.addEventListener('click', function() {
                startCameraIdBtn.click();
            });

            // Validación del formulario
            document.querySelector('form').addEventListener('submit', function(e) {
                // Verificar si se tomaron las fotos requeridas
                if (!photoInput.value) {
                    e.preventDefault();
                    alert('Por favor, toma una foto de perfil antes de enviar el formulario.');
                    return false;
                }

                if (!idPhotoInput.value) {
                    e.preventDefault();
                    alert(
                        'Por favor, toma una foto de tu identificación oficial antes de enviar el formulario.'
                    );
                    return false;
                }

                return true;
            });
        });
    </script>
</body>

</html>