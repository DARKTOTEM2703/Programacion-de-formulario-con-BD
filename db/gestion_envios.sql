-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 08-04-2025 a las 01:22:43
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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_envios`
--

CREATE TABLE `solicitudes_envios` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `celular` varchar(15) NOT NULL,
  `telefono_oficina` varchar(15) DEFAULT NULL,
  `direccion_origen` varchar(255) NOT NULL,
  `direccion_destino` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `valor_aproximado` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre_usuario`, `email`, `password`, `created_at`) VALUES
(1, 'isis can', 'nnnjnjj@gmail.com', '$2y$10$lWeAcOhIyX7WYYDuDCPQ4.MS9FoTi/lXAwidFxlEzpdjnFPTTzvxW', '2025-04-05 19:26:18'),
(2, 'jafeth', 'okanfdoknfojms@gmail.com', '$2y$10$h20bON2A5iLYMq9l.S1u8.K3fjTh9V98H5kka5/Q7SMLz2h3atj9W', '2025-04-07 21:42:03');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `solicitudes_envios`
--
ALTER TABLE `solicitudes_envios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `solicitudes_envios`
--
ALTER TABLE `solicitudes_envios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `solicitudes_envios`
--
ALTER TABLE `solicitudes_envios`
  ADD CONSTRAINT `solicitudes_envios_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
