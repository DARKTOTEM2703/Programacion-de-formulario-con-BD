-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-08-2025 a las 10:16:12
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

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

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_asignar_repartidor` (IN `p_envio_id` INT, IN `p_repartidor_usuario_id` INT, IN `p_actor` INT, OUT `p_ok` TINYINT, OUT `p_msg` VARCHAR(120))   proc: BEGIN
  -- Las declaraciones DECLARE deben ir PRIMERO
  DECLARE v_status VARCHAR(40);
  
  -- Luego las demás instrucciones
  SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
  SET p_ok=0; SET p_msg=NULL;
  
  SELECT status INTO v_status
    FROM envios WHERE id=p_envio_id FOR UPDATE;
  IF v_status IS NULL THEN
     SET p_msg='No existe envío'; LEAVE proc;
  END IF;
  IF v_status NOT IN ('Procesando','En tránsito') THEN
     SET p_msg='Estado no asignable'; LEAVE proc;
  END IF;
  INSERT INTO repartidores_envios(usuario_id,envio_id,fecha_asignacion)
    VALUES(p_repartidor_usuario_id,p_envio_id,NOW())
    ON DUPLICATE KEY UPDATE fecha_asignacion=NOW();
  IF v_status='Procesando' THEN
     SET @estado_transito = 'En tránsito';
     
     UPDATE envios SET status=@estado_transito
      WHERE id=p_envio_id;
      
     INSERT INTO tracking_history(envio_id,status,notes,created_by)
       VALUES(p_envio_id,@estado_transito,'Asignación repartidor',p_actor);
  END IF;
  SET p_ok=1; SET p_msg='OK';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_cambiar_estado_envio` (IN `p_envio_id` INT, IN `p_nuevo_estado` VARCHAR(40), IN `p_usuario_id` INT, IN `p_lat` DECIMAL(10,8), IN `p_lng` DECIMAL(11,8), IN `p_notes` TEXT, OUT `p_ok` TINYINT, OUT `p_msg` VARCHAR(150))   proc: BEGIN
  -- Las declaraciones DECLARE deben ir PRIMERO
  DECLARE v_actual VARCHAR(40);
  DECLARE v_valid INT DEFAULT 0;
  
  -- Luego las demás instrucciones
  SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
  SET p_ok=0; SET p_msg=NULL;
  
  SELECT status INTO v_actual FROM envios
    WHERE id=p_envio_id FOR UPDATE;
  IF v_actual IS NULL THEN
     SET p_msg='No existe envío'; LEAVE proc;
  END IF;
  SELECT COUNT(*) INTO v_valid FROM estado_envio_transiciones
    WHERE estado_origen=v_actual AND estado_destino=p_nuevo_estado;
  IF v_valid=0 AND p_nuevo_estado NOT IN ('Intento fallido') THEN
     SET p_msg=CONCAT('Transición inválida ',v_actual,' -> ',p_nuevo_estado);
     LEAVE proc;
  END IF;
  UPDATE envios SET
     status=p_nuevo_estado,
     lat=IFNULL(p_lat,lat),
     lng=IFNULL(p_lng,lng),
     delivery_date=IF(p_nuevo_estado='Entregado',NOW(),delivery_date),
     updated_at=NOW()
   WHERE id=p_envio_id;
  INSERT INTO tracking_history(
    envio_id,status,location,notes,created_by)
    VALUES(
      p_envio_id,
      p_nuevo_estado,
      IF(p_lat IS NULL OR p_lng IS NULL,NULL,CONCAT('Lat: ',p_lat,', Lng: ',p_lng)),
      p_notes,
      p_usuario_id
    );
  SET p_ok=1; SET p_msg='OK';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_obtener_repartidores_activos` ()   BEGIN
    SELECT * FROM vista_repartidores_activos;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_toggle_cliente_status` (IN `p_cliente_id` INT, IN `p_status` VARCHAR(20), OUT `p_ok` TINYINT)   BEGIN
  UPDATE usuarios SET status=p_status
   WHERE id=p_cliente_id AND rol_id=2;               
  SET p_ok=(ROW_COUNT()>0);                          
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_tracking_update_position` (IN `p_envio_id` INT, IN `p_status` VARCHAR(40), IN `p_usuario_id` INT, IN `p_lat` DECIMAL(10,8), IN `p_lng` DECIMAL(11,8), OUT `p_ok` TINYINT, OUT `p_msg` VARCHAR(150))   BEGIN
  CALL sp_cambiar_estado_envio(                       
    p_envio_id,p_status,p_usuario_id,p_lat,p_lng,'Update auto',@ok,@msg);
  SELECT @ok,@msg INTO p_ok,p_msg;                    
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_update_repartidor_status` (IN `p_usuario_id` INT, IN `p_status` VARCHAR(20), OUT `p_ok` TINYINT)   BEGIN
  UPDATE repartidores SET status=p_status
   WHERE usuario_id=p_usuario_id;
  SET p_ok=(ROW_COUNT()>0);
END$$

DELIMITER ;

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
  `estatus` enum('activo','vencido','cancelado') DEFAULT 'activo',
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `firmado_cliente` tinyint(1) DEFAULT 0,
  `firmado_empresa` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `peso` decimal(10,2) NOT NULL,
  `dimensiones` varchar(50) DEFAULT NULL,
  `valor_declarado` decimal(12,2) DEFAULT NULL,
  `precio_estimado` decimal(10,2) NOT NULL,
  `estatus` enum('pendiente','aprobada','rechazada','convertida') DEFAULT 'pendiente',
  `fecha_validez` date NOT NULL,
  `notas` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `estatus` enum('vigente','por_vencer','vencido') DEFAULT 'vigente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `fecha_pago` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `envios`
--

INSERT INTO `envios` (`id`, `usuario_id`, `name`, `email`, `phone`, `office_phone`, `origin`, `destination`, `description`, `value`, `tracking_number`, `delivery_date`, `package_type`, `weight`, `insurance`, `urgent`, `additional_notes`, `package_image`, `estimated_cost`, `status`, `created_at`, `lat`, `lng`, `estado_pago`, `fecha_pago`) VALUES
(15, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A #357 Juan Pablo ll 97236', 'c 55A #357 x 18 y 20 juan pablo ll 97246', 'CAJA DE HERRAMIENTAS MUY PESADA', NULL, 'MENDEZ-CB864B29', '2025-04-17', 'paquete_mediano', 12.00, 1, 1, 'XD', '', 485.00, 'Procesando', '2025-04-16 05:48:02', 20.96737000, -89.59258600, 'pendiente', NULL),
(16, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'hjvjvhj', NULL, 'MENDEZ-FA2681B2', '2025-04-23', 'paquete_pequeno', 678.00, 1, 1, 'ghjghj', '', 11013.90, 'Procesando', '2025-04-16 05:52:28', NULL, NULL, 'pendiente', NULL),
(17, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'hjvjvhj', NULL, 'MENDEZ-10402858', '2025-04-23', 'paquete_pequeno', 678.00, 1, 1, 'ghjghj', '', 11013.90, 'Procesando', '2025-04-16 05:52:32', NULL, NULL, 'pendiente', NULL),
(18, 24, 'Josué Gamboa', 'gamboajosue541@gmail.com', '999-645-4541', '', 'Una tienda abarrotes García', 'Xoclán', 'Folletos', NULL, 'MENDEZ-4653DDCF', '2025-04-30', 'paquete_mediano', 3.00, 0, 0, 'Frágil', '', 130.00, 'Procesando', '2025-04-18 02:46:58', NULL, NULL, 'pendiente', NULL),
(19, 23, 'thales', 'thales995aaa@gmail.com', '111-111-1111', '', 'xdfxfxfxdf', 'cdddd', 'xd', NULL, 'MENDEZ-80BC7E39', '2025-04-19', 'documento', 1.00, 1, 1, 'xddddd', '', 310.00, 'Procesando', '2025-04-18 08:52:55', NULL, NULL, 'pendiente', NULL),
(20, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'fasdfgasdfg', NULL, 'MENDEZ-96F3BBB5', '2025-04-30', 'paquete_mediano', 12.00, 1, 1, 'fslvnsdfopjmgsod', '', 12036.15, 'Procesando', '2025-04-24 02:51:14', NULL, NULL, 'pendiente', NULL),
(21, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'fasdfgasdfg', NULL, 'MENDEZ-77A3C588', '2025-04-30', 'paquete_mediano', 12.00, 1, 1, 'fslvnsdfopjmgsod', '', 12036.15, 'Procesando', '2025-04-24 02:51:19', NULL, NULL, 'pendiente', NULL),
(22, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'fvasdfv', NULL, 'MENDEZ-DA90CCDE', '2025-04-30', 'paquete_mediano', 12.00, 1, 1, '13123', '', 426.15, 'Procesando', '2025-04-29 05:22:31', NULL, NULL, 'pendiente', NULL),
(23, 1, 'Jafeth Daniel Gamboa Baas', 'LE21080769@merida.tecnm.mx', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'vzsdfg', NULL, 'MENDEZ-3F0002CC', '2025-04-30', 'documento', 12.00, 1, 1, 'kjanfjoñasnfvjon', '', 1035.65, 'Procesando', '2025-04-29 05:24:29', NULL, NULL, 'pendiente', NULL),
(24, 1, 'Jafeth Daniel Gamboa Baas', 'Jafethgamboa27@gmail.com', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'ñnvoñadfnmv', NULL, 'MENDEZ-20C78B68', '2025-04-30', 'documento', 0.20, 0, 0, 'fvldmf', '', 102.00, 'Procesando', '2025-04-29 05:26:15', NULL, NULL, 'pendiente', NULL),
(25, 1, 'Jafeth Daniel Gamboa Baas', 'Jafethgamboa27@gmail.com', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'ñnvoñadfnmv', NULL, 'MENDEZ-568B4C7B', '2025-04-30', 'documento', 0.20, 0, 0, 'fvldmf', '', 102.00, 'Procesando', '2025-04-29 05:26:19', NULL, NULL, 'pendiente', NULL),
(27, 21, 'avix garcia lopez', '123@gmail.com', '224-567-3399', '223455677', 'calle lopez, 14, col. progreso cp. 91000', 'calle mango,23 col. flores cp.91000', ':3', NULL, 'MENDEZ-FFAD699D', '2025-05-24', 'paquete_mediano', 8.00, 1, 1, 'uwu', '', 480.00, 'Procesando', '2025-04-30 03:28:00', NULL, NULL, 'pendiente', NULL),
(28, 21, 'avix garcia lopez', '123@gmail.com', '224-567-3399', '223455677', 'calle lopez, 14, col. progreso cp. 91000', 'calle mango,23 col. flores cp.91000', ':3', NULL, 'MENDEZ-1A202458', '2025-05-24', 'paquete_mediano', 8.00, 1, 1, 'uwu', '', 480.00, 'Procesando', '2025-04-30 03:28:03', NULL, NULL, 'pendiente', NULL),
(29, 21, 'avix garcia lopez', '123@gmail.com', '224-567-3399', '223455677', 'calle lopez, 14, col. progreso cp. 91000', 'calle mango,23 col. flores cp.91000', ':3', NULL, 'MENDEZ-D7236B32', '2025-05-24', 'paquete_mediano', 8.00, 1, 1, 'uwu', '', 480.00, 'Procesando', '2025-04-30 03:28:05', NULL, NULL, 'pendiente', NULL),
(30, 21, 'avix garcia lopez', '123@gmail.com', '224-567-3399', '223455677', 'calle lopez, 14, col. progreso cp. 91000', 'calle mango,23 col. flores cp.91000', ':3', NULL, 'MENDEZ-0AED579E', '2025-05-24', 'paquete_mediano', 8.00, 1, 1, 'uwu', '', 480.00, 'Procesando', '2025-04-30 03:28:08', NULL, NULL, 'pendiente', NULL),
(31, 21, 'avix garcia lopez', '123@gmail.com', '224-567-3399', '223455677', 'calle lopez, 14, col. progreso cp. 91000', 'calle mango,23 col. flores cp.91000', ':3', NULL, 'MENDEZ-C7CD52DC', '2025-05-24', 'paquete_mediano', 8.00, 1, 1, 'uwu', '', 480.00, 'Procesando', '2025-04-30 03:28:12', NULL, NULL, 'pendiente', NULL),
(32, 21, 'avix garcia lopez', '123@gmail.com', '224-567-3399', '223455677', 'calle lopez, 14, col. progreso cp. 91000', 'calle mango,23 col. flores cp.91000', ':3', NULL, 'MENDEZ-0F287266', '2025-05-24', 'paquete_mediano', 8.00, 1, 1, 'uwu', '', 480.00, 'Procesando', '2025-04-30 03:28:16', NULL, NULL, 'pendiente', NULL),
(33, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C. 55ᴬ 357, Juan Pablo II, 97246', 'C. 31 235, Juan Pablo II, 97246', 'HOLA', NULL, 'MENDEZ-7B25AB19', '2025-05-14', 'documento', 23.00, 0, 0, '', '', 330.00, 'Procesando', '2025-05-10 02:36:27', NULL, NULL, 'pendiente', NULL),
(34, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España', 'DROGAS', NULL, 'MENDEZ-5CFD04CC', '2025-06-26', 'carga_voluminosa', 500.00, 0, 0, 'MARIHUANA', '', 0.00, 'Procesando', '2025-06-07 09:24:50', NULL, NULL, 'pendiente', NULL),
(35, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España', 'DROGAS', NULL, 'MENDEZ-954B5638', '2025-06-26', 'carga_voluminosa', 500.00, 0, 0, 'MARIHUANA', '', 0.00, 'Procesando', '2025-06-07 09:27:32', NULL, NULL, 'pendiente', NULL),
(36, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España', 'DROGAS', NULL, 'MENDEZ-82C051A7', '2025-06-26', 'carga_voluminosa', 500.00, 0, 0, 'MARIHUANA', '', 0.00, 'Procesando', '2025-06-07 09:28:14', NULL, NULL, 'pendiente', NULL),
(37, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España', 'DROGAS', NULL, 'MENDEZ-C8CD30F7', '2025-06-26', 'carga_voluminosa', 500.00, 0, 0, 'MARIHUANA', '', 0.00, 'Procesando', '2025-06-07 09:35:44', NULL, NULL, 'pendiente', NULL),
(38, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España', 'DROGAS', NULL, 'MENDEZ-1B101B5B', '2025-06-26', 'carga_voluminosa', 500.00, 0, 0, 'MARIHUANA', '', 0.00, 'Procesando', '2025-06-07 09:35:55', NULL, NULL, 'pendiente', NULL),
(39, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España', 'DROGAS', NULL, 'MENDEZ-A66BC287', '2025-06-26', 'carga_voluminosa', 500.00, 0, 0, 'MARIHUANA', '', 0.00, 'Procesando', '2025-06-07 09:57:02', NULL, NULL, 'pendiente', NULL),
(40, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España', 'DROGAS', NULL, 'MENDEZ-76115E49', '2025-06-26', 'carga_voluminosa', 500.00, 0, 0, 'MARIHUANA', '', 0.00, 'Procesando', '2025-06-07 10:30:48', NULL, NULL, 'pendiente', NULL),
(41, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España', 'DROGAS', NULL, 'MENDEZ-2BE2E2A4', '2025-06-26', 'carga_voluminosa', 500.00, 0, 0, 'MARIHUANA', '', 0.00, 'Procesando', '2025-06-07 10:35:47', NULL, NULL, 'pendiente', NULL),
(42, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA', 'dnaskldnaskod', NULL, 'MENDEZ-5DBF3BBF', '2025-06-30', 'paquete_pequeno', 30.00, 1, 1, 'XCSC', '', 0.00, 'Procesando', '2025-06-12 06:15:23', NULL, NULL, 'pendiente', NULL),
(43, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA', 'dnaskldnaskod', NULL, 'MENDEZ-55601BBC', '2025-06-30', 'paquete_pequeno', 30.00, 1, 1, 'XCSC', '', 0.00, 'Procesando', '2025-06-12 06:21:53', NULL, NULL, 'pendiente', NULL),
(44, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA', 'dnaskldnaskod', NULL, 'MENDEZ-942EA441', '2025-06-30', 'paquete_pequeno', 30.00, 1, 1, 'XCSC', '', 0.00, 'Procesando', '2025-06-12 07:02:32', NULL, NULL, 'pendiente', NULL),
(45, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA', 'dnaskldnaskod', NULL, 'MENDEZ-700829D5', '2025-06-30', 'paquete_pequeno', 30.00, 1, 1, 'XCSC', '', 0.00, 'Procesando', '2025-06-12 07:08:01', NULL, NULL, 'pendiente', NULL),
(46, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA', 'dnaskldnaskod', NULL, 'MENDEZ-173FB574', '2025-06-30', 'paquete_pequeno', 30.00, 1, 1, 'XCSC', '', 0.00, 'Procesando', '2025-06-12 07:08:50', NULL, NULL, 'pendiente', NULL),
(47, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA', 'dnaskldnaskod', NULL, 'MENDEZ-57BF05D7', '2025-06-30', 'paquete_pequeno', 30.00, 1, 1, 'XCSC', '', 0.00, 'Procesando', '2025-06-12 07:08:54', NULL, NULL, 'pendiente', NULL),
(48, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA', 'dnaskldnaskod', NULL, 'MENDEZ-E7C1BC2D', '2025-06-30', 'paquete_pequeno', 30.00, 1, 1, 'XCSC', '', 9835.77, 'En tránsito', '2025-06-12 07:18:00', NULL, NULL, 'pendiente', NULL),
(49, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'C57 347, juan pablo ll, 97246, Mérida, Yucatán, México', 'MARIHUANA', NULL, 'MENDEZ-BE57C331', '2025-06-30', 'paquete_pequeno', 20.00, 0, 1, 'askdnasdas', '', 215.67, 'En tránsito', '2025-06-13 04:37:10', NULL, NULL, 'pendiente', NULL),
(50, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 357, juan pablo ll, 97246, Mérida, Yucatán, México', 'C55A 377, juan pablo ll, 97246, Mérida, Yucatán, México', 'fasdfs', NULL, 'MENDEZ-EC35EAF7', '2025-06-23', 'paquete_pequeno', 12.00, 0, 1, 'nhfgh', '', 173.31, 'En tránsito', '2025-06-13 05:34:19', NULL, NULL, 'pendiente', NULL),
(51, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 357, juan pablo ll, 97246, Mérida, Yucatán, México', 'C55A 377, juan pablo ll, 97246, Mérida, Yucatán, México', 'kkllkl', NULL, 'MENDEZ-77D7726B', '2025-06-24', 'documento', 12.00, 0, 1, 'ñlklkl', '', 158.31, 'En tránsito', '2025-06-13 05:36:07', NULL, NULL, 'pendiente', NULL),
(52, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 357, juan pablo ll, 97246, Mérida, Yucatán, México', 'C55A 400, juan pablo ll, 97246, Mérida, Yucatán, México', 'dfgsdfgsdf', NULL, 'MENDEZ-1590B212', '2025-06-29', 'paquete_pequeno', 12.00, 0, 1, 'asdad', '', 167.06, 'En tránsito', '2025-06-13 06:15:50', NULL, NULL, 'pendiente', NULL),
(53, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 357, juan pablo ll, 97246, Mérida, Yucatán, México', 'C55A 400, juan pablo ll, 97246, Mérida, Yucatán, México', 'dfgsdfgsdf', NULL, 'MENDEZ-7613B746', '2025-06-29', 'paquete_pequeno', 12.00, 0, 1, 'asdad', '', 167.06, 'En tránsito', '2025-06-13 06:15:55', NULL, NULL, 'pagado', '2025-06-13 07:04:58'),
(54, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 357, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià,, 03510, Alacant, almedia, España', 'cocaina', NULL, 'MENDEZ-47264C62', '2025-06-30', 'paquete_mediano', 30.00, 0, 1, 'xd', '', 12350.96, 'En tránsito', '2025-06-13 14:13:31', NULL, NULL, 'pagado', '2025-06-14 00:09:55'),
(55, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 355, juan pablo ll, 97246, Mérida, Yucatán, México', 'C55A 400, juan pablo ll, 97246, Mérida, Yucatán, México', 'xd', NULL, 'MENDEZ-BB356DED', '2025-06-24', 'paquete_mediano', 12.00, 0, 0, '', '', 172.50, 'En tránsito', '2025-06-13 14:15:34', NULL, NULL, 'pagado', '2025-06-13 08:20:12'),
(56, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 357, juan pablo ll, 97246, Mérida, Yucatán, México', 'C55A 400, juan pablo ll, 97246, Mérida, Yucatán, México', 'COCAINA', NULL, 'MENDEZ-FAEA8E96', '2025-06-29', 'paquete_mediano', 30.00, 0, 1, 'XD', '', 358.31, 'En tránsito', '2025-06-14 06:10:50', NULL, NULL, 'pendiente', NULL),
(57, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 103, Santa coloma de gramanet, 08924, Mérida, Yucatán, México', 'DRUGS', 0.00, 'MENDEZ-B4240C1C', '2025-08-25', 'paquete_mediano', 10.00, 1, 0, '', 'uploads/688d0b3714f60_7f13f56e-9a1d-4e67-aff8-7f216b0333d6.png', 13302.79, 'En tránsito', '2025-08-01 18:45:11', NULL, NULL, 'pendiente', NULL),
(58, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 103, Santa coloma de gramanet, 08924, barcelona, cataluña, España', 'NALAS', 0.00, 'MENDEZ-D40F3013', '2025-08-31', 'carga_voluminosa', 400.00, 1, 1, 'NALA PITBULL', 'uploads/688d166284361_0f60f665aae299f4bffc1993b2c3abff.png', 24033.99, 'En tránsito', '2025-08-01 19:32:50', NULL, NULL, 'pagado', '2025-08-01 13:34:22'),
(59, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 103, Santa coloma de gramanet, 08924, barcelona, cataluña, España', 'NALAS', 0.00, 'MENDEZ-7DBA2E9B', '2025-08-31', 'carga_voluminosa', 400.00, 1, 1, 'NALA PITBULL', 'uploads/688d1666426de_0f60f665aae299f4bffc1993b2c3abff.png', 24033.99, 'Entregado', '2025-08-01 19:32:54', NULL, NULL, 'pendiente', NULL),
(60, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 103, Santa coloma de gramanet, 08924, barcelona, cataluña, España', 'rfsdcvsdf', 3222.00, 'MENDEZ-B6577D96', '2025-08-27', 'paquete_mediano', 1212.00, 1, 1, 'dasdad', '', 28097.86, 'En tránsito', '2025-08-10 07:00:16', NULL, NULL, 'pendiente', NULL),
(61, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 103, Santa coloma de gramanet, 08924, barcelona, cataluña, España', 'NALA COMIDA', 100.00, 'MENDEZ-432480CE', '2025-08-20', 'paquete_pequeno', 10.00, 1, 1, 'COMIDA FRAGIL', 'uploads/packages/pkg_20250810_090554_689844d27692d.jpg', 16615.24, 'En tránsito', '2025-08-10 07:05:54', NULL, NULL, 'pendiente', NULL),
(62, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 101, Santa coloma de gramanet, 08924, barcelona, cataluña, España', 'FRAGIL VASOS CRISTAL', 3000.00, 'MENDEZ-EADA7855', '2025-08-20', 'paquete_mediano', 100.00, 1, 1, 'HOLA', 'uploads/packages/pkg_20250810_234708_6899135c61112.png', 17658.99, 'En tránsito', '2025-08-10 21:47:08', NULL, NULL, 'pagado', '2025-08-10 15:47:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `envios_backup`
--

CREATE TABLE `envios_backup` (
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
  `fecha_pago` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `envios_backup`
--

INSERT INTO `envios_backup` (`id`, `usuario_id`, `name`, `email`, `phone`, `office_phone`, `origin`, `destination`, `description`, `value`, `tracking_number`, `delivery_date`, `package_type`, `weight`, `insurance`, `urgent`, `additional_notes`, `package_image`, `estimated_cost`, `status`, `created_at`, `lat`, `lng`, `estado_pago`, `fecha_pago`) VALUES
(15, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A #357 Juan Pablo ll 97236', 'c 55A #357 x 18 y 20 juan pablo ll 97246', 'CAJA DE HERRAMIENTAS MUY PESADA', NULL, 'MENDEZ-CB864B29', '2025-04-17', 'paquete_mediano', 12.00, 1, 1, 'XD', '', 485.00, 'Procesando', '2025-04-16 05:48:02', 20.96737000, -89.59258600, 'pendiente', NULL),
(16, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'hjvjvhj', NULL, 'MENDEZ-FA2681B2', '2025-04-23', 'paquete_pequeno', 678.00, 1, 1, 'ghjghj', '', 11013.90, 'Procesando', '2025-04-16 05:52:28', NULL, NULL, 'pendiente', NULL),
(17, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'hjvjvhj', NULL, 'MENDEZ-10402858', '2025-04-23', 'paquete_pequeno', 678.00, 1, 1, 'ghjghj', '', 11013.90, 'Procesando', '2025-04-16 05:52:32', NULL, NULL, 'pendiente', NULL),
(18, 24, 'Josué Gamboa', 'gamboajosue541@gmail.com', '999-645-4541', '', 'Una tienda abarrotes García', 'Xoclán', 'Folletos', NULL, 'MENDEZ-4653DDCF', '2025-04-30', 'paquete_mediano', 3.00, 0, 0, 'Frágil', '', 130.00, 'Procesando', '2025-04-18 02:46:58', NULL, NULL, 'pendiente', NULL),
(19, 23, 'thales', 'thales995aaa@gmail.com', '111-111-1111', '', 'xdfxfxfxdf', 'cdddd', 'xd', NULL, 'MENDEZ-80BC7E39', '2025-04-19', 'documento', 1.00, 1, 1, 'xddddd', '', 310.00, 'Procesando', '2025-04-18 08:52:55', NULL, NULL, 'pendiente', NULL),
(20, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'fasdfgasdfg', NULL, 'MENDEZ-96F3BBB5', '2025-04-30', 'paquete_mediano', 12.00, 1, 1, 'fslvnsdfopjmgsod', '', 12036.15, 'Procesando', '2025-04-24 02:51:14', NULL, NULL, 'pendiente', NULL),
(21, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'fasdfgasdfg', NULL, 'MENDEZ-77A3C588', '2025-04-30', 'paquete_mediano', 12.00, 1, 1, 'fslvnsdfopjmgsod', '', 12036.15, 'Procesando', '2025-04-24 02:51:19', NULL, NULL, 'pendiente', NULL),
(22, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'fvasdfv', NULL, 'MENDEZ-DA90CCDE', '2025-04-30', 'paquete_mediano', 12.00, 1, 1, '13123', '', 426.15, 'Procesando', '2025-04-29 05:22:31', NULL, NULL, 'pendiente', NULL),
(23, 1, 'Jafeth Daniel Gamboa Baas', 'LE21080769@merida.tecnm.mx', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'vzsdfg', NULL, 'MENDEZ-3F0002CC', '2025-04-30', 'documento', 12.00, 1, 1, 'kjanfjoñasnfvjon', '', 1035.65, 'Procesando', '2025-04-29 05:24:29', NULL, NULL, 'pendiente', NULL),
(24, 1, 'Jafeth Daniel Gamboa Baas', 'Jafethgamboa27@gmail.com', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'ñnvoñadfnmv', NULL, 'MENDEZ-20C78B68', '2025-04-30', 'documento', 0.20, 0, 0, 'fvldmf', '', 102.00, 'Procesando', '2025-04-29 05:26:15', NULL, NULL, 'pendiente', NULL),
(25, 1, 'Jafeth Daniel Gamboa Baas', 'Jafethgamboa27@gmail.com', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'ñnvoñadfnmv', NULL, 'MENDEZ-568B4C7B', '2025-04-30', 'documento', 0.20, 0, 0, 'fvldmf', '', 102.00, 'Procesando', '2025-04-29 05:26:19', NULL, NULL, 'pendiente', NULL),
(27, 21, 'avix garcia lopez', '123@gmail.com', '224-567-3399', '223455677', 'calle lopez, 14, col. progreso cp. 91000', 'calle mango,23 col. flores cp.91000', ':3', NULL, 'MENDEZ-FFAD699D', '2025-05-24', 'paquete_mediano', 8.00, 1, 1, 'uwu', '', 480.00, 'Procesando', '2025-04-30 03:28:00', NULL, NULL, 'pendiente', NULL),
(28, 21, 'avix garcia lopez', '123@gmail.com', '224-567-3399', '223455677', 'calle lopez, 14, col. progreso cp. 91000', 'calle mango,23 col. flores cp.91000', ':3', NULL, 'MENDEZ-1A202458', '2025-05-24', 'paquete_mediano', 8.00, 1, 1, 'uwu', '', 480.00, 'Procesando', '2025-04-30 03:28:03', NULL, NULL, 'pendiente', NULL),
(29, 21, 'avix garcia lopez', '123@gmail.com', '224-567-3399', '223455677', 'calle lopez, 14, col. progreso cp. 91000', 'calle mango,23 col. flores cp.91000', ':3', NULL, 'MENDEZ-D7236B32', '2025-05-24', 'paquete_mediano', 8.00, 1, 1, 'uwu', '', 480.00, 'Procesando', '2025-04-30 03:28:05', NULL, NULL, 'pendiente', NULL),
(30, 21, 'avix garcia lopez', '123@gmail.com', '224-567-3399', '223455677', 'calle lopez, 14, col. progreso cp. 91000', 'calle mango,23 col. flores cp.91000', ':3', NULL, 'MENDEZ-0AED579E', '2025-05-24', 'paquete_mediano', 8.00, 1, 1, 'uwu', '', 480.00, 'Procesando', '2025-04-30 03:28:08', NULL, NULL, 'pendiente', NULL),
(31, 21, 'avix garcia lopez', '123@gmail.com', '224-567-3399', '223455677', 'calle lopez, 14, col. progreso cp. 91000', 'calle mango,23 col. flores cp.91000', ':3', NULL, 'MENDEZ-C7CD52DC', '2025-05-24', 'paquete_mediano', 8.00, 1, 1, 'uwu', '', 480.00, 'Procesando', '2025-04-30 03:28:12', NULL, NULL, 'pendiente', NULL),
(32, 21, 'avix garcia lopez', '123@gmail.com', '224-567-3399', '223455677', 'calle lopez, 14, col. progreso cp. 91000', 'calle mango,23 col. flores cp.91000', ':3', NULL, 'MENDEZ-0F287266', '2025-05-24', 'paquete_mediano', 8.00, 1, 1, 'uwu', '', 480.00, 'Procesando', '2025-04-30 03:28:16', NULL, NULL, 'pendiente', NULL),
(33, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C. 55ᴬ 357, Juan Pablo II, 97246', 'C. 31 235, Juan Pablo II, 97246', 'HOLA', NULL, 'MENDEZ-7B25AB19', '2025-05-14', 'documento', 23.00, 0, 0, '', '', 330.00, 'Procesando', '2025-05-10 02:36:27', NULL, NULL, 'pendiente', NULL),
(34, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España', 'DROGAS', NULL, 'MENDEZ-5CFD04CC', '2025-06-26', 'carga_voluminosa', 500.00, 0, 0, 'MARIHUANA', '', 0.00, 'Procesando', '2025-06-07 09:24:50', NULL, NULL, 'pendiente', NULL),
(35, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España', 'DROGAS', NULL, 'MENDEZ-954B5638', '2025-06-26', 'carga_voluminosa', 500.00, 0, 0, 'MARIHUANA', '', 0.00, 'Procesando', '2025-06-07 09:27:32', NULL, NULL, 'pendiente', NULL),
(36, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España', 'DROGAS', NULL, 'MENDEZ-82C051A7', '2025-06-26', 'carga_voluminosa', 500.00, 0, 0, 'MARIHUANA', '', 0.00, 'Procesando', '2025-06-07 09:28:14', NULL, NULL, 'pendiente', NULL),
(37, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España', 'DROGAS', NULL, 'MENDEZ-C8CD30F7', '2025-06-26', 'carga_voluminosa', 500.00, 0, 0, 'MARIHUANA', '', 0.00, 'Procesando', '2025-06-07 09:35:44', NULL, NULL, 'pendiente', NULL),
(38, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España', 'DROGAS', NULL, 'MENDEZ-1B101B5B', '2025-06-26', 'carga_voluminosa', 500.00, 0, 0, 'MARIHUANA', '', 0.00, 'Procesando', '2025-06-07 09:35:55', NULL, NULL, 'pendiente', NULL),
(39, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España', 'DROGAS', NULL, 'MENDEZ-A66BC287', '2025-06-26', 'carga_voluminosa', 500.00, 0, 0, 'MARIHUANA', '', 0.00, 'Procesando', '2025-06-07 09:57:02', NULL, NULL, 'pendiente', NULL),
(40, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España', 'DROGAS', NULL, 'MENDEZ-76115E49', '2025-06-26', 'carga_voluminosa', 500.00, 0, 0, 'MARIHUANA', '', 0.00, 'Procesando', '2025-06-07 10:30:48', NULL, NULL, 'pendiente', NULL),
(41, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, Comunidad Valenciana, España', 'DROGAS', NULL, 'MENDEZ-2BE2E2A4', '2025-06-26', 'carga_voluminosa', 500.00, 0, 0, 'MARIHUANA', '', 0.00, 'Procesando', '2025-06-07 10:35:47', NULL, NULL, 'pendiente', NULL),
(42, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA', 'dnaskldnaskod', NULL, 'MENDEZ-5DBF3BBF', '2025-06-30', 'paquete_pequeno', 30.00, 1, 1, 'XCSC', '', 0.00, 'Procesando', '2025-06-12 06:15:23', NULL, NULL, 'pendiente', NULL),
(43, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA', 'dnaskldnaskod', NULL, 'MENDEZ-55601BBC', '2025-06-30', 'paquete_pequeno', 30.00, 1, 1, 'XCSC', '', 0.00, 'Procesando', '2025-06-12 06:21:53', NULL, NULL, 'pendiente', NULL),
(44, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA', 'dnaskldnaskod', NULL, 'MENDEZ-942EA441', '2025-06-30', 'paquete_pequeno', 30.00, 1, 1, 'XCSC', '', 0.00, 'Procesando', '2025-06-12 07:02:32', NULL, NULL, 'pendiente', NULL),
(45, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA', 'dnaskldnaskod', NULL, 'MENDEZ-700829D5', '2025-06-30', 'paquete_pequeno', 30.00, 1, 1, 'XCSC', '', 0.00, 'Procesando', '2025-06-12 07:08:01', NULL, NULL, 'pendiente', NULL),
(46, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA', 'dnaskldnaskod', NULL, 'MENDEZ-173FB574', '2025-06-30', 'paquete_pequeno', 30.00, 1, 1, 'XCSC', '', 0.00, 'Procesando', '2025-06-12 07:08:50', NULL, NULL, 'pendiente', NULL),
(47, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA', 'dnaskldnaskod', NULL, 'MENDEZ-57BF05D7', '2025-06-30', 'paquete_pequeno', 30.00, 1, 1, 'XCSC', '', 0.00, 'Procesando', '2025-06-12 07:08:54', NULL, NULL, 'pendiente', NULL),
(48, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià, 03510, Alacant, almedia, ESPAÑA', 'dnaskldnaskod', NULL, 'MENDEZ-E7C1BC2D', '2025-06-30', 'paquete_pequeno', 30.00, 1, 1, 'XCSC', '', 9835.77, 'Procesando', '2025-06-12 07:18:00', NULL, NULL, 'pendiente', NULL),
(49, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'C57 347, juan pablo ll, 97246, Mérida, Yucatán, México', 'MARIHUANA', NULL, 'MENDEZ-BE57C331', '2025-06-30', 'paquete_pequeno', 20.00, 0, 1, 'askdnasdas', '', 215.67, 'Procesando', '2025-06-13 04:37:10', NULL, NULL, 'pendiente', NULL),
(50, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 357, juan pablo ll, 97246, Mérida, Yucatán, México', 'C55A 377, juan pablo ll, 97246, Mérida, Yucatán, México', 'fasdfs', NULL, 'MENDEZ-EC35EAF7', '2025-06-23', 'paquete_pequeno', 12.00, 0, 1, 'nhfgh', '', 173.31, 'Procesando', '2025-06-13 05:34:19', NULL, NULL, 'pendiente', NULL),
(51, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 357, juan pablo ll, 97246, Mérida, Yucatán, México', 'C55A 377, juan pablo ll, 97246, Mérida, Yucatán, México', 'kkllkl', NULL, 'MENDEZ-77D7726B', '2025-06-24', 'documento', 12.00, 0, 1, 'ñlklkl', '', 158.31, 'Procesando', '2025-06-13 05:36:07', NULL, NULL, 'pendiente', NULL),
(52, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 357, juan pablo ll, 97246, Mérida, Yucatán, México', 'C55A 400, juan pablo ll, 97246, Mérida, Yucatán, México', 'dfgsdfgsdf', NULL, 'MENDEZ-1590B212', '2025-06-29', 'paquete_pequeno', 12.00, 0, 1, 'asdad', '', 167.06, 'En tr├ínsito', '2025-06-13 06:15:50', NULL, NULL, 'pendiente', NULL),
(53, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 357, juan pablo ll, 97246, Mérida, Yucatán, México', 'C55A 400, juan pablo ll, 97246, Mérida, Yucatán, México', 'dfgsdfgsdf', NULL, 'MENDEZ-7613B746', '2025-06-29', 'paquete_pequeno', 12.00, 0, 1, 'asdad', '', 167.06, 'En tr├ínsito', '2025-06-13 06:15:55', NULL, NULL, 'pagado', '2025-06-13 07:04:58'),
(54, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 357, juan pablo ll, 97246, Mérida, Yucatán, México', 'Carrer Abdet 6, Callosa d&#039;en Sarrià,, 03510, Alacant, almedia, España', 'cocaina', NULL, 'MENDEZ-47264C62', '2025-06-30', 'paquete_mediano', 30.00, 0, 1, 'xd', '', 12350.96, 'En tránsito	\n', '2025-06-13 14:13:31', NULL, NULL, 'pagado', '2025-06-14 00:09:55'),
(55, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 355, juan pablo ll, 97246, Mérida, Yucatán, México', 'C55A 400, juan pablo ll, 97246, Mérida, Yucatán, México', 'xd', NULL, 'MENDEZ-BB356DED', '2025-06-24', 'paquete_mediano', 12.00, 0, 0, '', '', 172.50, 'En tránsito	\n', '2025-06-13 14:15:34', NULL, NULL, 'pagado', '2025-06-13 08:20:12'),
(56, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 357, juan pablo ll, 97246, Mérida, Yucatán, México', 'C55A 400, juan pablo ll, 97246, Mérida, Yucatán, México', 'COCAINA', NULL, 'MENDEZ-FAEA8E96', '2025-06-29', 'paquete_mediano', 30.00, 0, 1, 'XD', '', 358.31, 'En tránsito', '2025-06-14 06:10:50', NULL, NULL, 'pendiente', NULL),
(57, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 103, Santa coloma de gramanet, 08924, Mérida, Yucatán, México', 'DRUGS', 0.00, 'MENDEZ-B4240C1C', '2025-08-25', 'paquete_mediano', 10.00, 1, 0, '', 'uploads/688d0b3714f60_7f13f56e-9a1d-4e67-aff8-7f216b0333d6.png', 13302.79, 'En tránsito', '2025-08-01 18:45:11', NULL, NULL, 'pendiente', NULL),
(58, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 103, Santa coloma de gramanet, 08924, barcelona, cataluña, España', 'NALAS', 0.00, 'MENDEZ-D40F3013', '2025-08-31', 'carga_voluminosa', 400.00, 1, 1, 'NALA PITBULL', 'uploads/688d166284361_0f60f665aae299f4bffc1993b2c3abff.png', 24033.99, 'En tránsito', '2025-08-01 19:32:50', NULL, NULL, 'pagado', '2025-08-01 13:34:22'),
(59, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '9996369799', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 103, Santa coloma de gramanet, 08924, barcelona, cataluña, España', 'NALAS', 0.00, 'MENDEZ-7DBA2E9B', '2025-08-31', 'carga_voluminosa', 400.00, 1, 1, 'NALA PITBULL', 'uploads/688d1666426de_0f60f665aae299f4bffc1993b2c3abff.png', 24033.99, 'Entregado', '2025-08-01 19:32:54', NULL, NULL, 'pendiente', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado_envio_transiciones`
--

CREATE TABLE `estado_envio_transiciones` (
  `id` int(11) NOT NULL,
  `estado_origen` varchar(40) NOT NULL,
  `estado_destino` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estado_envio_transiciones`
--

INSERT INTO `estado_envio_transiciones` (`id`, `estado_origen`, `estado_destino`) VALUES
(23, 'En ruta', 'Cancelado'),
(21, 'En ruta', 'Entregado'),
(22, 'En ruta', 'Intento fallido'),
(20, 'En tránsito', 'Cancelado'),
(19, 'En tránsito', 'En ruta'),
(24, 'Intento fallido', 'En ruta'),
(18, 'Procesando', 'Cancelado'),
(17, 'Procesando', 'En tránsito');

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
  `monto` decimal(10,2) NOT NULL,
  `status` enum('pendiente','pagado','cancelado','vencido') DEFAULT 'pendiente',
  `metodo_pago` varchar(50) DEFAULT NULL,
  `referencia_pago` varchar(100) DEFAULT NULL,
  `fecha_pago` datetime DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `cfdi_xml` varchar(255) DEFAULT NULL,
  `cfdi_pdf` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mantenimientos`
--

CREATE TABLE `mantenimientos` (
  `id` int(11) NOT NULL,
  `vehiculo_id` int(11) NOT NULL,
  `tipo` enum('preventivo','correctivo','revision') NOT NULL,
  `descripcion` text NOT NULL,
  `fecha_programada` date NOT NULL,
  `fecha_realizado` date DEFAULT NULL,
  `costo` decimal(10,2) DEFAULT NULL,
  `proveedor` varchar(100) DEFAULT NULL,
  `estatus` enum('pendiente','en_progreso','completado','cancelado') DEFAULT 'pendiente',
  `notas` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_contables`
--

CREATE TABLE `movimientos_contables` (
  `id` int(11) NOT NULL,
  `tipo` enum('ingreso','egreso') NOT NULL,
  `factura_id` int(11) DEFAULT NULL,
  `concepto` varchar(255) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha_movimiento` date NOT NULL,
  `categoria` varchar(100) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `movimientos_contables`
--

INSERT INTO `movimientos_contables` (`id`, `tipo`, `factura_id`, `concepto`, `monto`, `fecha_movimiento`, `categoria`, `created_by`, `created_at`) VALUES
(1, 'ingreso', NULL, 'Pago en línea por envío #MENDEZ-BB356DED', 172.50, '2025-06-13', 'pagos_online', 1, '2025-06-13 14:15:50');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `status` enum('activo','pendiente','suspendido') DEFAULT 'pendiente',
  `ultimo_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `edad` int(11) DEFAULT NULL,
  `tipo_licencia` varchar(20) DEFAULT NULL,
  `num_licencia` varchar(30) DEFAULT NULL,
  `exp_vigencia` date DEFAULT NULL,
  `anos_experiencia` int(11) DEFAULT NULL,
  `capacidad_carga` decimal(5,2) DEFAULT NULL,
  `certificacion_medica` tinyint(1) DEFAULT 0,
  `conocimiento_rutas` tinyint(1) DEFAULT 0,
  `certificacion_carga` tinyint(1) DEFAULT 0,
  `antecedentes_penales` tinyint(1) DEFAULT 0,
  `profile_photo` mediumblob DEFAULT NULL,
  `id_photo` mediumblob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `repartidores`
--

INSERT INTO `repartidores` (`id`, `usuario_id`, `telefono`, `vehiculo`, `placa`, `status`, `ultimo_login`, `created_at`, `updated_at`, `edad`, `tipo_licencia`, `num_licencia`, `exp_vigencia`, `anos_experiencia`, `capacidad_carga`, `certificacion_medica`, `conocimiento_rutas`, `certificacion_carga`, `antecedentes_penales`, `profile_photo`, `id_photo`) VALUES
(1, 23, '999999999', '0', '12', 'activo', '2025-08-10 15:43:03', '2025-04-17 23:50:06', '2025-08-10 21:43:03', 21, 'E', '12', '2025-04-19', 12, 12.00, 1, 1, 1, 1, '', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `repartidores_envios`
--

CREATE TABLE `repartidores_envios` (
  `usuario_id` int(11) NOT NULL,
  `envio_id` int(11) NOT NULL,
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `repartidores_envios`
--

INSERT INTO `repartidores_envios` (`usuario_id`, `envio_id`, `fecha_asignacion`) VALUES
(23, 15, '2025-04-27 08:47:20'),
(23, 48, '2025-08-09 20:32:26'),
(23, 49, '2025-08-09 20:32:13'),
(23, 50, '2025-08-09 20:07:53'),
(23, 51, '2025-08-09 19:45:55'),
(23, 52, '2025-08-09 19:41:33'),
(23, 53, '2025-08-09 19:05:55'),
(23, 54, '2025-08-09 15:49:33'),
(23, 55, '2025-08-09 15:44:24'),
(23, 56, '2025-08-09 15:06:21'),
(23, 57, '2025-08-09 14:29:13'),
(23, 58, '2025-08-06 16:22:54'),
(23, 59, '2025-08-06 16:22:41'),
(23, 60, '2025-08-15 06:40:35'),
(23, 61, '2025-08-10 21:42:17'),
(23, 62, '2025-08-15 00:40:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`, `descripcion`, `created_at`) VALUES
(1, 'Administrador', 'Administrador del sistema', '2025-08-09 14:03:21'),
(2, 'cliente', 'Usuario que realiza env├¡os', '2025-08-09 14:03:21'),
(3, 'repartidor', 'Usuario que entrega paquetes', '2025-08-09 14:03:21'),
(4, 'cliente_repartidor', 'Cliente con rol repartidor', '2025-08-09 14:03:21');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tracking_history`
--

INSERT INTO `tracking_history` (`id`, `envio_id`, `status`, `location`, `notes`, `created_by`, `created_at`) VALUES
(1, 57, 'En tránsito', NULL, 'Asignaci├│n repartidor', 30, '2025-08-09 14:29:13'),
(2, 56, 'En tránsito', NULL, 'Asignaci├│n repartidor', 30, '2025-08-09 15:06:21'),
(3, 55, 'En tránsito', NULL, 'Asignaci├│n repartidor', 30, '2025-08-09 15:44:24'),
(4, 54, 'En tránsito', NULL, 'Asignaci├│n repartidor', 30, '2025-08-09 15:49:33'),
(5, 53, 'En tránsito', NULL, 'Asignaci├│n repartidor', 23, '2025-08-09 19:05:55'),
(6, 52, 'En tránsito', NULL, 'Asignaci├│n repartidor', 23, '2025-08-09 19:41:33'),
(7, 51, 'En tránsito', NULL, 'Asignaci├│n repartidor', 23, '2025-08-09 19:45:55'),
(8, 50, 'En tránsito', NULL, 'Asignación repartidor', 23, '2025-08-09 20:07:53'),
(9, 49, 'En tránsito', NULL, 'Asignación repartidor', 23, '2025-08-09 20:32:13'),
(10, 48, 'En tránsito', NULL, 'Asignación repartidor', 23, '2025-08-09 20:32:26'),
(11, 61, 'En tránsito', NULL, 'Asignación repartidor', 30, '2025-08-10 21:42:17'),
(12, 62, 'En tránsito', NULL, 'Asignación repartidor', 30, '2025-08-15 00:40:29'),
(13, 60, 'En tránsito', NULL, 'Asignación repartidor', 30, '2025-08-15 06:40:35');

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
  `status` enum('activo','pendiente','suspendido','eliminado') DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `google_id`, `nombre_usuario`, `email`, `password`, `created_at`, `rol_id`, `status`) VALUES
(1, '102805881195773678735', 'Darksoul 2703', 'jafethgamboabaas@gmail.com', NULL, '2025-04-08 02:39:36', 2, 'activo'),
(9, '115034569881549488883', 'Gamboa Baas Jafeth', 'jafethgamboa27@gmail.com', NULL, '2025-04-09 22:21:01', 2, 'activo'),
(19, '108144328180217974530', 'pruebaenvios', 'pruebaenvios9@gmail.com', NULL, '2025-04-10 18:51:49', 2, 'activo'),
(20, NULL, 'awaderuss', 'soidjaosjd@gmail.com', '$2y$10$zcbKqjqjjELeZ/1te1hJWeqD..NFaPToYOh1KGbVpT.Mp3zyGjPFu', '2025-04-16 05:04:49', 2, 'activo'),
(21, NULL, 'awaderuss', '123@gmail.com', '$2y$10$J8uBzGmRAZCsQRqkF6OsveuPY9AXon9mq7IS10yOasJxD3XTrHc6i', '2025-04-17 20:26:14', 2, 'activo'),
(22, NULL, 'Perico', 'ricardogamboabaas@gmail.com', '$2y$10$nDKjztpyiSSCNG76ZNhHfu.5xAy2syeb31XsvJR1iFvKTnNehfD66', '2025-04-17 20:47:43', 2, 'activo'),
(23, NULL, '1234', '1234@gmail.com', '$2y$10$QYYIKZBRDiilnaukXVb9HecdWzUiMN02yFWG9iQiaiAj5MoGvoJ42', '2025-04-17 23:50:06', 3, 'activo'),
(24, NULL, 'Josué Gamboa', 'gamboajosue541@gmail.com', '$2y$10$aTkwr4C.BKbXLhD7a3MQYuC13GIcmZe2pXZ7B0ptT7pxQC/KqU2GW', '2025-04-18 02:41:57', 2, 'activo'),
(26, NULL, 'awaderuss', 'LE21080769@merida.tecnm.mx', '$2y$10$jk48HsOZllb51Zgg33UgyuAjmJLccb7jtA/gw0NHrEzUaSeOvV5dm', '2025-04-18 08:44:09', 2, 'activo'),
(27, NULL, 'thales', 'thales995aaa@gmail.com', '$2y$10$CkiVR7JzYqXikKm.MSomqOkEVE6hb.v51cWSoqoeqWO1H4tramHq.', '2025-04-18 08:49:42', 2, 'activo'),
(30, NULL, 'DARK', '12345@gmail.com', '$2y$10$0J/hEalb3Us9RCCDsoLAB.WHObse6Zjr6n8g9eyLp4gz2p8iWPAxi', '2025-04-19 09:11:43', 1, 'activo');

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
  `capacidad_carga` decimal(10,2) NOT NULL,
  `rendimiento_combustible` decimal(5,2) DEFAULT NULL,
  `status` enum('activo','mantenimiento','inactivo') DEFAULT 'activo',
  `kilometraje` int(11) DEFAULT 0,
  `fecha_adquisicion` date NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_envios_detallados`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_envios_detallados` (
`id` int(11)
,`usuario_id` int(11)
,`name` varchar(255)
,`email` varchar(255)
,`phone` varchar(15)
,`office_phone` varchar(15)
,`origin` text
,`destination` text
,`description` text
,`value` decimal(10,2)
,`tracking_number` varchar(20)
,`delivery_date` date
,`package_type` varchar(50)
,`weight` decimal(10,2)
,`insurance` tinyint(1)
,`urgent` tinyint(1)
,`additional_notes` text
,`package_image` varchar(255)
,`estimated_cost` decimal(10,2)
,`status` varchar(50)
,`created_at` timestamp
,`lat` decimal(10,8)
,`lng` decimal(11,8)
,`estado_pago` varchar(20)
,`fecha_pago` datetime
,`cliente` varchar(50)
,`repartidor_nombre` varchar(50)
,`fecha_asignacion` timestamp
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_repartidores_activos`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_repartidores_activos` (
`id` int(11)
,`nombre_usuario` varchar(50)
,`telefono` varchar(20)
,`vehiculo` varchar(50)
,`placa` varchar(20)
,`capacidad_carga` decimal(5,2)
,`status` enum('activo','pendiente','suspendido')
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `zonas_precios`
--

CREATE TABLE `zonas_precios` (
  `id` int(11) NOT NULL,
  `origen_codigo` varchar(10) NOT NULL,
  `destino_codigo` varchar(10) NOT NULL,
  `precio_base` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_envios_detallados`
--
DROP TABLE IF EXISTS `vista_envios_detallados`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_envios_detallados`  AS SELECT `e`.`id` AS `id`, `e`.`usuario_id` AS `usuario_id`, `e`.`name` AS `name`, `e`.`email` AS `email`, `e`.`phone` AS `phone`, `e`.`office_phone` AS `office_phone`, `e`.`origin` AS `origin`, `e`.`destination` AS `destination`, `e`.`description` AS `description`, `e`.`value` AS `value`, `e`.`tracking_number` AS `tracking_number`, `e`.`delivery_date` AS `delivery_date`, `e`.`package_type` AS `package_type`, `e`.`weight` AS `weight`, `e`.`insurance` AS `insurance`, `e`.`urgent` AS `urgent`, `e`.`additional_notes` AS `additional_notes`, `e`.`package_image` AS `package_image`, `e`.`estimated_cost` AS `estimated_cost`, `e`.`status` AS `status`, `e`.`created_at` AS `created_at`, `e`.`lat` AS `lat`, `e`.`lng` AS `lng`, `e`.`estado_pago` AS `estado_pago`, `e`.`fecha_pago` AS `fecha_pago`, `u`.`nombre_usuario` AS `cliente`, `ur`.`nombre_usuario` AS `repartidor_nombre`, `re`.`fecha_asignacion` AS `fecha_asignacion` FROM (((`envios` `e` left join `usuarios` `u` on(`e`.`usuario_id` = `u`.`id`)) left join `repartidores_envios` `re` on(`e`.`id` = `re`.`envio_id`)) left join `usuarios` `ur` on(`re`.`usuario_id` = `ur`.`id`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_repartidores_activos`
--
DROP TABLE IF EXISTS `vista_repartidores_activos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_repartidores_activos`  AS SELECT `u`.`id` AS `id`, `u`.`nombre_usuario` AS `nombre_usuario`, `r`.`telefono` AS `telefono`, `r`.`vehiculo` AS `vehiculo`, `r`.`placa` AS `placa`, `r`.`capacidad_carga` AS `capacidad_carga`, `r`.`status` AS `status` FROM (`usuarios` `u` join `repartidores` `r` on(`u`.`id` = `r`.`usuario_id`)) WHERE `r`.`status` = 'activo' ORDER BY `u`.`nombre_usuario` ASC ;

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
  ADD UNIQUE KEY `uidx_envios_tracking` (`tracking_number`),
  ADD KEY `idx_envios_status_created` (`status`,`created_at`),
  ADD KEY `idx_envios_usuario_status` (`usuario_id`,`status`);

--
-- Indices de la tabla `envios_backup`
--
ALTER TABLE `envios_backup`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uidx_envios_tracking` (`tracking_number`),
  ADD KEY `idx_envios_status_created` (`status`,`created_at`),
  ADD KEY `idx_envios_usuario_status` (`usuario_id`,`status`);

--
-- Indices de la tabla `estado_envio_transiciones`
--
ALTER TABLE `estado_envio_transiciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_origen_destino` (`estado_origen`,`estado_destino`);

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
  ADD PRIMARY KEY (`usuario_id`,`envio_id`),
  ADD KEY `re_envio_fk` (`envio_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tracking_history`
--
ALTER TABLE `tracking_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_tracking_envio_created` (`envio_id`,`created_at`);

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
ALTER TABLE `zonas_precios`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `contratos`
--
ALTER TABLE `contratos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT de la tabla `envios_backup`
--
ALTER TABLE `envios_backup`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT de la tabla `estado_envio_transiciones`
--
ALTER TABLE `estado_envio_transiciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `facturas`
--
ALTER TABLE `facturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `movimientos_contables`
--
ALTER TABLE `movimientos_contables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `tracking_history`
--
ALTER TABLE `tracking_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `vehiculos`
--
ALTER TABLE `vehiculos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
