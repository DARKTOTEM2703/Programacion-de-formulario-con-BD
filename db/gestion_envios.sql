-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 08-04-2025 a las 05:00:11
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `envios`
--

INSERT INTO `envios` (`id`, `usuario_id`, `name`, `email`, `phone`, `office_phone`, `origin`, `destination`, `description`, `value`, `created_at`) VALUES
(1, 7, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '9996369799', '9996369799', 'C55A', 'c 55A #357 x 18 y 20', 'edjfnasdionfioñdasnf', 99999999.99, '2025-04-08 02:34:00'),
(2, 7, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '9996369799', '9996369799', 'C55A', 'c 55A #357 x 18 y 20', 'adfvasdvasdv', 99999999.99, '2025-04-08 02:36:00'),
(3, 7, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '9996369799', '9996369799', 'C55A', 'c 55A #357 x 18 y 20', 'oñdkjmvaklñ{sdmv', 99999999.99, '2025-04-08 02:41:23'),
(4, 0, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '9996369799', '9996369799', 'C55A', 'c 55A #357 x 18 y 20', 'oñvnasdklñvmao{svmp{asd,v', 99999999.99, '2025-04-08 02:48:02'),
(5, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '9996369799', '', 'C55A', 'c 55A #357 x 18 y 20', 'idhaisfhioadjsnf', 99999999.99, '2025-04-08 02:53:43'),
(6, 8, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '9996369799', '', 'C55A', 'c 55A #357 x 18 y 20', 'giuhiluhihiohihuiuhuihiuhui', 9999.00, '2025-04-08 02:55:11');

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
(7, NULL, 'DARK', 'jqadkjfbiasdnfoasd@xn--aodfnasdonf-9db.com', '$2y$10$5IVy1izbRvhZwK7VB9JlBOO9XxbkvumoRShonZKYIT/rfibQXTwP6', '2025-04-08 02:46:20'),
(8, '115034569881549488883', 'Gamboa Baas Jafeth', 'jafethgamboa27@gmail.com', NULL, '2025-04-08 02:54:53');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
