<?php
// filepath: c:\xampp\htdocs\Programacion-de-formulario-con-BD\api\meta-ai.php
session_start();
header('Content-Type: application/json');
require_once '../components/db_connection.php'; // Conexión a la BD

// Cargar variables de entorno
$env_path = __DIR__ . '/../.env';
if (file_exists($env_path)) {
    $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '//') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Recibir datos
$input = json_decode(file_get_contents('php://input'), true);
$query = $input['query'] ?? '';
$conversation_history = $input['history'] ?? [];

// Verificar autenticación y obtener datos del usuario
$user_context = "";
$is_authenticated = false;
$user_data = [];

if (isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];
    $is_authenticated = true;
    
    // Consultar información detallada del usuario
    $stmt = $conn->prepare("SELECT u.*, r.telefono, r.status as repartidor_status 
                           FROM usuarios u 
                           LEFT JOIN repartidores r ON u.id = r.usuario_id 
                           WHERE u.id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        
        // Enriquecer contexto con datos del usuario
        $user_context = "\nEl usuario está autenticado como {$user_data['nombre_usuario']}.\n";
        $user_context .= "Rol: " . getRolName($user_data['rol_id']) . "\n";
        
        // Si es cliente, obtener sus envíos
        if ($user_data['rol_id'] == 2 || $user_data['rol_id'] == 4) {
            $envios_stmt = $conn->prepare("
                SELECT id, tracking_number, origin, destination, status, created_at 
                FROM envios 
                WHERE usuario_id = ? 
                ORDER BY created_at DESC LIMIT 5
            ");
            $envios_stmt->bind_param("i", $usuario_id);
            $envios_stmt->execute();
            $envios_result = $envios_stmt->get_result();
            
            if ($envios_result->num_rows > 0) {
                $user_context .= "Envíos recientes:\n";
                while ($envio = $envios_result->fetch_assoc()) {
                    $user_context .= "- Tracking #{$envio['tracking_number']}: {$envio['origin']} → {$envio['destination']} ({$envio['status']})\n";
                }
            } else {
                $user_context .= "El usuario no tiene envíos registrados.\n";
            }
        }
        
        // Si es repartidor, obtener sus entregas asignadas
        if ($user_data['rol_id'] == 3 || $user_data['rol_id'] == 4) {
            $entregas_stmt = $conn->prepare("
                SELECT e.id, e.tracking_number, e.destination, e.status  
                FROM envios e
                JOIN repartidores_envios re ON e.id = re.envio_id
                WHERE re.usuario_id = ? AND e.status != 'Entregado'
                LIMIT 5
            ");
            $entregas_stmt->bind_param("i", $usuario_id);
            $entregas_stmt->execute();
            $entregas_result = $entregas_stmt->get_result();
            
            if ($entregas_result->num_rows > 0) {
                $user_context .= "Entregas asignadas:\n";
                while ($entrega = $entregas_result->fetch_assoc()) {
                    $user_context .= "- Tracking #{$entrega['tracking_number']}: {$entrega['destination']} ({$entrega['status']})\n";
                }
            }
        }
    }
}

// Función para obtener el nombre del rol
function getRolName($rol_id) {
    switch($rol_id) {
        case 1: return "Administrador";
        case 2: return "Cliente";
        case 3: return "Repartidor";
        case 4: return "Cliente y Repartidor";
        default: return "Usuario";
    }
}

// GEMINI API Key
$GEMINI_API_KEY = getenv(name: 'GEMINI_API_KEY');
$GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';

// Verificación de API Key
if (empty($GEMINI_API_KEY)) {
    echo json_encode([
        'response' => 'Error: API key de Gemini no configurada.',
        'suggestions' => ['Contactar soporte'],
        'intent' => 'error'
    ]);
    exit;
}

// Contexto básico del sistema
$system_context = "Eres un asistente virtual para MENDEZ Transportes, una empresa mexicana líder en servicios de logística y transporte. 
Tu objetivo es ayudar a los usuarios del sitio web proporcionando información sobre los servicios, 
respondiendo preguntas frecuentes y guiando a los usuarios sobre cómo contactar o utilizar los servicios de MENDEZ.";

// Agregar contexto adicional según autenticación
if ($is_authenticated) {
    $system_context .= "\n\n" . $user_context;
    $system_context .= "\nPuedes proporcionar información personalizada a este usuario, incluyendo detalles sobre sus envíos, 
    entregas pendientes y otra información relevante. Utiliza su nombre para personalizar las respuestas.";
} else {
    $system_context .= "\n\nEl usuario NO está autenticado. Debes sugerir que inicie sesión para 
    acceder a información personalizada, rastrear envíos o realizar cotizaciones detalladas.";
}

// Añadir instrucciones adicionales
$system_context .= "\n\nInformación sobre MENDEZ:
- Ofrece servicios de transporte nacional, logística integral, almacenaje, transporte refrigerado, carga especializada y paquetería express.
- Cuenta con una flota de más de 150 unidades de diferentes tipos (camiones de 5 toneladas, camiones torton, tráileres, etc.).
- Tiene más de 20 años de experiencia en el mercado.
- Cobertura en toda la República Mexicana.
- Cuenta con certificaciones ISO 9001, ISO 14001 y OHSAS 18001.";

// Preparar mensajes para Gemini
$parts = [];

// Añadir contexto del sistema como primer mensaje
$parts[] = [
    'text' => $system_context
];

// Añadir historial de conversación si existe
if (!empty($conversation_history)) {
    foreach ($conversation_history as $message) {
        $parts[] = [
            'text' => ($message['role'] == 'user' ? "Usuario: " : "Asistente: ") . $message['content']
        ];
    }
}

// Añadir consulta actual
$parts[] = [
    'text' => "Usuario: " . $query . "\n\nAsistente:"
];

// Preparar datos para Gemini
$data = [
    'contents' => [
        [
            'parts' => $parts
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 300,
        'topP' => 0.95,
        'topK' => 40
    ],
    'safetySettings' => [
        [
            'category' => 'HARM_CATEGORY_HARASSMENT',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ],
        [
            'category' => 'HARM_CATEGORY_HATE_SPEECH',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ],
        [
            'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ],
        [
            'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ]
    ]
];

// Configurar la solicitud a la API de Gemini
$ch = curl_init($GEMINI_API_URL . "?key=" . $GEMINI_API_KEY);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// Ejecutar la solicitud
$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

// Procesar la respuesta de Gemini
if ($err) {
    // Usar el sistema de respuestas predefinidas como respaldo
    $intent = analyzeIntent($query);
    $aiResponse = getIntelligentResponse($intent, $query);
} else {
    $responseData = json_decode($response, true);

    // Verificar si la respuesta es válida
    if (isset($responseData['candidates']) && isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        $aiResponse = $responseData['candidates'][0]['content']['parts'][0]['text'];
    } else {
        // Si hay un error en la respuesta, usar sistema de respaldo
        $intent = analyzeIntent($query);
        $aiResponse = getIntelligentResponse($intent, $query);
    }
}

// Usar un analizador de intención simple basado en palabras clave (mantener tu función actual)
function analyzeIntent($query)
{
    $query = strtolower($query);

    if (strpos($query, 'cotiz') !== false || strpos($query, 'precio') !== false || strpos($query, 'costo') !== false || strpos($query, 'presupuesto') !== false) {
        return 'cotizacion';
    } elseif (strpos($query, 'rastr') !== false || strpos($query, 'track') !== false || strpos($query, 'segui') !== false || strpos($query, 'donde') !== false) {
        return 'rastreo';
    } elseif (strpos($query, 'servic') !== false || strpos($query, 'ofrec') !== false) {
        return 'servicios';
    } elseif (strpos($query, 'unidad') !== false || strpos($query, 'camion') !== false || strpos($query, 'flota') !== false) {
        return 'unidades';
    } elseif (strpos($query, 'contact') !== false || strpos($query, 'telefono') !== false || strpos($query, 'mail') !== false) {
        return 'contacto';
    } elseif (strpos($query, 'login') !== false || strpos($query, 'iniciar') !== false || strpos($query, 'sesion') !== false || strpos($query, 'registr') !== false) {
        return 'login';
    } else {
        return 'general';
    }
}

// Generar sugerencias basadas en la intención detectada (mantener tu función actual)
function generateSuggestions($intent)
{
    switch ($intent) {
        case 'cotizacion':
            return ['Ver servicios', 'Registrarse para cotizar', 'Hablar con un asesor'];
        case 'rastreo':
            return ['Iniciar sesión para rastrear', 'Contactar soporte', 'Servicios de seguimiento'];
        case 'servicios':
            return ['Transporte nacional', 'Logística integral', 'Carga especializada'];
        case 'unidades':
            return ['Tipos de camiones', 'Capacidades de carga', 'Refrigerados'];
        case 'contacto':
            return ['Llamar ahora', 'Enviar email', 'Visitar oficinas'];
        case 'login':
            return ['Iniciar sesión', 'Crear cuenta', 'Recuperar contraseña'];
        default:
            return ['Servicios', 'Cotizar envío', 'Contactar'];
    }
}

// Respuestas predefinidas para el sistema de respaldo
function getIntelligentResponse($intent, $query)
{
    switch ($intent) {
        case 'cotizacion':
            return "Para brindarte una cotización personalizada, necesitamos conocer origen, destino, tipo de carga y peso aproximado. ¿Podrías proporcionarnos estos datos? Para cotizaciones precisas, te recomendamos registrarte en nuestra plataforma.";
        case 'rastreo':
            return "Para rastrear tu envío, necesitas iniciar sesión en tu cuenta MENDEZ. Ahí podrás ver la ubicación y estado de todos tus envíos en tiempo real.";
        case 'servicios':
            return "MENDEZ ofrece diversos servicios de transporte y logística: transporte nacional de carga, logística integral, almacenaje, transporte refrigerado, carga especializada y paquetería express. ¿Sobre cuál te gustaría más información?";
        case 'unidades':
            return "Contamos con una flota de más de 150 unidades de diferentes tipos: camiones de 5 toneladas, camiones torton, tráileres de 48 pies, unidades refrigeradas, plataformas y más. Todas nuestras unidades cuentan con GPS y sistemas de seguridad avanzados.";
        case 'contacto':
            return "Puedes contactarnos por teléfono al (55) 1234-5678, por correo a contacto@mendez.mx o visitar nuestras oficinas en Av. Principal #123, CDMX. Nuestro horario de atención es de lunes a viernes de 9:00 a 18:00 horas.";
        case 'login':
            return "Para iniciar sesión o registrarte en nuestra plataforma, haz clic en el botón 'Iniciar sesión' en la parte superior de la página. Si ya tienes cuenta, ingresa tus credenciales; si no, puedes crear una nueva cuenta fácilmente.";
        default:
            return "Soy el asistente virtual de MENDEZ Transportes. Puedo ayudarte con información sobre nuestros servicios de transporte, cotizaciones y más. ¿En qué puedo asistirte hoy?";
    }
}

// Analizar la intención y generar sugerencias
$intent = analyzeIntent($query);
$suggestions = generateSuggestions($intent);

// Enviar respuesta
echo json_encode([
    'response' => $aiResponse,
    'suggestions' => $suggestions,
    'intent' => $intent,
    'is_authenticated' => $is_authenticated,
    'user_name' => $is_authenticated ? $user_data['nombre_usuario'] : null
]);