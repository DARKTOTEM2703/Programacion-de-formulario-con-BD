-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 09-08-2025 a las 14:22:06
-- Versión del servidor: 10.4.28-MariaDB
-- Versión de PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */
;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */
;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */
;
/*!40101 SET NAMES utf8mb4 */
;

--
-- Base de datos: `gestion_envios`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contratos`
--

CREATE TABLE `contratos` (
    `id` int(11) NOT NULL,
    `usuario_id` int(11) NOT NULL,
    `tipo` varchar(50) NOT NULL,
    `nombre` varchar(100) NOT NULL,
    `archivo` varchar(255) NOT NULL,
    `estatus` enum(
        'activo',
        'vencido',
        'cancelado'
    ) DEFAULT 'activo',
    `fecha_inicio` date NOT NULL,
    `fecha_fin` date DEFAULT NULL,
    `firmado_cliente` tinyint(1) DEFAULT 0,
    `firmado_empresa` tinyint(1) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones`
--

CREATE TABLE `cotizaciones` (
    `id` int(11) NOT NULL,
    `usuario_id` int(11) NOT NULL,
    `origen` varchar(255) NOT NULL,
    `destino` varchar(255) NOT NULL,
    `tipo_servicio` varchar(50) NOT NULL,
    `peso` decimal(10, 2) NOT NULL,
    `dimensiones` varchar(50) DEFAULT NULL,
    `valor_declarado` decimal(12, 2) DEFAULT NULL,
    `precio_estimado` decimal(10, 2) NOT NULL,
    `estatus` enum(
        'pendiente',
        'aprobada',
        'rechazada',
        'convertida'
    ) DEFAULT 'pendiente',
    `fecha_validez` date NOT NULL,
    `notas` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documentos_vehiculos`
--

CREATE TABLE `documentos_vehiculos` (
    `id` int(11) NOT NULL,
    `vehiculo_id` int(11) NOT NULL,
    `tipo` varchar(50) NOT NULL,
    `nombre` varchar(100) NOT NULL,
    `archivo` varchar(255) NOT NULL,
    `fecha_emision` date NOT NULL,
    `fecha_vencimiento` date DEFAULT NULL,
    `estatus` enum(
        'vigente',
        'por_vencer',
        'vencido'
    ) DEFAULT 'vigente',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `envios`
--

CREATE TABLE `envios` (
    `id` int(11) NOT NULL,
    `usuario_id` int(11) NOT NULL,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `phone` varchar(15) NOT NULL,
    `office_phone` varchar(15) DEFAULT NULL,
    `origin` text NOT NULL,
    `destination` text NOT NULL,
    `description` text DEFAULT NULL,
    `value` decimal(10, 2) DEFAULT NULL,
    `tracking_number` varchar(20) DEFAULT NULL,
    `delivery_date` date DEFAULT NULL,
    `package_type` varchar(50) DEFAULT NULL,
    `weight` decimal(10, 2) DEFAULT NULL,
    `insurance` tinyint(1) DEFAULT 0,
    `urgent` tinyint(1) DEFAULT 0,
    `additional_notes` text DEFAULT NULL,
    `package_image` varchar(255) DEFAULT NULL,
    `estimated_cost` decimal(10, 2) DEFAULT NULL,
    `status` varchar(50) DEFAULT 'Procesando',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `lat` decimal(10, 8) DEFAULT NULL,
    `lng` decimal(11, 8) DEFAULT NULL,
    `estado_pago` varchar(20) DEFAULT 'pendiente',
    `fecha_pago` datetime DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `envios`
--

INSERT INTO
    `envios` (
        `id`,
        `usuario_id`,
        `name`,
        `email`,
        `phone`,
        `office_phone`,
        `origin`,
        `destination`,
        `description`,
        `value`,
        `tracking_number`,
        `delivery_date`,
        `package_type`,
        `weight`,
        `insurance`,
        `urgent`,
        `additional_notes`,
        `package_image`,
        `estimated_cost`,
        `status`,
        `created_at`,
        `lat`,
        `lng`,
        `estado_pago`,
        `fecha_pago`
    )
VALUES (
        39,
        1,
        'Jafeth Daniel Gamboa Baas',
        'jafethgamboabaas@gmail.com',
        '999-636-9799',
        '9996369799',
        'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México',
        'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España',
        'DROGAS',
        NULL,
        'MENDEZ-A66BC287',
        '2025-06-26',
        'carga_voluminosa',
        500.00,
        0,
        0,
        'MARIHUANA',
        '',
        0.00,
        'Procesando',
        '2025-06-07 09:57:02',
        NULL,
        NULL,
        'pendiente',
        NULL
    ),
    (
        40,
        1,
        'Jafeth Daniel Gamboa Baas',
        'jafethgamboabaas@gmail.com',
        '999-636-9799',
        '9996369799',
        'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México',
        'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España',
        'DROGAS',
        NULL,
        'MENDEZ-76115E49',
        '2025-06-26',
        'carga_voluminosa',
        500.00,
        0,
        0,
        'MARIHUANA',
        '',
        0.00,
        'Procesando',
        '2025-06-07 10:30:48',
        NULL,
        NULL,
        'pendiente',
        NULL
    ),
    (
        41,
        1,
        'Jafeth Daniel Gamboa Baas',
        'jafethgamboabaas@gmail.com',
        '999-636-9799',
        '9996369799',
        'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México',
        'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España',
        'DROGAS',
        NULL,
        'MENDEZ-2BE2E2A4',
        '2025-06-26',
        'carga_voluminosa',
        500.00,
        0,
        0,
        'MARIHUANA',
        '',
        0.00,
        'Procesando',
        '2025-06-07 10:35:47',
        NULL,
        NULL,
        'pendiente',
        NULL
    ),
    (
        42,
        1,
        'Jafeth Daniel Gamboa Baas',
        'jafethgamboabaas@gmail.com',
        '999-636-9799',
        '9996369799',
        'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México',
        'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA',
        'dnaskldnaskod',
        NULL,
        'MENDEZ-5DBF3BBF',
        '2025-06-30',
        'paquete_pequeno',
        30.00,
        1,
        1,
        'XCSC',
        '',
        0.00,
        'Procesando',
        '2025-06-12 06:15:23',
        NULL,
        NULL,
        'pendiente',
        NULL
    ),
    (
        43,
        1,
        'Jafeth Daniel Gamboa Baas',
        'jafethgamboabaas@gmail.com',
        '999-636-9799',
        '9996369799',
        'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México',
        'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA',
        'dnaskldnaskod',
        NULL,
        'MENDEZ-55601BBC',
        '2025-06-30',
        'paquete_pequeno',
        30.00,
        1,
        1,
        'XCSC',
        '',
        0.00,
        'Procesando',
        '2025-06-12 06:21:53',
        NULL,
        NULL,
        'pendiente',
        NULL
    ),
    (
        44,
        1,
        'Jafeth Daniel Gamboa Baas',
        'jafethgamboabaas@gmail.com',
        '999-636-9799',
        '9996369799',
        'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México',
        'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA',
        'dnaskldnaskod',
        NULL,
        'MENDEZ-942EA441',
        '2025-06-30',
        'paquete_pequeno',
        30.00,
        1,
        1,
        'XCSC',
        '',
        0.00,
        'Procesando',
        '2025-06-12 07:02:32',
        NULL,
        NULL,
        'pendiente',
        NULL
    ),
    (
        45,
        1,
        'Jafeth Daniel Gamboa Baas',
        'jafethgamboabaas@gmail.com',
        '999-636-9799',
        '9996369799',
        'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México',
        'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA',
        'dnaskldnaskod',
        NULL,
        'MENDEZ-700829D5',
        '2025-06-30',
        'paquete_pequeno',
        30.00,
        1,
        1,
        'XCSC',
        '',
        0.00,
        'Procesando',
        '2025-06-12 07:08:01',
        NULL,
        NULL,
        'pendiente',
        NULL
    ),
    (
        46,
        1,
        'Jafeth Daniel Gamboa Baas',
        'jafethgamboabaas@gmail.com',
        '999-636-9799',
        '9996369799',
        'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México',
        'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA',
        'dnaskldnaskod',
        NULL,
        'MENDEZ-173FB574',
        '2025-06-30',
        'paquete_pequeno',
        30.00,
        1,
        1,
        'XCSC',
        '',
        0.00,
        'Procesando',
        '2025-06-12 07:08:50',
        NULL,
        NULL,
        'pendiente',
        NULL
    ),
    (
        47,
        1,
        'Jafeth Daniel Gamboa Baas',
        'jafethgamboabaas@gmail.com',
        '999-636-9799',
        '9996369799',
        'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México',
        'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA',
        'dnaskldnaskod',
        NULL,
        'MENDEZ-57BF05D7',
        '2025-06-30',
        'paquete_pequeno',
        30.00,
        1,
        1,
        'XCSC',
        '',
        0.00,
        'Procesando',
        '2025-06-12 07:08:54',
        NULL,
        NULL,
        'pendiente',
        NULL
    ),
    (
        48,
        1,
        'Jafeth Daniel Gamboa Baas',
        'jafethgamboabaas@gmail.com',
        '999-636-9799',
        '9996369799',
        'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México',
        'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA',
        'dnaskldnaskod',
        NULL,
        'MENDEZ-E7C1BC2D',
        '2025-06-30',
        'paquete_pequeno',
        30.00,
        1,
        1,
        'XCSC',
        '',
        9835.77,
        'Procesando',
        '2025-06-12 07:18:00',
        NULL,
        NULL,
        'pendiente',
        NULL
    ),
    (
        49,
        1,
        'Jafeth Daniel Gamboa Baas',
        'jafethgamboabaas@gmail.com',
        '999-636-9799',
        '9996369799',
        'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México',
        'C57 347, juan pablo ll, 97246, Mérida, Yucatán, México',
        'MARIHUANA',
        NULL,
        'MENDEZ-BE57C331',
        '2025-06-30',
        'paquete_pequeno',
        20.00,
        0,
        1,
        'askdnasdas',
        '',
        215.67,
        'Procesando',
        '2025-06-13 04:37:10',
        NULL,
        NULL,
        'pendiente',
        NULL
    ),
    (
        50,
        1,
        'Jafeth Daniel Gamboa Baas',
        'jafethgamboabaas@gmail.com',
        '999-636-9799',
        '9996369799',
        'C55A 357, juan pablo ll, 97246, Mérida, Yucatán, México',
        'C55A 377, juan pablo ll, 97246, Mérida, Yucatán, México',
        'fasdfs',
        NULL,
        'MENDEZ-EC35EAF7',
        '2025-06-23',
        'paquete_pequeno',
        12.00,
        0,
        1,
        'nhfgh',
        '',
        173.31,
        'Procesando',
        '2025-06-13 05:34:19',
        NULL,
        NULL,
        'pendiente',
        NULL
    ),
    (
        51,
        1,
        'Jafeth Daniel Gamboa Baas',
        'jafethgamboabaas@gmail.com',
        '999-636-9799',
        '9996369799',
        'C55A 357, juan pablo ll, 97246, Mérida, Yucatán, México',
        'C55A 377, juan pablo ll, 97246, Mérida, Yucatán, México',
        'kkllkl',
        NULL,
        'MENDEZ-77D7726B',
        '2025-06-24',
        'documento',
        12.00,
        0,
        1,
        'ñlklkl',
        '',
        158.31,
        'Procesando',
        '2025-06-13 05:36:07',
        NULL,
        NULL,
        'pendiente',
        NULL
    ),
    (
        52,
        1,
        'Jafeth Daniel Gamboa Baas',
        'jafethgamboabaas@gmail.com',
        '999-636-9799',
        '9996369799',
        'C55A 357, juan pablo ll, 97246, Mérida, Yucatán, México',
        'C55A 400, juan pablo ll, 97246, Mérida, Yucatán, México',
        'dfgsdfgsdf',
        NULL,
        'MENDEZ-1590B212',
        '2025-06-29',
        'paquete_pequeno',
        12.00,
        0,
        1,
        'asdad',
        '',
        167.06,
        'Procesando',
        '2025-06-13 06:15:50',
        NULL,
        NULL,
        'pendiente',
        NULL
    ),
    (
        53,
        1,
        'Jafeth Daniel Gamboa Baas',
        'jafethgamboabaas@gmail.com',
        '999-636-9799',
        '9996369799',
        'C55A 357, juan pablo ll, 97246, Mérida, Yucatán, México',
        'C55A 400, juan pablo ll, 97246, Mérida, Yucatán, México',
        'dfgsdfgsdf',
        NULL,
        'MENDEZ-7613B746',
        '2025-06-29',
        'paquete_pequeno',
        12.00,
        0,
        1,
        'asdad',
        '',
        167.06,
        'Procesando',
        '2025-06-13 06:15:55',
        NULL,
        NULL,
        'pagado',
        '2025-06-13 07:04:58'
    ),
    (
        54,
        1,
        'Jafeth Daniel Gamboa Baas',
        'jafethgamboabaas@gmail.com',
        '999-636-9799',
        '9996369799',
        'C55A 357, juan pablo ll, 97246, Mérida, Yucatán, México',
        'Carrer Abdet 6, Callosa d&#039;en Sarrià,, 03510, Alacant, almedia, España',
        'cocaina',
        NULL,
        'MENDEZ-47264C62',
        '2025-06-30',
        'paquete_mediano',
        30.00,
        0,
        1,
        'xd',
        '',
        12350.96,
        'Procesando',
        '2025-06-13 14:13:31',
        NULL,
        NULL,
        'pagado',
        '2025-06-14 00:09:55'
    ),
    (
        55,
        1,
        'Jafeth Daniel Gamboa Baas',
        'jafethgamboabaas@gmail.com',
        '999-636-9799',
        '9996369799',
        'C55A 355, juan pablo ll, 97246, Mérida, Yucatán, México',
        'C55A 400, juan pablo ll, 97246, Mérida, Yucatán, México',
        'xd',
        NULL,
        'MENDEZ-BB356DED',
        '2025-06-24',
        'paquete_mediano',
        12.00,
        0,
        0,
        '',
        '',
        172.50,
        'Procesando',
        '2025-06-13 14:15:34',
        NULL,
        NULL,
        'pagado',
        '2025-06-13 08:20:12'
    ),
    (
        56,
        1,
        'Jafeth Daniel Gamboa Baas',
        'jafethgamboabaas@gmail.com',
        '999-636-9799',
        '9996369799',
        'C55A 357, juan pablo ll, 97246, Mérida, Yucatán, México',
        'C55A 400, juan pablo ll, 97246, Mérida, Yucatán, México',
        'COCAINA',
        NULL,
        'MENDEZ-FAEA8E96',
        '2025-06-29',
        'paquete_mediano',
        30.00,
        0,
        1,
        'XD',
        '',
        358.31,
        'Procesando',
        '2025-06-14 06:10:50',
        NULL,
        NULL,
        'pendiente',
        NULL
    ),
    (
        57,
        1,
        'Jafeth Daniel Gamboa Baas',
        'jafethgamboabaas@gmail.com',
        '999-636-9799',
        '9996369799',
        'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México',
        'Av.pallaresa 103, Santa coloma de gramanet, 08924, Mérida, Yucatán, México',
        'DRUGS',
        0.00,
        'MENDEZ-B4240C1C',
        '2025-08-25',
        'paquete_mediano',
        10.00,
        1,
        0,
        '',
        'uploads/688d0b3714f60_7f13f56e-9a1d-4e67-aff8-7f216b0333d6.png',
        13302.79,
        'Procesando',
        '2025-08-01 18:45:11',
        NULL,
        NULL,
        'pendiente',
        NULL
    ),
    (
        58,
        1,
        'Jafeth Daniel Gamboa Baas',
        'jafethgamboabaas@gmail.com',
        '999-636-9799',
        '9996369799',
        'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México',
        'Av.pallaresa 103, Santa coloma de gramanet, 08924, barcelona, cataluña, España',
        'NALAS',
        0.00,
        'MENDEZ-D40F3013',
        '2025-08-31',
        'carga_voluminosa',
        400.00,
        1,
        1,
        'NALA PITBULL',
        'uploads/688d166284361_0f60f665aae299f4bffc1993b2c3abff.png',
        24033.99,
        'En tránsito',
        '2025-08-01 19:32:50',
        NULL,
        NULL,
        'pagado',
        '2025-08-01 13:34:22'
    ),
    (
        59,
        1,
        'Jafeth Daniel Gamboa Baas',
        'jafethgamboabaas@gmail.com',
        '999-636-9799',
        '9996369799',
        'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México',
        'Av.pallaresa 103, Santa coloma de gramanet, 08924, barcelona, cataluña, España',
        'NALAS',
        0.00,
        'MENDEZ-7DBA2E9B',
        '2025-08-31',
        'carga_voluminosa',
        400.00,
        1,
        1,
        'NALA PITBULL',
        'uploads/688d1666426de_0f60f665aae299f4bffc1993b2c3abff.png',
        24033.99,
        'Entregado',
        '2025-08-01 19:32:54',
        NULL,
        NULL,
        'pendiente',
        NULL
    );

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas`
--

CREATE TABLE `facturas` (
    `id` int(11) NOT NULL,
    `envio_id` int(11) NOT NULL,
    `numero_factura` varchar(25) NOT NULL,
    `fecha_emision` datetime NOT NULL DEFAULT current_timestamp(),
    `fecha_vencimiento` datetime DEFAULT NULL,
    `monto` decimal(10, 2) NOT NULL,
    `status` enum(
        'pendiente',
        'pagado',
        'cancelado',
        'vencido'
    ) DEFAULT 'pendiente',
    `metodo_pago` varchar(50) DEFAULT NULL,
    `referencia_pago` varchar(100) DEFAULT NULL,
    `fecha_pago` datetime DEFAULT NULL,
    `notas` text DEFAULT NULL,
    `cfdi_xml` varchar(255) DEFAULT NULL,
    `cfdi_pdf` varchar(255) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mantenimientos`
--

CREATE TABLE `mantenimientos` (
    `id` int(11) NOT NULL,
    `vehiculo_id` int(11) NOT NULL,
    `tipo` enum(
        'preventivo',
        'correctivo',
        'revision'
    ) NOT NULL,
    `descripcion` text NOT NULL,
    `fecha_programada` date NOT NULL,
    `fecha_realizado` date DEFAULT NULL,
    `costo` decimal(10, 2) DEFAULT NULL,
    `proveedor` varchar(100) DEFAULT NULL,
    `estatus` enum(
        'pendiente',
        'en_progreso',
        'completado',
        'cancelado'
    ) DEFAULT 'pendiente',
    `notas` text DEFAULT NULL,
    `created_by` int(11) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_contables`
--

CREATE TABLE `movimientos_contables` (
    `id` int(11) NOT NULL,
    `tipo` enum('ingreso', 'egreso') NOT NULL,
    `factura_id` int(11) DEFAULT NULL,
    `concepto` varchar(255) NOT NULL,
    `monto` decimal(10, 2) NOT NULL,
    `fecha_movimiento` date NOT NULL,
    `categoria` varchar(100) NOT NULL,
    `created_by` int(11) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `movimientos_contables`
--

INSERT INTO
    `movimientos_contables` (
        `id`,
        `tipo`,
        `factura_id`,
        `concepto`,
        `monto`,
        `fecha_movimiento`,
        `categoria`,
        `created_by`,
        `created_at`
    )
VALUES (
        1,
        'ingreso',
        NULL,
        'Pago en línea por envío #MENDEZ-BB356DED',
        172.50,
        '2025-06-13',
        'pagos_online',
        1,
        '2025-06-13 14:15:50'
    );

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
    `id` int(11) NOT NULL,
    `usuario_id` int(11) NOT NULL,
    `tipo` varchar(50) NOT NULL,
    `titulo` varchar(100) NOT NULL,
    `mensaje` text NOT NULL,
    `enlace` varchar(255) DEFAULT NULL,
    `leida` tinyint(1) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `push_subscriptions`
--

CREATE TABLE `push_subscriptions` (
    `id` int(11) NOT NULL,
    `usuario_id` int(11) NOT NULL,
    `endpoint` varchar(500) NOT NULL,
    `p256dh` varchar(255) NOT NULL,
    `auth` varchar(100) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `repartidores`
--

CREATE TABLE `repartidores` (
    `id` int(11) NOT NULL,
    `usuario_id` int(11) NOT NULL,
    `telefono` varchar(20) DEFAULT NULL,
    `vehiculo` varchar(50) DEFAULT NULL,
    `placa` varchar(20) DEFAULT NULL,
    `status` enum(
        'activo',
        'pendiente',
        'suspendido'
    ) DEFAULT 'pendiente',
    `ultimo_login` datetime DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `edad` int(11) DEFAULT NULL,
    `tipo_licencia` varchar(20) DEFAULT NULL,
    `num_licencia` varchar(30) DEFAULT NULL,
    `exp_vigencia` date DEFAULT NULL,
    `anos_experiencia` int(11) DEFAULT NULL,
    `capacidad_carga` decimal(5, 2) DEFAULT NULL,
    `certificacion_medica` tinyint(1) DEFAULT 0,
    `conocimiento_rutas` tinyint(1) DEFAULT 0,
    `certificacion_carga` tinyint(1) DEFAULT 0,
    `antecedentes_penales` tinyint(1) DEFAULT 0,
    `profile_photo` mediumblob DEFAULT NULL,
    `id_photo` mediumblob DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `repartidores`
--

INSERT INTO
    `repartidores` (
        `id`,
        `usuario_id`,
        `telefono`,
        `vehiculo`,
        `placa`,
        `status`,
        `ultimo_login`,
        `created_at`,
        `updated_at`,
        `edad`,
        `tipo_licencia`,
        `num_licencia`,
        `exp_vigencia`,
        `anos_experiencia`,
        `capacidad_carga`,
        `certificacion_medica`,
        `conocimiento_rutas`,
        `certificacion_carga`,
        `antecedentes_penales`,
        `profile_photo`,
        `id_photo`
    )
VALUES (
        1,
        23,
        '999999999',
        '0',
        '12',
        'activo',
        '2025-08-06 15:56:36',
        '2025-04-17 23:50:06',
        '2025-08-06 21:56:36',
        21,
        'E',
        '12',
        '2025-04-19',
        12,
        12.00,
        1,
        1,
        1,
        1,
        '',
        ''
    );

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `repartidores_envios`
--

CREATE TABLE `repartidores_envios` (
    `usuario_id` int(11) NOT NULL,
    `envio_id` int(11) NOT NULL,
    `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `repartidores_envios`
--

INSERT INTO
    `repartidores_envios` (
        `usuario_id`,
        `envio_id`,
        `fecha_asignacion`
    )
VALUES (23, 15, '2025-04-27 08:47:20'),
    (23, 58, '2025-08-06 16:22:54'),
    (23, 59, '2025-08-06 16:22:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
    `id` int(11) NOT NULL,
    `nombre` varchar(50) NOT NULL,
    `descripcion` varchar(255) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO
    `roles` (
        `id`,
        `nombre`,
        `descripcion`,
        `created_at`
    )
VALUES (
        1,
        'Administrador',
        'Administrador del sistema',
        '2025-04-17 22:01:50'
    ),
    (
        2,
        'cliente',
        'Usuario que realiza envíos',
        '2025-04-17 22:01:50'
    ),
    (
        3,
        'repartidor',
        'Usuario que entrega paquetes',
        '2025-04-17 22:01:50'
    ),
    (
        4,
        'cliente_repartidor',
        'Usuario con roles de cliente y repartidor',
        '2025-04-18 07:57:39'
    );

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tracking_history`
--

CREATE TABLE `tracking_history` (
    `id` int(11) NOT NULL,
    `envio_id` int(11) NOT NULL,
    `status` varchar(50) NOT NULL,
    `location` varchar(255) DEFAULT NULL,
    `notes` text DEFAULT NULL,
    `created_by` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
    `id` int(11) NOT NULL,
    `google_id` varchar(255) DEFAULT NULL,
    `nombre_usuario` varchar(50) NOT NULL,
    `email` varchar(100) NOT NULL,
    `password` varchar(255) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `rol_id` int(11) DEFAULT 2,
    `status` enum(
        'activo',
        'pendiente',
        'suspendido',
        'eliminado'
    ) DEFAULT 'activo'
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO
    `usuarios` (
        `id`,
        `google_id`,
        `nombre_usuario`,
        `email`,
        `password`,
        `created_at`,
        `rol_id`,
        `status`
    )
VALUES (
        1,
        '102805881195773678735',
        'Darksoul 2703',
        'jafethgamboabaas@gmail.com',
        NULL,
        '2025-04-08 02:39:36',
        2,
        'activo'
    ),
    (
        9,
        '115034569881549488883',
        'Gamboa Baas Jafeth',
        'jafethgamboa27@gmail.com',
        NULL,
        '2025-04-09 22:21:01',
        2,
        'activo'
    ),
    (
        19,
        '108144328180217974530',
        'pruebaenvios',
        'pruebaenvios9@gmail.com',
        NULL,
        '2025-04-10 18:51:49',
        2,
        'activo'
    ),
    (
        20,
        NULL,
        'awaderuss',
        'soidjaosjd@gmail.com',
        '$2y$10$zcbKqjqjjELeZ/1te1hJWeqD..NFaPToYOh1KGbVpT.Mp3zyGjPFu',
        '2025-04-16 05:04:49',
        2,
        'activo'
    ),
    (
        21,
        NULL,
        'awaderuss',
        '123@gmail.com',
        '$2y$10$J8uBzGmRAZCsQRqkF6OsveuPY9AXon9mq7IS10yOasJxD3XTrHc6i',
        '2025-04-17 20:26:14',
        2,
        'activo'
    ),
    (
        22,
        NULL,
        'Perico',
        'ricardogamboabaas@gmail.com',
        '$2y$10$nDKjztpyiSSCNG76ZNhHfu.5xAy2syeb31XsvJR1iFvKTnNehfD66',
        '2025-04-17 20:47:43',
        2,
        'activo'
    ),
    (
        23,
        NULL,
        '1234',
        '1234@gmail.com',
        '$2y$10$QYYIKZBRDiilnaukXVb9HecdWzUiMN02yFWG9iQiaiAj5MoGvoJ42',
        '2025-04-17 23:50:06',
        3,
        'activo'
    ),
    (
        24,
        NULL,
        'Josué Gamboa',
        'gamboajosue541@gmail.com',
        '$2y$10$aTkwr4C.BKbXLhD7a3MQYuC13GIcmZe2pXZ7B0ptT7pxQC/KqU2GW',
        '2025-04-18 02:41:57',
        2,
        'activo'
    ),
    (
        26,
        NULL,
        'awaderuss',
        'LE21080769@merida.tecnm.mx',
        '$2y$10$jk48HsOZllb51Zgg33UgyuAjmJLccb7jtA/gw0NHrEzUaSeOvV5dm',
        '2025-04-18 08:44:09',
        2,
        'activo'
    ),
    (
        27,
        NULL,
        'thales',
        'thales995aaa@gmail.com',
        '$2y$10$CkiVR7JzYqXikKm.MSomqOkEVE6hb.v51cWSoqoeqWO1H4tramHq.',
        '2025-04-18 08:49:42',
        2,
        'activo'
    ),
    (
        30,
        NULL,
        'DARK',
        '12345@gmail.com',
        '$2y$10$0J/hEalb3Us9RCCDsoLAB.WHObse6Zjr6n8g9eyLp4gz2p8iWPAxi',
        '2025-04-19 09:11:43',
        1,
        'activo'
    );

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vehiculos`
--

CREATE TABLE `vehiculos` (
    `id` int(11) NOT NULL,
    `tipo` varchar(100) NOT NULL,
    `marca` varchar(100) NOT NULL,
    `modelo` varchar(100) NOT NULL,
    `ano` year(4) NOT NULL,
    `placa` varchar(20) NOT NULL,
    `num_serie` varchar(50) NOT NULL,
    `capacidad_carga` decimal(10, 2) NOT NULL,
    `rendimiento_combustible` decimal(5, 2) DEFAULT NULL,
    `status` enum(
        'activo',
        'mantenimiento',
        'inactivo'
    ) DEFAULT 'activo',
    `kilometraje` int(11) DEFAULT 0,
    `fecha_adquisicion` date NOT NULL,
    `imagen` varchar(255) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `zonas_precios`
--

CREATE TABLE `zonas_precios` (
    `id` int(11) NOT NULL,
    `origen_codigo` varchar(10) NOT NULL,
    `destino_codigo` varchar(10) NOT NULL,
    `precio_base` decimal(10, 2) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `contratos`
--
ALTER TABLE `contratos`
ADD PRIMARY KEY (`id`),
ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
ADD PRIMARY KEY (`id`),
ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `documentos_vehiculos`
--
ALTER TABLE `documentos_vehiculos`
ADD PRIMARY KEY (`id`),
ADD KEY `vehiculo_id` (`vehiculo_id`);

--
-- Indices de la tabla `envios`
--
ALTER TABLE `envios`
ADD PRIMARY KEY (`id`),
ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `facturas`
--
ALTER TABLE `facturas`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `numero_factura` (`numero_factura`),
ADD KEY `fk_factura_envio` (`envio_id`);

--
-- Indices de la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
ADD PRIMARY KEY (`id`),
ADD KEY `vehiculo_id` (`vehiculo_id`),
ADD KEY `created_by` (`created_by`);

--
-- Indices de la tabla `movimientos_contables`
--
ALTER TABLE `movimientos_contables`
ADD PRIMARY KEY (`id`),
ADD KEY `factura_id` (`factura_id`),
ADD KEY `created_by` (`created_by`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
ADD PRIMARY KEY (`id`),
ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
ADD PRIMARY KEY (`id`),
ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `repartidores`
--
ALTER TABLE `repartidores`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `repartidores_envios`
--
ALTER TABLE `repartidores_envios`
ADD PRIMARY KEY (`usuario_id`, `envio_id`),
ADD KEY `re_envio_fk` (`envio_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles` ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tracking_history`
--
ALTER TABLE `tracking_history`
ADD PRIMARY KEY (`id`),
ADD KEY `envio_id` (`envio_id`),
ADD KEY `created_by` (`created_by`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `email` (`email`),
ADD UNIQUE KEY `google_id` (`google_id`);

--
-- Indices de la tabla `vehiculos`
--
ALTER TABLE `vehiculos`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `placa` (`placa`);

--
-- Indices de la tabla `zonas_precios`
--
ALTER TABLE `zonas_precios` ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `contratos`
--
ALTER TABLE `contratos` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `documentos_vehiculos`
--
ALTER TABLE `documentos_vehiculos`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `envios`
--
ALTER TABLE `envios`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 60;

--
-- AUTO_INCREMENT de la tabla `facturas`
--
ALTER TABLE `facturas` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `movimientos_contables`
--
ALTER TABLE `movimientos_contables`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 2;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `repartidores`
--
ALTER TABLE `repartidores`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 2;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 10;

--
-- AUTO_INCREMENT de la tabla `tracking_history`
--
ALTER TABLE `tracking_history`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 31;

--
-- AUTO_INCREMENT de la tabla `vehiculos`
--
ALTER TABLE `vehiculos` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `zonas_precios`
--
ALTER TABLE `zonas_precios`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `contratos`
--
ALTER TABLE `contratos`
ADD CONSTRAINT `contratos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
ADD CONSTRAINT `cotizaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `documentos_vehiculos`
--
ALTER TABLE `documentos_vehiculos`
ADD CONSTRAINT `documentos_vehiculos_ibfk_1` FOREIGN KEY (`vehiculo_id`) REFERENCES `vehiculos` (`id`);

--
-- Filtros para la tabla `facturas`
--
ALTER TABLE `facturas`
ADD CONSTRAINT `fk_factura_envio` FOREIGN KEY (`envio_id`) REFERENCES `envios` (`id`);

--
-- Filtros para la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
ADD CONSTRAINT `mantenimientos_ibfk_1` FOREIGN KEY (`vehiculo_id`) REFERENCES `vehiculos` (`id`),
ADD CONSTRAINT `mantenimientos_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `movimientos_contables`
--
ALTER TABLE `movimientos_contables`
ADD CONSTRAINT `movimientos_contables_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`),
ADD CONSTRAINT `movimientos_contables_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
ADD CONSTRAINT `push_subscriptions_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `repartidores`
--
ALTER TABLE `repartidores`
ADD CONSTRAINT `fk_repartidor_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `repartidores_envios`
--
ALTER TABLE `repartidores_envios`
ADD CONSTRAINT `re_envio_fk` FOREIGN KEY (`envio_id`) REFERENCES `envios` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `re_usuario_fk` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tracking_history`
--
ALTER TABLE `tracking_history`
ADD CONSTRAINT `tracking_history_ibfk_1` FOREIGN KEY (`envio_id`) REFERENCES `envios` (`id`),
ADD CONSTRAINT `tracking_history_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */
;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */
;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */
;