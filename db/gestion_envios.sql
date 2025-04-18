-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 17-04-2025 a las 21:49:27
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
  `value` decimal(10,2) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `envios`
--

INSERT INTO `envios` (`id`, `usuario_id`, `name`, `email`, `phone`, `office_phone`, `origin`, `destination`, `description`, `value`, `tracking_number`, `delivery_date`, `package_type`, `weight`, `insurance`, `urgent`, `additional_notes`, `package_image`, `estimated_cost`, `status`, `created_at`) VALUES
(1, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '9996369799', '9996369799', 'C55A', 'c 55A #357 x 18 y 20', 'edjfnasdionfioñdasnf', 99999999.99, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, 'Procesando', '2025-04-08 02:34:00'),
(2, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '9996369799', '9996369799', 'C55A', 'c 55A #357 x 18 y 20', 'adfvasdvasdv', 99999999.99, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, 'Procesando', '2025-04-08 02:36:00'),
(3, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '9996369799', '9996369799', 'C55A', 'c 55A #357 x 18 y 20', 'oñdkjmvaklñ{sdmv', 99999999.99, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, 'Procesando', '2025-04-08 02:41:23'),
(5, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '9996369799', '', 'C55A', 'c 55A #357 x 18 y 20', 'idhaisfhioadjsnf', 99999999.99, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, 'Procesando', '2025-04-08 02:53:43'),
(6, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '9996369799', '', 'C55A', 'c 55A #357 x 18 y 20', 'giuhiluhihiohihuiuhuihiuhui', 9999.00, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, 'Procesando', '2025-04-08 02:55:11'),
(7, 1, 'maria', 'jafethgamboabaas@gmail.com', '9996369799', '', 'sdimid', 'c 55A #357 x 18 y 20', 'naproxeno', 10.00, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, 'Procesando', '2025-04-08 03:07:00'),
(9, 1, 'xela anal', 'shjklbxcilASBNCILJAN@gmail.com', '9996369799', '', 'C55A', 'c 55A #357 x 18 y 20', 'dhsbbvknavalsv', 2000.00, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, 'Procesando', '2025-04-08 11:49:57'),
(10, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '9996369799', '', 'C55A', 'c 55A #357 x 18 y 20', 'viklasdbnviasdnvoñasd', 99999.00, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, 'Procesando', '2025-04-09 22:25:59'),
(11, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '9996369799', '', 'C55A', 'c 55A #357 x 18 y 20', 'sdasdasd', 99999999.99, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, 'Procesando', '2025-04-16 04:57:20'),
(12, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '9996369799', '', 'C55A', 'c 55A #357 x 18 y 20', 'asdfweafawe', 123123.00, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, 'Procesando', '2025-04-16 05:04:05'),
(13, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '9996369799', '', 'C55A', 'c 55A #357 x 18 y 20', 'fsdfasdfasdfasdf', 1223123.00, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, 'Procesando', '2025-04-16 05:08:08'),
(14, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '9996369799', '', 'C55A', 'c 55A #357 x 18 y 20', 'ihsdiansckoñansdc', 99999999.99, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, 'Procesando', '2025-04-16 05:10:06'),
(15, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A #357 Juan Pablo ll 97236', 'c 55A #357 x 18 y 20 juan pablo ll 97246', 'CAJA DE HERRAMIENTAS MUY PESADA', 1300.00, 'MENDEZ-CB864B29', '2025-04-17', 'paquete_mediano', 12.00, 1, 1, 'XD', '', 485.00, 'Procesando', '2025-04-16 05:48:02'),
(16, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'hjvjvhj', 78678.00, 'MENDEZ-FA2681B2', '2025-04-23', 'paquete_pequeno', 678.00, 1, 1, 'ghjghj', '', 11013.90, 'Procesando', '2025-04-16 05:52:28'),
(17, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A', 'c 55A #357 x 18 y 20', 'hjvjvhj', 78678.00, 'MENDEZ-10402858', '2025-04-23', 'paquete_pequeno', 678.00, 1, 1, 'ghjghj', '', 11013.90, 'Procesando', '2025-04-16 05:52:32');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `repartidores`
--

CREATE TABLE `repartidores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `vehiculo` varchar(50) DEFAULT NULL,
  `placa` varchar(20) DEFAULT NULL,
  `estado` enum('activo','inactivo','suspendido') DEFAULT 'activo',
  `ultimo_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `repartidores_envios`
--

CREATE TABLE `repartidores_envios` (
  `repartidor_id` int(11) NOT NULL,
  `envio_id` int(11) NOT NULL,
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `google_id`, `nombre_usuario`, `email`, `password`, `created_at`) VALUES
(1, '102805881195773678735', 'Darksoul 2703', 'jafethgamboabaas@gmail.com', NULL, '2025-04-08 02:39:36'),
(9, '115034569881549488883', 'Gamboa Baas Jafeth', 'jafethgamboa27@gmail.com', NULL, '2025-04-09 22:21:01'),
(17, NULL, 'awaderuss', 'LE21080769@merida.tecnm.mx', '$2y$10$5SlVm41KHaTF/43I/12Zuu51AHF.PlrvJ51EXWpk4YSOfk1uw7Ybe', '2025-04-09 22:17:32'),
(19, '108144328180217974530', 'pruebaenvios', 'pruebaenvios9@gmail.com', NULL, '2025-04-10 18:51:49'),
(20, NULL, 'awaderuss', 'soidjaosjd@gmail.com', '$2y$10$zcbKqjqjjELeZ/1te1hJWeqD..NFaPToYOh1KGbVpT.Mp3zyGjPFu', '2025-04-16 05:04:49');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `envios`
--
ALTER TABLE `envios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `repartidores`
--
ALTER TABLE `repartidores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `repartidores_envios`
--
ALTER TABLE `repartidores_envios`
  ADD PRIMARY KEY (`repartidor_id`,`envio_id`),
  ADD KEY `envio_id` (`envio_id`);

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
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `envios`
--
ALTER TABLE `envios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `repartidores`
--
ALTER TABLE `repartidores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tracking_history`
--
ALTER TABLE `tracking_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `repartidores_envios`
--
ALTER TABLE `repartidores_envios`
  ADD CONSTRAINT `repartidores_envios_ibfk_1` FOREIGN KEY (`repartidor_id`) REFERENCES `repartidores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `repartidores_envios_ibfk_2` FOREIGN KEY (`envio_id`) REFERENCES `envios` (`id`) ON DELETE CASCADE;

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
