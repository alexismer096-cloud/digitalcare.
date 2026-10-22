-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-05-2026 a las 16:56:04
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
-- Base de datos: `digital_care`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mantenimiento`
--

CREATE TABLE `mantenimiento` (
  `id_mantenimiento` int(11) NOT NULL,
  `tipo` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` varchar(50) DEFAULT 'Pendiente',
  `fecha_programada` date DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id_notificacion` int(11) NOT NULL,
  `mensaje` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `leida` tinyint(1) DEFAULT 0,
  `id_usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `repuestos`
--

CREATE TABLE `repuestos` (
  `id_repuesto` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `precio` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `repuestos`
--

INSERT INTO `repuestos` (`id_repuesto`, `nombre`, `descripcion`, `stock`, `precio`) VALUES
(1, 'Placa madre', 'Placa base para PC de escritorio', 20, 85.00),
(2, 'Procesador (CPU)', 'Unidad central de procesamiento', 20, 120.00),
(3, 'Memoria RAM DDR3', 'Módulo DDR3 para equipos antiguos', 20, 28.00),
(4, 'Memoria RAM DDR4', 'Módulo DDR4 3200MHz', 25, 35.00),
(5, 'Memoria RAM DDR5', 'Módulo DDR5 5600MHz', 20, 65.00),
(6, 'Disco duro HDD', 'Disco mecánico 1TB', 20, 50.00),
(7, 'Disco sólido SSD', 'SSD SATA3 480GB', 20, 65.00),
(8, 'SSD NVMe', 'SSD PCIe 4.0 500GB', 20, 85.00),
(9, 'Fuente de alimentación', 'Fuente 500W 80 Plus', 20, 40.00),
(10, 'Gabinete', 'Caja para PC ATX', 20, 55.00),
(11, 'Tarjeta gráfica (GPU)', 'Tarjeta de video para gaming', 20, 250.00),
(12, 'Tarjeta de sonido', 'Tarjeta de audio PCIe', 20, 35.00),
(13, 'Tarjeta de red', 'Tarjeta Ethernet Gigabit', 20, 20.00),
(14, 'Tarjeta WiFi', 'Tarjeta WiFi 6 PCIe', 20, 30.00),
(15, 'Tarjeta Bluetooth', 'Módulo Bluetooth 5.0', 20, 15.00),
(16, 'Tarjeta PCI', 'Tarjeta de expansión PCI', 20, 25.00),
(17, 'Tarjeta PCIe', 'Tarjeta de expansión PCIe x1', 20, 35.00),
(18, 'Controladora SATA', 'Tarjeta controladora SATA 3.0', 20, 28.00),
(19, 'Controladora RAID', 'Controladora RAID 0/1/5', 20, 85.00),
(20, 'Capturadora de video', 'Capturadora HDMI 1080p', 20, 120.00),
(21, 'Cooler de CPU', 'Ventilador para procesador', 25, 25.00),
(22, 'Ventilador gabinete', 'Ventilador 120mm', 35, 12.00),
(23, 'Disipador de calor', 'Disipador pasivo para chipset', 20, 10.00),
(24, 'Pasta térmica', 'Pasta térmica MX-4 4g', 50, 6.00),
(25, 'Refrigeración líquida', 'Kit AIO de refrigeración', 20, 120.00),
(26, 'Fan RGB', 'Ventilador 120mm RGB', 20, 18.00),
(27, 'Controlador de ventiladores', 'Hub para control de fans', 20, 25.00),
(28, 'Sensor térmico', 'Sensor de temperatura interno', 20, 8.00),
(29, 'Soporte ventilador', 'Soporte para montaje', 25, 5.00),
(30, 'Filtro antipolvo', 'Filtro magnético para gabinete', 30, 8.00),
(31, 'Cable SATA', 'Cable de datos SATA 3.0', 40, 4.00),
(32, 'Cable IDE', 'Cable IDE para discos antiguos', 20, 6.00),
(33, 'Cable de alimentación', 'Cable de poder para PC', 25, 8.00),
(34, 'Cable USB', 'Cable USB-A a USB-C 2m', 50, 10.00),
(35, 'Cable HDMI', 'Cable HDMI 2.0 3m', 40, 12.00),
(36, 'Cable VGA', 'Cable VGA para monitor', 20, 8.00),
(37, 'Cable DisplayPort', 'Cable DisplayPort 1.4', 20, 15.00),
(38, 'Cable Ethernet', 'Cable Cat6 5m', 30, 10.00),
(39, 'Adaptador USB', 'Adaptador USB-C a USB-A', 35, 8.00),
(40, 'Adaptador HDMI', 'Adaptador VGA a HDMI', 20, 10.00),
(41, 'Teclado', 'Teclado mecánico RGB', 20, 45.00),
(42, 'Mouse', 'Mouse óptico inalámbrico', 25, 25.00),
(43, 'Monitor', 'Monitor LED 24\" Full HD', 20, 180.00),
(44, 'Webcam', 'Webcam Full HD 1080p', 20, 30.00),
(45, 'Micrófono', 'Micrófono de escritorio', 20, 40.00),
(46, 'Parlantes', 'Parlantes estéreo 2.0', 20, 35.00),
(47, 'Auriculares', 'Auriculares con micrófono', 22, 50.00),
(48, 'Impresora', 'Impresora multifunción', 20, 150.00),
(49, 'Escáner', 'Escáner de documentos', 20, 80.00),
(50, 'Tableta gráfica', 'Tableta para dibujo digital', 20, 120.00),
(51, 'UPS', 'UPS de 1000VA', 20, 180.00),
(52, 'Batería CMOS', 'Batería CR2032 para placa madre', 50, 3.00),
(53, 'Fuente notebook', 'Fuente de alimentación para laptop', 20, 60.00),
(54, 'Cargador laptop', 'Cargador universal para notebook', 20, 35.00),
(55, 'Regulador de voltaje', 'Regulador 500VA', 20, 45.00),
(56, 'Power bank laptop', 'Batería externa para notebook', 20, 80.00),
(57, 'Cable power', 'Cable de poder para notebook', 30, 12.00),
(58, 'Transformador', 'Transformador 110v a 220v', 20, 30.00),
(59, 'Adaptador corriente', 'Adaptador de corriente universal', 25, 18.00),
(60, 'Switch de energía', 'Switch de corriente para PC', 20, 25.00),
(61, 'Chip BIOS', 'Chip BIOS para placa madre', 20, 20.00),
(62, 'Cristal oscilador', 'Cristal de reloj para placa', 20, 5.00),
(63, 'Capacitor', 'Capacitor electrolítico 1000µF', 100, 2.00),
(64, 'Resistencia', 'Resistencia de carbono 1/4W', 200, 1.00),
(65, 'Transistor', 'Transistor NPN BC547', 80, 1.50),
(66, 'Diodo', 'Diodo rectificador 1N4007', 120, 1.00),
(67, 'Fusible', 'Fusible de vidrio 5A', 100, 2.00),
(68, 'Regulador voltaje', 'Regulador LM7805', 50, 4.00),
(69, 'MOSFET', 'MOSFET de potencia IRF540', 30, 8.00),
(70, 'IC integrado', 'Circuito integrado 555 Timer', 40, 5.00),
(71, 'Memoria RAM DDR4 16GB', 'Módulo DDR4 3200MHz 16GB', 20, 55.00),
(72, 'Disco duro HDD 2TB', 'Disco mecánico 2TB', 20, 75.00),
(73, 'SSD SATA3 1TB', 'SSD SATA3 1TB', 20, 110.00),
(74, 'SSD NVMe 1TB', 'SSD PCIe 4.0 1TB', 20, 150.00),
(75, 'Fuente 650W', 'Fuente 650W 80 Plus Bronze', 20, 55.00),
(76, 'Gabinete RGB', 'Caja para PC ATX RGB', 20, 85.00),
(77, 'Tarjeta gráfica GTX', 'Tarjeta de video mid-range', 20, 350.00),
(78, 'Tarjeta WiFi USB', 'Adaptador WiFi USB', 25, 15.00),
(79, 'Tarjeta Capture USB', 'Capturadora USB HDMI', 20, 80.00),
(80, 'Cooler CPU RGB', 'Ventilador CPU RGB', 20, 35.00),
(81, 'Ventilador 140mm', 'Ventilador 140mm silencioso', 20, 15.00),
(82, 'Kit refrigeración líquida 240mm', 'Kit AIO 240mm', 20, 150.00),
(83, 'Cable SATA 90°', 'Cable SATA ángulo recto', 20, 5.00),
(84, 'Cable USB-C a USB-C', 'Cable USB-C 2m', 30, 12.00),
(85, 'Cable USB 3.0', 'Cable USB 3.0 1.5m', 25, 8.00),
(86, 'Cable DisplayPort 2.0', 'Cable DP 2.0 4K', 20, 20.00),
(87, 'Cable Ethernet Cat6a', 'Cable Cat6a 3m', 20, 12.00),
(88, 'Adaptador USB-C a HDMI', 'Adaptador USB-C a HDMI 4K', 20, 18.00),
(89, 'Adaptador USB-C a VGA', 'Adaptador USB-C a VGA', 20, 15.00),
(90, 'Teclado membrana', 'Teclado de membrana económico', 20, 20.00),
(91, 'Mouse gamer', 'Mouse gamer con luces', 20, 35.00),
(92, 'Monitor 27\"', 'Monitor 27\" QHD', 20, 280.00),
(93, 'Webcam 4K', 'Webcam 4K Ultra HD', 20, 60.00),
(94, 'Micrófono USB', 'Micrófono USB para streaming', 20, 55.00),
(95, 'Parlantes Bluetooth', 'Parlantes estéreo Bluetooth', 20, 45.00),
(96, 'Auriculares Bluetooth', 'Auriculares inalámbricos', 20, 65.00),
(97, 'Impresora láser', 'Impresora láser monocromática', 20, 200.00),
(98, 'Escáner portátil', 'Escáner portátil de documentos', 20, 100.00),
(99, 'Tableta gráfica pequeña', 'Tableta gráfica compacta', 20, 50.00),
(100, 'UPS 1500VA', 'UPS de 1500VA con display', 20, 250.00),
(101, 'Batería externa 20000mAh', 'Power bank 20000mAh', 20, 35.00),
(102, 'Cargador rápido 65W', 'Cargador USB-C PD 65W', 20, 30.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes`
--

CREATE TABLE `solicitudes` (
  `id_solicitud` int(11) NOT NULL,
  `tipo_equipo` varchar(100) NOT NULL,
  `problema` text NOT NULL,
  `estado` varchar(50) DEFAULT 'Pendiente',
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tecnicos`
--

CREATE TABLE `tecnicos` (
  `id_tecnico` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `trabajos`
--

CREATE TABLE `trabajos` (
  `id_trabajo` int(11) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` varchar(50) DEFAULT 'En proceso',
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `costo` decimal(10,2) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `id_tecnico` int(11) DEFAULT NULL,
  `id_solicitud` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `trabajo_repuesto`
--

CREATE TABLE `trabajo_repuesto` (
  `id_trabajo` int(11) NOT NULL,
  `id_repuesto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `email`, `password`, `telefono`, `fecha_registro`) VALUES
(1, 'alexis', 'alexis@gmail.com', '$2y$10$22sPigLtsIPv8PdpwmeDv.XNtxE2MJhk3ceNIp8ja2qz/amcqgWNi', NULL, '2026-04-28 11:44:42'),
(2, 'alexis', 'almercado@escuelasproa.edu.ar', '$2y$10$n4.TSaV0te3XKEc49AqmzuRsVxKcCn8R0HYXjY1XH3q4oVIjDEt0u', NULL, '2026-04-28 11:46:37'),
(3, 'alexis', 'alexismer096@gmail.com', '$2y$10$kvhmAJFnmxlLHR4saj3uqe6K6aqFPYOhSU56FygYditOAaTlQwN6S', NULL, '2026-04-28 11:52:28'),
(4, 'dario', 'dario@gmail.com', '$2y$10$8E34axcW/oillk1Qx6PO6.fzdC9XZxSTGM5rOkQt.FkKKd1H3TZFm', NULL, '2026-04-28 12:42:40'),
(5, 'alexis', 'aleis@gmail.com', '$2y$10$otlFPFwpapdtkEpnmsin8e07pes45T48gPAc7xZbvuxT/HvM4Q.ES', NULL, '2026-05-06 14:48:10'),
(6, 'PECHU@GMAIL.COM', 'PECHU@GMAIL.COM', '$2y$10$.YvluefYnaIYUpiTiJZJYupnVC2MoaaneD.h.Dc9/TXhXwkPobgeu', NULL, '2026-05-13 16:31:31'),
(7, 'alexis', 'almercado111@escuelasproa.edu.ar', '$2y$10$7tPr61miWhpUTTjDPQ3hze/9ccKZlaxIEscg8VY4L.d1r3QW/M2z.', NULL, '2026-05-15 14:29:38');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `mantenimiento`
--
ALTER TABLE `mantenimiento`
  ADD PRIMARY KEY (`id_mantenimiento`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `repuestos`
--
ALTER TABLE `repuestos`
  ADD PRIMARY KEY (`id_repuesto`);

--
-- Indices de la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  ADD PRIMARY KEY (`id_solicitud`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `tecnicos`
--
ALTER TABLE `tecnicos`
  ADD PRIMARY KEY (`id_tecnico`);

--
-- Indices de la tabla `trabajos`
--
ALTER TABLE `trabajos`
  ADD PRIMARY KEY (`id_trabajo`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_tecnico` (`id_tecnico`),
  ADD KEY `id_solicitud` (`id_solicitud`);

--
-- Indices de la tabla `trabajo_repuesto`
--
ALTER TABLE `trabajo_repuesto`
  ADD PRIMARY KEY (`id_trabajo`,`id_repuesto`),
  ADD KEY `id_repuesto` (`id_repuesto`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `mantenimiento`
--
ALTER TABLE `mantenimiento`
  MODIFY `id_mantenimiento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id_notificacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `repuestos`
--
ALTER TABLE `repuestos`
  MODIFY `id_repuesto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT de la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  MODIFY `id_solicitud` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tecnicos`
--
ALTER TABLE `tecnicos`
  MODIFY `id_tecnico` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `trabajos`
--
ALTER TABLE `trabajos`
  MODIFY `id_trabajo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `mantenimiento`
--
ALTER TABLE `mantenimiento`
  ADD CONSTRAINT `mantenimiento_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  ADD CONSTRAINT `solicitudes_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `trabajos`
--
ALTER TABLE `trabajos`
  ADD CONSTRAINT `trabajos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `trabajos_ibfk_2` FOREIGN KEY (`id_tecnico`) REFERENCES `tecnicos` (`id_tecnico`),
  ADD CONSTRAINT `trabajos_ibfk_3` FOREIGN KEY (`id_solicitud`) REFERENCES `solicitudes` (`id_solicitud`);

--
-- Filtros para la tabla `trabajo_repuesto`
--
ALTER TABLE `trabajo_repuesto`
  ADD CONSTRAINT `trabajo_repuesto_ibfk_1` FOREIGN KEY (`id_trabajo`) REFERENCES `trabajos` (`id_trabajo`),
  ADD CONSTRAINT `trabajo_repuesto_ibfk_2` FOREIGN KEY (`id_repuesto`) REFERENCES `repuestos` (`id_repuesto`);

--
-- Tablas complementarias para carrito y compras
--

CREATE TABLE IF NOT EXISTS `carrito` (
  `id_carrito` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_repuesto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `fecha_agregado` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_carrito`),
  UNIQUE KEY `uq_carrito_usuario_repuesto` (`id_usuario`,`id_repuesto`),
  KEY `id_repuesto` (`id_repuesto`),
  CONSTRAINT `carrito_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  CONSTRAINT `carrito_ibfk_2` FOREIGN KEY (`id_repuesto`) REFERENCES `repuestos` (`id_repuesto`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `compras` (
  `id_compra` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `estado` varchar(50) DEFAULT 'Pendiente',
  `fecha_compra` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_compra`),
  KEY `idx_usuario_compra` (`id_usuario`),
  CONSTRAINT `compras_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `detalles_compras` (
  `id_detalle` int(11) NOT NULL AUTO_INCREMENT,
  `id_compra` int(11) NOT NULL,
  `id_repuesto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_detalle`),
  KEY `idx_compra_detalle` (`id_compra`),
  KEY `id_repuesto` (`id_repuesto`),
  CONSTRAINT `detalles_compras_ibfk_1` FOREIGN KEY (`id_compra`) REFERENCES `compras` (`id_compra`) ON DELETE CASCADE,
  CONSTRAINT `detalles_compras_ibfk_2` FOREIGN KEY (`id_repuesto`) REFERENCES `repuestos` (`id_repuesto`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
