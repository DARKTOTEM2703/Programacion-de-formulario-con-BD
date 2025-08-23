-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-08-2025 a las 06:39:21
-- Versión del servidor: 10.4.28-MariaDB
-- Versión de PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `gestion_envios`
--

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
  `value` decimal(10,2) DEFAULT NULL,
  `tracking_number` varchar(20) DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `package_type` varchar(50) DEFAULT NULL,
  `weight` decimal(10,2) DEFAULT NULL,
  `insurance` tinyint(1) DEFAULT 0,
  `urgent` tinyint(1) DEFAULT 0,
  `additional_notes` text DEFAULT NULL,
  `package_image` varchar(255) DEFAULT NULL,
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Procesando',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `estado_pago` varchar(20) DEFAULT 'pendiente',
  `fecha_pago` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `pin_seguro` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `envios`
--

INSERT INTO `envios` (`id`, `usuario_id`, `name`, `email`, `phone`, `office_phone`, `origin`, `destination`, `description`, `value`, `tracking_number`, `delivery_date`, `package_type`, `weight`, `insurance`, `urgent`, `additional_notes`, `package_image`, `estimated_cost`, `status`, `created_at`, `lat`, `lng`, `estado_pago`, `fecha_pago`, `updated_at`, `pin_seguro`) VALUES
(15, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A #357 Juan Pablo ll 97236', 'c 55A #357 x 18 y 20 juan pablo ll 97246', 'CAJA DE HERRAMIENTAS MUY PESADA', NULL, 'MENDEZ-CB864B29', '2025-04-17', 'paquete_mediano', 12.00, 1, 1, 'XD', '', 485.00, 'En tránsito', '2025-04-16 05:48:02', 20.96737000, -89.59258600, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(16, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'hjvjvhj', NULL, 'MENDEZ-FA2681B2', '2025-04-23', 'paquete_pequeno', 678.00, 1, 1, 'ghjghj', '', 11013.90, 'En tránsito', '2025-04-16 05:52:28', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(17, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'hjvjvhj', NULL, 'MENDEZ-10402858', '2025-04-23', 'paquete_pequeno', 678.00, 1, 1, 'ghjghj', '', 11013.90, 'En tránsito', '2025-04-16 05:52:32', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(18, 24, 'Josué Gamboa', 'gamboajosue541@gmail.com', '999-645-4541', '', 'Una tienda abarrotes García', 'Xoclán', 'Folletos', NULL, 'MENDEZ-4653DDCF', '2025-04-30', 'paquete_mediano', 3.00, 0, 0, 'Frágil', '', 130.00, 'Procesando', '2025-04-18 02:46:58', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(19, 23, 'thales', 'thales995aaa@gmail.com', '111-111-1111', '', 'xdfxfxfxdf', 'cdddd', 'xd', NULL, 'MENDEZ-80BC7E39', '2025-04-19', 'documento', 1.00, 1, 1, 'xddddd', '', 310.00, 'En tránsito', '2025-04-18 08:52:55', NULL, NULL, 'pendiente', NULL, '2025-08-23 03:59:17', 644959),
(20, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'fasdfgasdfg', NULL, 'MENDEZ-96F3BBB5', '2025-04-30', 'paquete_mediano', 12.00, 1, 1, 'fslvnsdfopjmgsod', '', 12036.15, 'En tránsito', '2025-04-24 02:51:14', NULL, NULL, 'pendiente', NULL, '2025-08-23 03:59:28', 758346),
(21, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'fasdfgasdfg', NULL, 'MENDEZ-77A3C588', '2025-04-30', 'paquete_mediano', 12.00, 1, 1, 'fslvnsdfopjmgsod', '', 12036.15, 'Procesando', '2025-04-24 02:51:19', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(22, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'fvasdfv', NULL, 'MENDEZ-DA90CCDE', '2025-04-30', 'paquete_mediano', 12.00, 1, 1, '13123', '', 426.15, 'Procesando', '2025-04-29 05:22:31', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(23, 1, 'Jafeth Daniel Gamboa Baas', 'LE21080769@merida.tecnm.mx', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'vzsdfg', NULL, 'MENDEZ-3F0002CC', '2025-04-30', 'documento', 12.00, 1, 1, 'kjanfjoñasnfvjon', '', 1035.65, 'Procesando', '2025-04-29 05:24:29', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(24, 1, 'Jafeth Daniel Gamboa Baas', 'Jafethgamboa27@gmail.com', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'ñnvoñadfnmv', NULL, 'MENDEZ-20C78B68', '2025-04-30', 'documento', 0.20, 0, 0, 'fvldmf', '', 102.00, 'Procesando', '2025-04-29 05:26:15', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(25, 1, 'Jafeth Daniel Gamboa Baas', 'Jafethgamboa27@gmail.com', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'ñnvoñadfnmv', NULL, 'MENDEZ-568B4C7B', '2025-04-30', 'documento', 0.20, 0, 0, 'fvldmf', '', 102.00, 'Procesando', '2025-04-29 05:26:19', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(27, 21, 'avix garcia lopez', '123@gmail.com', '224-567-3399', '223455677', 'calle lopez, 14, col. progreso cp. 91000', 'calle mango,23 col. flores cp.91000', ':3', NULL, 'MENDEZ-FFAD699D', '2025-05-24', 'paquete_mediano', 8.00, 1, 1, 'uwu', '', 480.00, 'Procesando', '2025-04-30 03:28:00', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(28, 21, 'avix garcia lopez', '123@gmail.com', '224-567-3399', '223455677', 'calle lopez, 14, col. progreso cp. 91000', 'calle mango,23 col. flores cp.91000', ':3', NULL, 'MENDEZ-1A202458', '2025-05-24', 'paquete_mediano', 8.00, 1, 1, 'uwu', '', 480.00, 'Procesando', '2025-04-30 03:28:03', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(29, 21, 'avix garcia lopez', '123@gmail.com', '224-567-3399', '223455677', 'calle lopez, 14, col. progreso cp. 91000', 'calle mango,23 col. flores cp.91000', ':3', NULL, 'MENDEZ-D7236B32', '2025-05-24', 'paquete_mediano', 8.00, 1, 1, 'uwu', '', 480.00, 'Procesando', '2025-04-30 03:28:05', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(30, 21, 'avix garcia lopez', '123@gmail.com', '224-567-3399', '223455677', 'calle lopez, 14, col. progreso cp. 91000', 'calle mango,23 col. flores cp.91000', ':3', NULL, 'MENDEZ-0AED579E', '2025-05-24', 'paquete_mediano', 8.00, 1, 1, 'uwu', '', 480.00, 'Procesando', '2025-04-30 03:28:08', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(31, 21, 'avix garcia lopez', '123@gmail.com', '224-567-3399', '223455677', 'calle lopez, 14, col. progreso cp. 91000', 'calle mango,23 col. flores cp.91000', ':3', NULL, 'MENDEZ-C7CD52DC', '2025-05-24', 'paquete_mediano', 8.00, 1, 1, 'uwu', '', 480.00, 'Procesando', '2025-04-30 03:28:12', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(32, 21, 'avix garcia lopez', '123@gmail.com', '224-567-3399', '223455677', 'calle lopez, 14, col. progreso cp. 91000', 'calle mango,23 col. flores cp.91000', ':3', NULL, 'MENDEZ-0F287266', '2025-05-24', 'paquete_mediano', 8.00, 1, 1, 'uwu', '', 480.00, 'Procesando', '2025-04-30 03:28:16', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(33, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C. 55ᴬ 357, Juan Pablo II, 97246', 'C. 31 235, Juan Pablo II, 97246', 'HOLA', NULL, 'MENDEZ-7B25AB19', '2025-05-14', 'documento', 23.00, 0, 0, '', '', 330.00, 'Procesando', '2025-05-10 02:36:27', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(34, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España', 'DROGAS', NULL, 'MENDEZ-5CFD04CC', '2025-06-26', 'carga_voluminosa', 500.00, 0, 0, 'MARIHUANA', '', 0.00, 'Procesando', '2025-06-07 09:24:50', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(35, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España', 'DROGAS', NULL, 'MENDEZ-954B5638', '2025-06-26', 'carga_voluminosa', 500.00, 0, 0, 'MARIHUANA', '', 0.00, 'Procesando', '2025-06-07 09:27:32', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(36, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España', 'DROGAS', NULL, 'MENDEZ-82C051A7', '2025-06-26', 'carga_voluminosa', 500.00, 0, 0, 'MARIHUANA', '', 0.00, 'Procesando', '2025-06-07 09:28:14', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(37, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España', 'DROGAS', NULL, 'MENDEZ-C8CD30F7', '2025-06-26', 'carga_voluminosa', 500.00, 0, 0, 'MARIHUANA', '', 0.00, 'Procesando', '2025-06-07 09:35:44', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(38, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España', 'DROGAS', NULL, 'MENDEZ-1B101B5B', '2025-06-26', 'carga_voluminosa', 500.00, 0, 0, 'MARIHUANA', '', 0.00, 'Procesando', '2025-06-07 09:35:55', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(40, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España', 'DROGAS', NULL, 'MENDEZ-76115E49', '2025-06-26', 'carga_voluminosa', 500.00, 0, 0, 'MARIHUANA', '', 0.00, 'Procesando', '2025-06-07 10:30:48', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(41, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España', 'DROGAS', NULL, 'MENDEZ-2BE2E2A4', '2025-06-26', 'carga_voluminosa', 500.00, 0, 0, 'MARIHUANA', '', 0.00, 'Procesando', '2025-06-07 10:35:47', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(42, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA', 'dnaskldnaskod', NULL, 'MENDEZ-5DBF3BBF', '2025-06-30', 'paquete_pequeno', 30.00, 1, 1, 'XCSC', '', 0.00, 'Procesando', '2025-06-12 06:15:23', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(43, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA', 'dnaskldnaskod', NULL, 'MENDEZ-55601BBC', '2025-06-30', 'paquete_pequeno', 30.00, 1, 1, 'XCSC', '', 0.00, 'Procesando', '2025-06-12 06:21:53', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(44, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA', 'dnaskldnaskod', NULL, 'MENDEZ-942EA441', '2025-06-30', 'paquete_pequeno', 30.00, 1, 1, 'XCSC', '', 0.00, 'Procesando', '2025-06-12 07:02:32', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(45, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA', 'dnaskldnaskod', NULL, 'MENDEZ-700829D5', '2025-06-30', 'paquete_pequeno', 30.00, 1, 1, 'XCSC', '', 0.00, 'Procesando', '2025-06-12 07:08:01', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(46, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA', 'dnaskldnaskod', NULL, 'MENDEZ-173FB574', '2025-06-30', 'paquete_pequeno', 30.00, 1, 1, 'XCSC', '', 0.00, 'Recibido bodega', '2025-06-12 07:08:50', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(47, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA', 'dnaskldnaskod', NULL, 'MENDEZ-57BF05D7', '2025-06-30', 'paquete_pequeno', 30.00, 1, 1, 'XCSC', '', 0.00, 'Recibido bodega', '2025-06-12 07:08:54', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(48, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA', 'dnaskldnaskod', NULL, 'MENDEZ-E7C1BC2D', '2025-06-30', 'paquete_pequeno', 30.00, 1, 1, 'XCSC', '', 9835.77, 'En tránsito', '2025-06-12 07:18:00', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(49, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'C57 347, juan pablo ll, 97246, Mérida, Yucatán, México', 'MARIHUANA', NULL, 'MENDEZ-BE57C331', '2025-06-30', 'paquete_pequeno', 20.00, 0, 1, 'askdnasdas', '', 215.67, 'En tránsito', '2025-06-13 04:37:10', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(50, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 357, juan pablo ll, 97246, Mérida, Yucatán, México', 'C55A 377, juan pablo ll, 97246, Mérida, Yucatán, México', 'fasdfs', NULL, 'MENDEZ-EC35EAF7', '2025-06-23', 'paquete_pequeno', 12.00, 0, 1, 'nhfgh', '', 173.31, 'En tránsito', '2025-06-13 05:34:19', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(51, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 357, juan pablo ll, 97246, Mérida, Yucatán, México', 'C55A 377, juan pablo ll, 97246, Mérida, Yucatán, México', 'kkllkl', NULL, 'MENDEZ-77D7726B', '2025-06-24', 'documento', 12.00, 0, 1, 'ñlklkl', '', 158.31, 'En tránsito', '2025-06-13 05:36:07', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(52, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 357, juan pablo ll, 97246, Mérida, Yucatán, México', 'C55A 400, juan pablo ll, 97246, Mérida, Yucatán, México', 'dfgsdfgsdf', NULL, 'MENDEZ-1590B212', '2025-06-29', 'paquete_pequeno', 12.00, 0, 1, 'asdad', '', 167.06, 'En tránsito', '2025-06-13 06:15:50', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(53, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 357, juan pablo ll, 97246, Mérida, Yucatán, México', 'C55A 400, juan pablo ll, 97246, Mérida, Yucatán, México', 'dfgsdfgsdf', NULL, 'MENDEZ-7613B746', '2025-06-29', 'paquete_pequeno', 12.00, 0, 1, 'asdad', '', 167.06, 'En tránsito', '2025-06-13 06:15:55', NULL, NULL, 'pagado', '2025-06-13 07:04:58', '2025-08-23 04:36:33', NULL),
(54, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 357, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià,, 03510, Alacant, almedia, España', 'cocaina', NULL, 'MENDEZ-47264C62', '2025-06-30', 'paquete_mediano', 30.00, 0, 1, 'xd', '', 12350.96, 'En tránsito', '2025-06-13 14:13:31', NULL, NULL, 'pagado', '2025-06-14 00:09:55', '2025-08-23 04:36:33', NULL),
(55, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 355, juan pablo ll, 97246, Mérida, Yucatán, México', 'C55A 400, juan pablo ll, 97246, Mérida, Yucatán, México', 'xd', NULL, 'MENDEZ-BB356DED', '2025-06-24', 'paquete_mediano', 12.00, 0, 0, '', '', 172.50, 'En tránsito', '2025-06-13 14:15:34', NULL, NULL, 'pagado', '2025-06-13 08:20:12', '2025-08-23 04:36:33', NULL),
(56, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 357, juan pablo ll, 97246, Mérida, Yucatán, México', 'C55A 400, juan pablo ll, 97246, Mérida, Yucatán, México', 'COCAINA', NULL, 'MENDEZ-FAEA8E96', '2025-06-29', 'paquete_mediano', 30.00, 0, 1, 'XD', '', 358.31, 'En tránsito', '2025-06-14 06:10:50', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(57, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 103, Santa coloma de gramanet, 08924, Mérida, Yucatán, México', 'DRUGS', 0.00, 'MENDEZ-B4240C1C', '2025-08-25', 'paquete_mediano', 10.00, 1, 0, '', 'uploads/688d0b3714f60_7f13f56e-9a1d-4e67-aff8-7f216b0333d6.png', 13302.79, 'En tránsito', '2025-08-01 18:45:11', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(58, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 103, Santa coloma de gramanet, 08924, barcelona, cataluña, España', 'NALAS', 0.00, 'MENDEZ-D40F3013', '2025-08-31', 'carga_voluminosa', 400.00, 1, 1, 'NALA PITBULL', 'uploads/688d166284361_0f60f665aae299f4bffc1993b2c3abff.png', 24033.99, 'En tránsito', '2025-08-01 19:32:50', NULL, NULL, 'pagado', '2025-08-01 13:34:22', '2025-08-23 04:36:33', NULL),
(59, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 103, Santa coloma de gramanet, 08924, barcelona, cataluña, España', 'NALAS', 0.00, 'MENDEZ-7DBA2E9B', '2025-08-31', 'carga_voluminosa', 400.00, 1, 1, 'NALA PITBULL', 'uploads/688d1666426de_0f60f665aae299f4bffc1993b2c3abff.png', 24033.99, 'Entregado', '2025-08-01 19:32:54', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(60, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 103, Santa coloma de gramanet, 08924, barcelona, cataluña, España', 'rfsdcvsdf', 3222.00, 'MENDEZ-B6577D96', '2025-08-27', 'paquete_mediano', 1212.00, 1, 1, 'dasdad', '', 28097.86, 'En tránsito', '2025-08-10 07:00:16', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(61, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 103, Santa coloma de gramanet, 08924, barcelona, cataluña, España', 'NALA COMIDA', 100.00, 'MENDEZ-432480CE', '2025-08-20', 'paquete_pequeno', 10.00, 1, 1, 'COMIDA FRAGIL', 'uploads/packages/pkg_20250810_090554_689844d27692d.jpg', 16615.24, 'En tránsito', '2025-08-10 07:05:54', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(62, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 101, Santa coloma de gramanet, 08924, barcelona, cataluña, España', 'FRAGIL VASOS CRISTAL', 3000.00, 'MENDEZ-EADA7855', '2025-08-20', 'paquete_mediano', 100.00, 1, 1, 'HOLA', 'uploads/packages/pkg_20250810_234708_6899135c61112.png', 17658.99, 'En tránsito', '2025-08-10 21:47:08', NULL, NULL, 'pagado', '2025-08-10 15:47:52', '2025-08-23 04:36:33', NULL),
(63, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 103, Santa coloma de gramanet, 08924, barcelona, cataluña, España', 'COMIDA DE XELA', 4000.00, 'MENDEZ-C1672171', '2025-08-30', 'paquete_mediano', 10.00, 1, 1, 'KEBAB', 'uploads/packages/pkg_20250820_120431_68a59dafdd172.png', 16877.74, 'En tránsito', '2025-08-20 10:04:31', NULL, NULL, 'pagado', '2025-08-20 04:05:55', '2025-08-23 04:36:33', NULL),
(64, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 103, Santa coloma de gramanet, 08924, barcelona, cataluña, España', 'DROGAS DE FREEZ', 232323.00, 'MENDEZ-827A0BDC', '2025-08-26', 'paquete_mediano', 12.00, 0, 0, 'MARIHUANA FREEZ', 'uploads/packages/pkg_20250821_015535_68a66077d9f08.jpg', 13317.19, 'Procesando', '2025-08-20 23:55:35', NULL, NULL, 'pagado', '2025-08-20 17:57:45', '2025-08-23 04:36:33', NULL),
(65, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 101, Santa coloma de gramanet, 08924, barcelona, cataluña, España', 'XDDD', 150.00, 'MENDEZ-9A038AFB', '2025-08-25', 'paquete_pequeno', 10.00, 1, 1, 'HOLA', 'uploads/packages/pkg_20250821_023644_68a66a1c49b32.jpg', 16618.37, 'Procesando', '2025-08-21 00:36:44', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(66, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 103, Santa coloma de gramanet, 08924, barcelona, cataluña, España', '123', 123.00, 'MENDEZ-CF18C337', '2025-08-29', 'paquete_mediano', 23.00, 1, 1, '123', 'uploads/packages/pkg_20250821_025354_68a66e229fea1.jpg', 16757.31, 'Recibido bodega', '2025-08-21 00:53:54', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(67, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 103, Santa coloma de gramanet, 08924, barcelona, cataluña, España', 'dasd', 12.00, 'MENDEZ-A2C0E30E', '2025-08-31', 'paquete_pequeno', 12.00, 0, 0, 'asd', 'uploads/packages/pkg_20250821_025811_68a66f2310cbc.jpg', 13299.19, 'En tránsito', '2025-08-21 00:58:11', NULL, NULL, 'pagado', '2025-08-20 19:00:01', '2025-08-23 04:36:33', NULL),
(68, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', NULL, 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 103, Santa coloma de gramanet, 08924, barcelona, cataluña, España', 'COMIDA MESSI', 100.00, '0', '2025-08-30', 'paquete_pequeno', 10.00, 1, 1, 'COMIDA CARA DE MESSI', 'uploads/packages/pkg_20250823_061925_68a9414dd5610.jpg', 16615.24, 'Procesando', '2025-08-23 04:19:25', NULL, NULL, 'pendiente', NULL, NULL, 216505);

--
-- Disparadores `envios`
--
DELIMITER $$
CREATE TRIGGER `tr_envios_update` BEFORE UPDATE ON `envios` FOR EACH ROW BEGIN
  SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `envios`
--
ALTER TABLE `envios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uidx_envios_tracking` (`tracking_number`),
  ADD KEY `idx_envios_status_created` (`status`,`created_at`),
  ADD KEY `idx_envios_usuario_status` (`usuario_id`,`status`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `envios`
--
ALTER TABLE `envios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
