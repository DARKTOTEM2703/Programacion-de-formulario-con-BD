-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 21-09-2025 a las 23:24:34
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

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_asignar_repartidor` (IN `p_envio_id` INT, IN `p_repartidor_usuario_id` INT, IN `p_actor` INT, OUT `p_ok` TINYINT, OUT `p_msg` VARCHAR(120))   proc: BEGIN
  DECLARE v_status VARCHAR(40);
  SET p_ok=0; SET p_msg=NULL;

  SELECT status INTO v_status FROM envios WHERE id=p_envio_id FOR UPDATE;
  IF v_status IS NULL THEN SET p_msg='No existe envío'; LEAVE proc; END IF;

  INSERT INTO repartidores_envios(usuario_id,envio_id,fecha_asignacion)
  VALUES(p_repartidor_usuario_id,p_envio_id,NOW())
  ON DUPLICATE KEY UPDATE fecha_asignacion=VALUES(fecha_asignacion);

  IF v_status='Procesando' THEN
    UPDATE envios SET status='En tránsito', updated_at=NOW() WHERE id=p_envio_id;
    INSERT INTO tracking_history(envio_id,status,location,notes,created_by,created_at)
    VALUES(p_envio_id,'En tránsito',NULL,'Asignación repartidor',p_actor,NOW());
  END IF;

  SET p_ok=1; SET p_msg='OK';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_cambiar_estado_envio` (IN `p_envio_id` INT, IN `p_nuevo_estado` VARCHAR(40), IN `p_usuario_id` INT, IN `p_lat` DECIMAL(10,8), IN `p_lng` DECIMAL(11,8), IN `p_notes` TEXT, OUT `p_ok` TINYINT, OUT `p_msg` VARCHAR(150))   proc: BEGIN
  DECLARE v_actual VARCHAR(40);
  DECLARE v_valid INT DEFAULT 0;
  DECLARE v_loc VARCHAR(100);

  SET p_ok=0; SET p_msg=NULL; SET v_loc=NULL;

  SELECT status INTO v_actual FROM envios WHERE id=p_envio_id FOR UPDATE;
  IF v_actual IS NULL THEN SET p_msg='No existe envío'; LEAVE proc; END IF;

  SELECT COUNT(*) INTO v_valid
  FROM estado_envio_transiciones
  WHERE estado_origen=v_actual AND estado_destino=p_nuevo_estado;

  IF v_valid=0 AND p_nuevo_estado NOT IN ('Intento fallido') THEN
    SET p_msg=CONCAT('Transición inválida ',v_actual,' -> ',p_nuevo_estado);
    LEAVE proc;
  END IF;

  IF p_lat IS NOT NULL AND p_lng IS NOT NULL THEN
    SET v_loc = CONCAT('Lat: ',p_lat,', Lng: ',p_lng);
  END IF;

  UPDATE envios SET status=p_nuevo_estado, updated_at=NOW() WHERE id=p_envio_id;

  INSERT INTO tracking_history(envio_id,status,location,notes,created_by,created_at)
  VALUES(p_envio_id,p_nuevo_estado,v_loc,p_notes,p_usuario_id,NOW());

  SET p_ok=1; SET p_msg='OK';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_cargar_camion` (IN `p_envio_id` INT, IN `p_usuario_bodega` INT, OUT `p_ok` TINYINT, OUT `p_msg` VARCHAR(120))   proc: BEGIN
  DECLARE v_status VARCHAR(40);
  SET p_ok=0; SET p_msg=NULL;

  SELECT status INTO v_status FROM envios WHERE id=p_envio_id FOR UPDATE;
  IF v_status IS NULL THEN SET p_msg='No existe envío'; LEAVE proc; END IF;
  IF v_status NOT IN ('Recibido bodega','En tránsito') THEN
    SET p_msg='No se puede cargar en este estado';
    LEAVE proc;
  END IF;

  UPDATE envios SET status='Cargado camión', updated_at=NOW() WHERE id=p_envio_id;
  INSERT INTO tracking_history(envio_id,status,location,notes,created_by,created_at)
  VALUES(p_envio_id,'Cargado camión','Bodega','Cargado a unidad',p_usuario_bodega,NOW());

  SET p_ok=1; SET p_msg='OK';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_crear_notificacion` (IN `p_tipo` VARCHAR(50), IN `p_usuario_id` INT, IN `p_titulo` VARCHAR(255), IN `p_mensaje` TEXT, IN `p_enlace` VARCHAR(255), OUT `p_notificacion_id` INT)   BEGIN
    INSERT INTO notificaciones (
        tipo, usuario_id, titulo, mensaje, enlace, leida, created_at
    ) VALUES (
        p_tipo, p_usuario_id, p_titulo, p_mensaje, p_enlace, 0, NOW()
    );
    
    SET p_notificacion_id = LAST_INSERT_ID();
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_estadisticas_notificaciones` (IN `p_usuario_id` INT)   BEGIN
    SELECT * FROM vista_resumen_notificaciones
    WHERE usuario_id = p_usuario_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_generar_factura_envio` (IN `p_envio_id` INT, IN `p_actor` INT, OUT `p_ok` TINYINT, OUT `p_msg` VARCHAR(140))   proc: BEGIN
  DECLARE v_exists INT DEFAULT 0;
  DECLARE v_monto DECIMAL(10,2) DEFAULT 0;
  SET p_ok=0; SET p_msg=NULL;

  SELECT COUNT(*), COALESCE(estimated_cost,0) INTO v_exists, v_monto
  FROM envios WHERE id=p_envio_id;

  IF v_exists=0 THEN SET p_msg='Envío no existe'; LEAVE proc; END IF;

  SELECT COUNT(*) INTO v_exists FROM facturas WHERE envio_id=p_envio_id;
  IF v_exists>0 THEN SET p_msg='Ya existe factura'; LEAVE proc; END IF;

  INSERT INTO facturas(envio_id,numero_factura,fecha_emision,fecha_vencimiento,monto,status)
  VALUES(p_envio_id, CONCAT('FAC-',DATE_FORMAT(NOW(),'%Y%m%d'),'-',LPAD(p_envio_id,6,'0')), NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), v_monto,'pendiente');

  SET p_ok=1; SET p_msg='OK';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_generar_qr_bodeguista` (IN `p_bodeguista_id` INT, IN `p_regenerar` BOOLEAN, OUT `p_ok` TINYINT, OUT `p_msg` VARCHAR(200), OUT `p_qr_data` JSON)   proc: BEGIN
    DECLARE v_rol_id INT;
    DECLARE v_qr_token VARCHAR(100);
    DECLARE v_zona_codigo VARCHAR(20);
    
    SET p_ok = 0;
    SET p_msg = '';
    SET p_qr_data = NULL;

    -- Verificar que el usuario sea bodeguista
    SELECT rol_id INTO v_rol_id FROM usuarios WHERE id = p_bodeguista_id;
    
    IF v_rol_id IS NULL THEN
        SET p_msg = 'Usuario no encontrado';
        LEAVE proc;
    END IF;
    
    IF v_rol_id != 4 AND v_rol_id != 8 THEN
        SET p_msg = 'El usuario no es bodeguista';
        LEAVE proc;
    END IF;
    
    -- Verificar si ya tiene perfil de bodeguista
    SELECT qr_token, codigo_zona INTO v_qr_token, v_zona_codigo
    FROM perfiles_bodeguistas 
    WHERE usuario_id = p_bodeguista_id;
    
    IF v_qr_token IS NULL OR p_regenerar = TRUE THEN
        -- Crear nuevo token QR
        SET v_qr_token = CONCAT('BOD-', UPPER(SUBSTRING(MD5(CONCAT(p_bodeguista_id, NOW())), 1, 16)));
        
        IF v_zona_codigo IS NULL THEN
            -- Crear nuevo código de zona
            SET v_zona_codigo = CONCAT('ZONA-', LPAD(p_bodeguista_id, 3, '0'));
        END IF;
        
        -- Insertar o actualizar perfil
        INSERT INTO perfiles_bodeguistas (
            usuario_id, codigo_zona, qr_token, token_generado, token_expira, status
        ) VALUES (
            p_bodeguista_id, v_zona_codigo, v_qr_token, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 'activo'
        ) ON DUPLICATE KEY UPDATE 
            qr_token = VALUES(qr_token),
            token_generado = VALUES(token_generado),
            token_expira = VALUES(token_expira),
            status = 'activo';
        
        SET p_msg = 'QR generado correctamente';
    ELSE
        SET p_msg = 'QR existente recuperado';
    END IF;
    
    -- Obtener datos actualizados
    SELECT JSON_OBJECT(
        'token', qr_token,
        'generado', token_generado,
        'expira', token_expira,
        'zona', codigo_zona
    ) INTO p_qr_data
    FROM perfiles_bodeguistas
    WHERE usuario_id = p_bodeguista_id;
    
    SET p_ok = 1;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_intake_envio` (IN `p_tracking` VARCHAR(40), IN `p_usuario_bodega` INT, OUT `p_ok` TINYINT, OUT `p_msg` VARCHAR(120))   proc: BEGIN
  DECLARE v_id INT; DECLARE v_status VARCHAR(40);
  SET p_ok=0; SET p_msg=NULL;
  SELECT id,status INTO v_id,v_status FROM envios WHERE tracking_number=p_tracking FOR UPDATE;
  IF v_id IS NULL THEN SET p_msg='No encontrado'; LEAVE proc; END IF;
  IF v_status NOT IN ('Procesando','Recibido bodega') THEN SET p_msg='Estado no válido'; LEAVE proc; END IF;

  UPDATE envios SET status='Recibido bodega', updated_at=NOW() WHERE id=v_id;
  INSERT INTO tracking_history(envio_id,status,location,notes,created_by,created_at)
  VALUES(v_id,'Recibido bodega','Bodega','Ingreso a bodega',p_usuario_bodega,NOW());

  SET p_ok=1; SET p_msg='OK';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_marcar_notificacion_leida` (IN `p_notificacion_id` INT, IN `p_usuario_id` INT, OUT `p_ok` BOOLEAN)   BEGIN
    DECLARE affected_rows INT;
    
    UPDATE notificaciones 
    SET leida = 1 
    WHERE id = p_notificacion_id AND usuario_id = p_usuario_id;
    
    SET affected_rows = ROW_COUNT();
    SET p_ok = (affected_rows > 0);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_marcar_todas_leidas` (IN `p_usuario_id` INT, OUT `p_count` INT)   BEGIN
    UPDATE notificaciones 
    SET leida = 1
    WHERE usuario_id = p_usuario_id AND leida = 0;
    
    SET p_count = ROW_COUNT();
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_obtener_detalle_envio` (IN `p_envio_id` INT, IN `p_rol_id` INT, IN `p_usuario_id` INT)   BEGIN
    CASE p_rol_id
        -- Administrador: Acceso completo a todos los envíos
        WHEN 1 THEN 
            SELECT e.*, 
                   u.nombre_usuario as cliente_nombre, 
                   u.email as cliente_email,
                   ur.nombre_usuario as repartidor_nombre,
                   r.telefono as repartidor_telefono,
                   r.vehiculo as repartidor_vehiculo,
                   r.placa as repartidor_placa,
                   r.capacidad_carga as repartidor_capacidad,
                   re.fecha_asignacion
            FROM envios e
            LEFT JOIN usuarios u ON e.usuario_id = u.id
            LEFT JOIN repartidores_envios re ON e.id = re.envio_id
            LEFT JOIN usuarios ur ON re.usuario_id = ur.id
            LEFT JOIN repartidores r ON ur.id = r.usuario_id
            WHERE e.id = p_envio_id;
        
        -- Cliente: Solo puede ver sus propios envíos
        WHEN 2 THEN 
            SELECT e.*, 
                   ur.nombre_usuario as repartidor_nombre,
                   re.fecha_asignacion
            FROM envios e
            LEFT JOIN repartidores_envios re ON e.id = re.envio_id
            LEFT JOIN usuarios ur ON re.usuario_id = ur.id
            WHERE e.id = p_envio_id AND e.usuario_id = p_usuario_id;
        
        -- Repartidor: Solo puede ver envíos asignados a él
        WHEN 3 THEN 
            SELECT e.*, 
                   u.nombre_usuario as cliente_nombre, 
                   u.email as cliente_email,
                   re.fecha_asignacion
            FROM envios e
            LEFT JOIN usuarios u ON e.usuario_id = u.id
            LEFT JOIN repartidores_envios re ON e.id = re.envio_id
            WHERE e.id = p_envio_id AND re.usuario_id = p_usuario_id;
            
        -- Otro rol (por defecto, no acceso)
        ELSE
            SELECT NULL AS id;
    END CASE;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_obtener_estadisticas_bodega` (IN `p_bodeguista_id` INT)   BEGIN
    -- Estadísticas generales de la bodega
    SELECT 
        COUNT(DISTINCT e.id) AS total_envios,
        SUM(CASE WHEN e.status = 'Procesando' THEN 1 ELSE 0 END) AS procesando,
        SUM(CASE WHEN e.status = 'Recibido bodega' THEN 1 ELSE 0 END) AS en_bodega,
        SUM(CASE WHEN e.status = 'Cargado camión' THEN 1 ELSE 0 END) AS cargados,
        SUM(CASE WHEN e.status = 'En tránsito' THEN 1 ELSE 0 END) AS en_transito,
        SUM(CASE WHEN e.status = 'En ruta' THEN 1 ELSE 0 END) AS en_ruta,
        SUM(CASE WHEN e.status = 'Entregado' THEN 1 ELSE 0 END) AS entregados,
        SUM(CASE WHEN e.urgent = 1 THEN 1 ELSE 0 END) AS urgentes,
        SUM(CASE WHEN DATE(e.created_at) = CURDATE() THEN 1 ELSE 0 END) AS nuevos_hoy,
        (SELECT COUNT(DISTINCT re.usuario_id) 
         FROM repartidores_envios re 
         JOIN repartidores r ON re.usuario_id = r.usuario_id 
         WHERE r.status = 'activo') AS repartidores_activos,
        (SELECT COUNT(DISTINCT vq.envio_id) 
         FROM validaciones_qr vq 
         WHERE vq.bodeguista_id = p_bodeguista_id 
           AND DATE(vq.timestamp) = CURDATE()) AS validaciones_hoy
    FROM envios e;
    
    -- Envíos procesados por este bodeguista hoy
    SELECT 
        e.id,
        e.tracking_number,
        e.name AS cliente,
        e.status,
        vq.tipo_validacion,
        vq.timestamp,
        u.nombre_usuario AS repartidor
    FROM validaciones_qr vq
    JOIN envios e ON vq.envio_id = e.id
    JOIN usuarios u ON vq.repartidor_id = u.id
    WHERE vq.bodeguista_id = p_bodeguista_id
      AND DATE(vq.timestamp) = CURDATE()
    ORDER BY vq.timestamp DESC;
    
    -- Envíos pendientes por procesar
    SELECT 
        e.id,
        e.tracking_number,
        e.name AS cliente,
        e.status,
        e.urgent,
        e.package_type,
        e.weight,
        u.nombre_usuario AS repartidor_asignado
    FROM envios e
    LEFT JOIN repartidores_envios re ON e.id = re.envio_id
    LEFT JOIN usuarios u ON re.usuario_id = u.id
    WHERE e.status IN ('Procesando', 'Recibido bodega')
    ORDER BY e.urgent DESC, e.created_at ASC
    LIMIT 20;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_obtener_notificaciones` (IN `p_usuario_id` INT, IN `p_limit` INT, IN `p_solo_pendientes` BOOLEAN)   BEGIN
    IF p_solo_pendientes THEN
        SELECT * FROM vista_notificaciones 
        WHERE usuario_id = p_usuario_id AND leida = 0
        ORDER BY created_at DESC
        LIMIT p_limit;
    ELSE
        SELECT * FROM vista_notificaciones 
        WHERE usuario_id = p_usuario_id
        ORDER BY created_at DESC
        LIMIT p_limit;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_obtener_repartidores_activos` ()   BEGIN
    SELECT * FROM vista_repartidores_activos;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_obtener_tracking_historial` (IN `p_envio_id` INT)   BEGIN
    SELECT th.*, 
           u.nombre_usuario
    FROM tracking_history th
    LEFT JOIN usuarios u ON th.created_by = u.id
    WHERE th.envio_id = p_envio_id
    ORDER BY th.created_at DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_registrar_intento_entrega` (IN `p_envio_id` INT, IN `p_exito` TINYINT, IN `p_usuario_id` INT, IN `p_lat` DECIMAL(10,8), IN `p_lng` DECIMAL(11,8), IN `p_notes` TEXT, OUT `p_ok` TINYINT, OUT `p_msg` VARCHAR(150))   proc: BEGIN
  IF p_exito=1 THEN
    CALL sp_cambiar_estado_envio(p_envio_id,'Entregado',p_usuario_id,p_lat,p_lng,p_notes,@ok,@m);
  ELSE
    CALL sp_cambiar_estado_envio(p_envio_id,'Intento fallido',p_usuario_id,p_lat,p_lng,p_notes,@ok,@m);
  END IF;
  SELECT @ok,@m INTO p_ok,p_msg;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_registrar_tracking_actualizacion` (IN `p_envio_id` INT, IN `p_status` VARCHAR(50), IN `p_location` VARCHAR(255), IN `p_notes` TEXT, IN `p_usuario_id` INT)   BEGIN
    -- Actualizar estado del envío
    UPDATE envios 
    SET status = p_status,
        updated_at = NOW()
    WHERE id = p_envio_id;
    
    -- Registrar en historial
    INSERT INTO tracking_history(envio_id, status, location, notes, created_by, created_at)
    VALUES (p_envio_id, p_status, p_location, p_notes, p_usuario_id, NOW());
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_toggle_cliente_status` (IN `p_usuario_id` INT, IN `p_nuevo_status` VARCHAR(20), OUT `p_ok` TINYINT)   proc: BEGIN
  UPDATE usuarios SET status=p_nuevo_status WHERE id=p_usuario_id AND rol_id=2;
  SET p_ok = (ROW_COUNT()>0);
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_validar_escaneo_bodeguista` (IN `p_qr_token` VARCHAR(100), IN `p_tracking_number` VARCHAR(40), IN `p_repartidor_id` INT, IN `p_tipo_validacion` ENUM('recepcion','carga','entrega','devolucion'), OUT `p_ok` TINYINT, OUT `p_msg` VARCHAR(200), OUT `p_data` JSON)   proc: BEGIN
    DECLARE v_bodeguista_id INT DEFAULT NULL;
    DECLARE v_zona_codigo VARCHAR(20) DEFAULT NULL;
    DECLARE v_envio_id INT DEFAULT NULL;
    DECLARE v_envio_status VARCHAR(40) DEFAULT NULL;
    DECLARE v_asignado TINYINT DEFAULT 0;
    
    SET p_ok = 0;
    SET p_msg = 'Error desconocido';
    SET p_data = NULL;

    -- 1. Validar QR del bodeguista
    SELECT pb.usuario_id, pb.codigo_zona
    INTO v_bodeguista_id, v_zona_codigo
    FROM perfiles_bodeguistas pb
    WHERE pb.qr_token = p_qr_token
      AND pb.status = 'activo'
      AND (pb.token_expira IS NULL OR pb.token_expira > NOW());
    
    IF v_bodeguista_id IS NULL THEN
        SET p_msg = 'QR de bodeguista inválido o expirado';
        LEAVE proc;
    END IF;

    -- 2. Validar envío
    SELECT e.id, e.status
    INTO v_envio_id, v_envio_status
    FROM envios e
    WHERE e.tracking_number = p_tracking_number;
    
    IF v_envio_id IS NULL THEN
        SET p_msg = 'Tracking number no encontrado';
        LEAVE proc;
    END IF;

    -- 3. Verificar asignación al repartidor (excepto para carga inicial)
    IF p_tipo_validacion != 'carga' THEN
        SELECT COUNT(*) INTO v_asignado
        FROM repartidores_envios
        WHERE envio_id = v_envio_id AND usuario_id = p_repartidor_id;
        
        IF v_asignado = 0 THEN
            SET p_msg = 'El envío no está asignado a este repartidor';
            LEAVE proc;
        END IF;
    END IF;

    -- 4. Procesar según tipo de validación
    CASE p_tipo_validacion
        WHEN 'recepcion' THEN
            -- Repartidor recibe paquete del bodeguista
            IF v_envio_status NOT IN ('Cargado camión', 'Recibido bodega') THEN
                SET p_msg = CONCAT('Estado actual (', v_envio_status, ') no permite recepción');
                LEAVE proc;
            END IF;
            
            -- Actualizar a En tránsito
            UPDATE envios 
            SET status = 'En tránsito', 
                updated_at = NOW() 
            WHERE id = v_envio_id;
            
            -- Registrar en historial
            INSERT INTO tracking_history(envio_id, status, location, notes, created_by)
            VALUES(v_envio_id, 'En tránsito', v_zona_codigo, 
                   CONCAT('Paquete recibido desde bodega: ', v_zona_codigo), 
                   p_repartidor_id);
                   
        WHEN 'carga' THEN
            -- Bodeguista carga paquete a repartidor
            IF v_envio_status != 'Recibido bodega' THEN
                SET p_msg = CONCAT('Estado actual (', v_envio_status, ') no permite carga');
                LEAVE proc;
            END IF;
            
            -- Asignar el envío al repartidor si no está asignado
            INSERT IGNORE INTO repartidores_envios (usuario_id, envio_id, fecha_asignacion)
            VALUES (p_repartidor_id, v_envio_id, NOW());
            
            -- Actualizar a Cargado camión
            UPDATE envios 
            SET status = 'Cargado camión', 
                updated_at = NOW() 
            WHERE id = v_envio_id;
            
            -- Registrar en historial
            INSERT INTO tracking_history(envio_id, status, location, notes, created_by)
            VALUES(v_envio_id, 'Cargado camión', v_zona_codigo, 
                   CONCAT('Cargado en vehículo del repartidor desde zona: ', v_zona_codigo), 
                   v_bodeguista_id);
                   
        WHEN 'entrega' THEN
            -- No aplica directamente a bodeguista-repartidor
            SET p_msg = 'Tipo de validación no aplicable entre bodeguista y repartidor';
            LEAVE proc;
            
        WHEN 'devolucion' THEN
            -- Repartidor devuelve paquete al bodeguista
            IF v_envio_status NOT IN ('En tránsito', 'En ruta', 'Intento fallido') THEN
                SET p_msg = CONCAT('Estado actual (', v_envio_status, ') no permite devolución');
                LEAVE proc;
            END IF;
            
            -- Actualizar a Recibido bodega
            UPDATE envios 
            SET status = 'Recibido bodega', 
                updated_at = NOW() 
            WHERE id = v_envio_id;
            
            -- Registrar en historial
            INSERT INTO tracking_history(envio_id, status, location, notes, created_by)
            VALUES(v_envio_id, 'Recibido bodega', v_zona_codigo, 
                   CONCAT('Devuelto a bodega: ', v_zona_codigo), 
                   v_bodeguista_id);
    END CASE;
    
    -- Obtener datos del envío para la respuesta
    SELECT @nombre_cliente := name, @destino := destination, 
           @telefono := phone, @tipo_paquete := package_type,
           @peso := weight, @urgente := urgent
    FROM envios WHERE id = v_envio_id;
    
    -- Construir respuesta
    SET p_data = JSON_OBJECT(
        'envio_id', v_envio_id,
        'tracking', p_tracking_number,
        'cliente', @nombre_cliente,
        'destino', @destino,
        'telefono', @telefono,
        'tipo_paquete', @tipo_paquete,
        'peso', @peso,
        'urgente', IF(@urgente = 1, TRUE, FALSE),
        'nuevo_status', (SELECT status FROM envios WHERE id = v_envio_id),
        'bodeguista_id', v_bodeguista_id,
        'zona_codigo', v_zona_codigo,
        'timestamp', NOW()
    );
    
    SET p_ok = 1;
    SET p_msg = CONCAT('Validación ', p_tipo_validacion, ' completada exitosamente');
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
  `fecha_pago` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `pin_seguro` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `envios`
--

INSERT INTO `envios` (`id`, `usuario_id`, `name`, `email`, `phone`, `office_phone`, `origin`, `destination`, `description`, `value`, `tracking_number`, `delivery_date`, `package_type`, `weight`, `insurance`, `urgent`, `additional_notes`, `package_image`, `estimated_cost`, `status`, `created_at`, `lat`, `lng`, `estado_pago`, `fecha_pago`, `updated_at`, `pin_seguro`) VALUES
(15, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A #357 Juan Pablo ll 97236', 'c 55A #357 x 18 y 20 juan pablo ll 97246', 'CAJA DE HERRAMIENTAS MUY PESADA', NULL, 'MENDEZ-CB864B29', '2025-04-17', 'paquete_mediano', 12.00, 1, 1, 'XD', '', 485.00, 'En ruta', '2025-04-16 05:48:02', 20.96737000, -89.59258600, 'pendiente', NULL, '2025-09-21 20:49:59', NULL),
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
(65, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 101, Santa coloma de gramanet, 08924, barcelona, cataluña, España', 'XDDD', 150.00, 'MENDEZ-9A038AFB', '2025-08-25', 'paquete_pequeno', 10.00, 1, 1, 'HOLA', 'uploads/packages/pkg_20250821_023644_68a66a1c49b32.jpg', 16618.37, 'En tránsito', '2025-08-21 00:36:44', NULL, NULL, 'pendiente', NULL, '2025-08-23 07:50:04', NULL),
(66, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 103, Santa coloma de gramanet, 08924, barcelona, cataluña, España', '123', 123.00, 'MENDEZ-CF18C337', '2025-08-29', 'paquete_mediano', 23.00, 1, 1, '123', 'uploads/packages/pkg_20250821_025354_68a66e229fea1.jpg', 16757.31, 'Recibido bodega', '2025-08-21 00:53:54', NULL, NULL, 'pendiente', NULL, '2025-08-23 04:36:33', NULL),
(67, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 103, Santa coloma de gramanet, 08924, barcelona, cataluña, España', 'dasd', 12.00, 'MENDEZ-A2C0E30E', '2025-08-31', 'paquete_pequeno', 12.00, 0, 0, 'asd', 'uploads/packages/pkg_20250821_025811_68a66f2310cbc.jpg', 13299.19, 'En tránsito', '2025-08-21 00:58:11', NULL, NULL, 'pagado', '2025-08-20 19:00:01', '2025-08-23 04:36:33', NULL),
(98, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 103, Santa coloma de gramanet, 08924, barcelona, cataluña, España', 'asdd', 12.00, 'MENDEZ-2638CDE9', '2025-08-30', 'paquete_pequeno', 12.00, 1, 1, 'adad', 'uploads/packages/pkg_20250823_072203_68a94ffb61e6c.jpg', 13299.19, 'Entregado', '2025-08-23 05:22:03', NULL, NULL, 'pagado', '2025-08-22 23:23:04', '2025-08-23 05:39:35', 330582),
(99, 1, 'Jafeth Daniel Gamboa Baas', 'jafethgamboabaas@gmail.com', '999-636-9799', '', 'C55A 18, juan pablo ll, 97246, Mérida, Yucatán, México', 'Av.pallaresa 103, Santa coloma de gramanet, 08924, barcelona, cataluña, España', 'xojfasoid', 2345.00, 'MENDEZ-31FE6304', '2025-09-25', 'paquete_mediano', 12.00, 0, 0, 'zsdasdas', '', 13317.19, 'Procesando', '2025-09-14 08:44:34', NULL, NULL, 'pagado', '2025-09-14 02:46:11', '2025-09-14 08:46:11', NULL);

--
-- Disparadores `envios`
--
DELIMITER $$
CREATE TRIGGER `tr_envio_status_change` AFTER UPDATE ON `envios` FOR EACH ROW BEGIN
    DECLARE v_notif_id INT;
    DECLARE v_ok BOOLEAN;
    
    -- Solo disparar si el status realmente cambió
    IF OLD.status != NEW.status THEN
        -- Usar el SP para crear la notificación
        CALL sp_notificar_cambio_estado(
            NEW.id, 
            NEW.status,
            NULL, -- mensaje personalizado null (usará el predeterminado)
            v_ok,
            v_notif_id
        );
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_envios_update` BEFORE UPDATE ON `envios` FOR EACH ROW BEGIN
  SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

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

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`id`, `usuario_id`, `tipo`, `titulo`, `mensaje`, `enlace`, `leida`, `created_at`) VALUES
(1, 1, 'envio_status', '📦 Actualización del envío #MENDEZ-CB864B29', 'Tu envío #MENDEZ-CB864B29 cambió de \"En tránsito\" a \"En ruta\"', '/php/tracking.php?tracking=MENDEZ-CB864B29', 1, '2025-09-21 20:27:54'),
(2, 1, 'envio_status', '📦 Actualización del envío #MENDEZ-CB864B29', 'Tu envío #MENDEZ-CB864B29 cambió de \"En ruta\" a \"Procesando\"', '/php/tracking.php?tracking=MENDEZ-CB864B29', 1, '2025-09-21 20:31:21'),
(3, 1, 'envio_status', '📦 Actualización del envío #MENDEZ-CB864B29', 'Tu envío #MENDEZ-CB864B29 cambió de \"Procesando\" a \"si\"', '/php/tracking.php?tracking=MENDEZ-CB864B29', 1, '2025-09-21 20:31:54'),
(4, 1, 'envio_status', '📦 Actualización del envío #MENDEZ-CB864B29', 'Tu envío #MENDEZ-CB864B29 cambió de \"si\" a \"En ruta\"', '/php/tracking.php?tracking=MENDEZ-CB864B29', 1, '2025-09-21 20:32:13'),
(5, 1, 'envio_status', '📦 Actualización del envío #MENDEZ-CB864B29', 'Tu envío cambió a: no', '/php/tracking.php?tracking=MENDEZ-CB864B29', 1, '2025-09-21 20:49:51'),
(6, 1, 'envio_status', '📦 Actualización del envío #MENDEZ-CB864B29', 'Tu envío está muy cerca. El repartidor está en la zona de entrega.', '/php/tracking.php?tracking=MENDEZ-CB864B29', 1, '2025-09-21 20:49:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `perfiles_bodeguistas`
--

CREATE TABLE `perfiles_bodeguistas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nombre_zona` varchar(100) DEFAULT NULL,
  `codigo_zona` varchar(20) DEFAULT NULL,
  `tipo_zona` enum('entrada','salida','almacén','mixta') DEFAULT 'mixta',
  `qr_token` varchar(100) DEFAULT NULL,
  `token_generado` timestamp NULL DEFAULT NULL,
  `token_expira` timestamp NULL DEFAULT NULL,
  `status` enum('activo','inactivo','suspendido') DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `id` int(11) NOT NULL,
  `modulo` varchar(50) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `permisos`
--

INSERT INTO `permisos` (`id`, `modulo`, `accion`, `descripcion`, `created_at`) VALUES
(1, 'envios', 'ver', 'Ver lista de envíos', '2025-08-20 07:59:52'),
(2, 'envios', 'crear', 'Crear nuevos envíos', '2025-08-20 07:59:52'),
(3, 'envios', 'editar', 'Modificar envíos existentes', '2025-08-20 07:59:52'),
(4, 'envios', 'eliminar', 'Eliminar envíos', '2025-08-20 07:59:52'),
(5, 'envios', 'asignar_repartidor', 'Asignar repartidores a envíos', '2025-08-20 07:59:52'),
(6, 'envios', 'cambiar_estado', 'Cambiar estado de envíos', '2025-08-20 07:59:52'),
(7, 'envios', 'ver_todos', 'Ver todos los envíos del sistema', '2025-08-20 07:59:52'),
(8, 'bodega', 'recibir_paquetes', 'Recibir paquetes en bodega', '2025-08-20 07:59:52'),
(9, 'bodega', 'escanear', 'Escanear códigos QR/barras', '2025-08-20 07:59:52'),
(10, 'bodega', 'cargar_camion', 'Marcar paquetes como cargados', '2025-08-20 07:59:52'),
(11, 'bodega', 'inventario', 'Ver inventario de bodega', '2025-08-20 07:59:52'),
(12, 'repartidores', 'ver', 'Ver lista de repartidores', '2025-08-20 07:59:52'),
(13, 'repartidores', 'aprobar', 'Aprobar nuevos repartidores', '2025-08-20 07:59:52'),
(14, 'repartidores', 'suspender', 'Suspender repartidores', '2025-08-20 07:59:52'),
(15, 'repartidores', 'asignar_envios', 'Asignar envíos a repartidores', '2025-08-20 07:59:52'),
(16, 'clientes', 'ver', 'Ver lista de clientes', '2025-08-20 07:59:52'),
(17, 'clientes', 'activar', 'Activar/desactivar clientes', '2025-08-20 07:59:52'),
(18, 'clientes', 'soporte', 'Brindar soporte a clientes', '2025-08-20 07:59:52'),
(19, 'facturas', 'ver', 'Ver facturas', '2025-08-20 07:59:52'),
(20, 'facturas', 'crear', 'Generar facturas', '2025-08-20 07:59:52'),
(21, 'facturas', 'editar', 'Modificar facturas', '2025-08-20 07:59:52'),
(22, 'facturas', 'eliminar', 'Eliminar facturas', '2025-08-20 07:59:52'),
(23, 'facturas', 'enviar_email', 'Enviar facturas por email', '2025-08-20 07:59:52'),
(24, 'finanzas', 'ver_reportes', 'Ver reportes financieros', '2025-08-20 07:59:52'),
(25, 'finanzas', 'registrar_pagos', 'Registrar pagos', '2025-08-20 07:59:52'),
(26, 'finanzas', 'ver_movimientos', 'Ver movimientos contables', '2025-08-20 07:59:52'),
(27, 'sistema', 'ver_logs', 'Ver logs del sistema', '2025-08-20 07:59:52'),
(28, 'sistema', 'configuracion', 'Modificar configuración', '2025-08-20 07:59:52'),
(29, 'sistema', 'usuarios', 'Gestionar usuarios del sistema', '2025-08-20 07:59:52'),
(30, 'sistema', 'roles', 'Gestionar roles y permisos', '2025-08-20 07:59:52'),
(31, 'pwa', 'escanear', 'Usar escáner móvil', '2025-08-20 07:59:52'),
(32, 'pwa', 'tracking', 'Ver tracking de envíos asignados', '2025-08-20 07:59:52'),
(33, 'pwa', 'actualizar_estado', 'Actualizar estado desde móvil', '2025-08-20 07:59:52');

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
(1, 23, '999999999', '0', '12', 'activo', '2025-09-14 02:48:53', '2025-04-17 23:50:06', '2025-09-14 08:48:53', 21, 'E', '12', '2025-04-19', 12, 12.00, 1, 1, 1, 1, '', ''),
(2, 32, '31231233', NULL, NULL, 'pendiente', NULL, '2025-09-14 08:50:58', '2025-09-14 08:50:58', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, NULL, NULL);

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
(23, 16, '2025-08-22 22:24:49'),
(23, 19, '2025-08-22 23:48:28'),
(23, 20, '2025-08-23 03:59:28'),
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
(23, 62, '2025-08-15 00:40:29'),
(23, 63, '2025-08-20 10:09:23'),
(23, 65, '2025-08-23 07:50:04'),
(23, 67, '2025-08-22 21:25:13'),
(23, 98, '2025-08-23 05:26:12');

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
(2, 'cliente', 'Usuario que realiza envios', '2025-08-09 14:03:21'),
(3, 'repartidor', 'Usuario que entrega paquetes', '2025-08-09 14:03:21'),
(4, 'bodeguista', 'Personal de bodega', '2025-08-09 14:03:21'),
(5, 'soporte', 'Atención al cliente y resolución de incidencias', '2025-08-20 07:59:52'),
(6, 'supervisor', 'Supervisor de operaciones', '2025-08-20 07:59:52'),
(7, 'contador', 'Manejo de facturación y finanzas', '2025-08-20 07:59:52'),
(8, 'super_admin', 'Super usuario programador - acceso total', '2025-08-20 07:59:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles_permisos`
--

CREATE TABLE `roles_permisos` (
  `id` int(11) NOT NULL,
  `rol_id` int(11) NOT NULL,
  `permiso_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles_permisos`
--

INSERT INTO `roles_permisos` (`id`, `rol_id`, `permiso_id`, `created_at`) VALUES
(1, 8, 10, '2025-08-20 07:59:52'),
(2, 8, 9, '2025-08-20 07:59:52'),
(3, 8, 11, '2025-08-20 07:59:52'),
(4, 8, 8, '2025-08-20 07:59:52'),
(5, 8, 17, '2025-08-20 07:59:52'),
(6, 8, 18, '2025-08-20 07:59:52'),
(7, 8, 16, '2025-08-20 07:59:52'),
(8, 8, 5, '2025-08-20 07:59:52'),
(9, 8, 6, '2025-08-20 07:59:52'),
(10, 8, 2, '2025-08-20 07:59:52'),
(11, 8, 3, '2025-08-20 07:59:52'),
(12, 8, 4, '2025-08-20 07:59:52'),
(13, 8, 1, '2025-08-20 07:59:52'),
(14, 8, 7, '2025-08-20 07:59:52'),
(15, 8, 20, '2025-08-20 07:59:52'),
(16, 8, 21, '2025-08-20 07:59:52'),
(17, 8, 22, '2025-08-20 07:59:52'),
(18, 8, 23, '2025-08-20 07:59:52'),
(19, 8, 19, '2025-08-20 07:59:52'),
(20, 8, 25, '2025-08-20 07:59:52'),
(21, 8, 26, '2025-08-20 07:59:52'),
(22, 8, 24, '2025-08-20 07:59:52'),
(23, 8, 33, '2025-08-20 07:59:52'),
(24, 8, 31, '2025-08-20 07:59:52'),
(25, 8, 32, '2025-08-20 07:59:52'),
(26, 8, 13, '2025-08-20 07:59:52'),
(27, 8, 15, '2025-08-20 07:59:52'),
(28, 8, 14, '2025-08-20 07:59:52'),
(29, 8, 12, '2025-08-20 07:59:52'),
(30, 8, 28, '2025-08-20 07:59:52'),
(31, 8, 30, '2025-08-20 07:59:52'),
(32, 8, 29, '2025-08-20 07:59:52'),
(33, 8, 27, '2025-08-20 07:59:52'),
(64, 1, 10, '2025-08-20 07:59:52'),
(65, 1, 9, '2025-08-20 07:59:52'),
(66, 1, 11, '2025-08-20 07:59:52'),
(67, 1, 8, '2025-08-20 07:59:52'),
(68, 1, 17, '2025-08-20 07:59:52'),
(69, 1, 18, '2025-08-20 07:59:52'),
(70, 1, 16, '2025-08-20 07:59:52'),
(71, 1, 5, '2025-08-20 07:59:52'),
(72, 1, 6, '2025-08-20 07:59:52'),
(73, 1, 2, '2025-08-20 07:59:52'),
(74, 1, 3, '2025-08-20 07:59:52'),
(75, 1, 4, '2025-08-20 07:59:52'),
(76, 1, 1, '2025-08-20 07:59:52'),
(77, 1, 7, '2025-08-20 07:59:52'),
(78, 1, 20, '2025-08-20 07:59:52'),
(79, 1, 21, '2025-08-20 07:59:52'),
(80, 1, 22, '2025-08-20 07:59:52'),
(81, 1, 23, '2025-08-20 07:59:52'),
(82, 1, 19, '2025-08-20 07:59:52'),
(83, 1, 25, '2025-08-20 07:59:52'),
(84, 1, 26, '2025-08-20 07:59:52'),
(85, 1, 24, '2025-08-20 07:59:52'),
(86, 1, 33, '2025-08-20 07:59:52'),
(87, 1, 31, '2025-08-20 07:59:52'),
(88, 1, 32, '2025-08-20 07:59:52'),
(89, 1, 13, '2025-08-20 07:59:52'),
(90, 1, 15, '2025-08-20 07:59:52'),
(91, 1, 14, '2025-08-20 07:59:52'),
(92, 1, 12, '2025-08-20 07:59:52'),
(93, 1, 29, '2025-08-20 07:59:52'),
(94, 1, 27, '2025-08-20 07:59:52'),
(95, 2, 2, '2025-08-20 07:59:52'),
(96, 2, 1, '2025-08-20 07:59:52'),
(97, 2, 32, '2025-08-20 07:59:52'),
(98, 3, 6, '2025-08-20 07:59:52'),
(99, 3, 1, '2025-08-20 07:59:52'),
(100, 3, 33, '2025-08-20 07:59:52'),
(101, 3, 31, '2025-08-20 07:59:52'),
(102, 3, 32, '2025-08-20 07:59:52'),
(105, 4, 10, '2025-08-20 07:59:52'),
(106, 4, 9, '2025-08-20 07:59:52'),
(107, 4, 11, '2025-08-20 07:59:52'),
(108, 4, 8, '2025-08-20 07:59:52'),
(109, 4, 6, '2025-08-20 07:59:52'),
(110, 4, 1, '2025-08-20 07:59:52'),
(111, 4, 31, '2025-08-20 07:59:52'),
(112, 5, 17, '2025-08-20 07:59:52'),
(113, 5, 18, '2025-08-20 07:59:52'),
(114, 5, 16, '2025-08-20 07:59:52'),
(115, 5, 6, '2025-08-20 07:59:52'),
(116, 5, 1, '2025-08-20 07:59:52'),
(117, 5, 7, '2025-08-20 07:59:52'),
(119, 6, 10, '2025-08-20 07:59:52'),
(120, 6, 9, '2025-08-20 07:59:52'),
(121, 6, 11, '2025-08-20 07:59:52'),
(122, 6, 8, '2025-08-20 07:59:52'),
(123, 6, 17, '2025-08-20 07:59:52'),
(124, 6, 18, '2025-08-20 07:59:52'),
(125, 6, 16, '2025-08-20 07:59:52'),
(126, 6, 5, '2025-08-20 07:59:52'),
(127, 6, 6, '2025-08-20 07:59:52'),
(128, 6, 2, '2025-08-20 07:59:52'),
(129, 6, 3, '2025-08-20 07:59:52'),
(130, 6, 1, '2025-08-20 07:59:52'),
(131, 6, 7, '2025-08-20 07:59:52'),
(132, 6, 13, '2025-08-20 07:59:52'),
(133, 6, 15, '2025-08-20 07:59:52'),
(134, 6, 14, '2025-08-20 07:59:52'),
(135, 6, 12, '2025-08-20 07:59:52'),
(150, 7, 7, '2025-08-20 07:59:52'),
(151, 7, 20, '2025-08-20 07:59:52'),
(152, 7, 21, '2025-08-20 07:59:52'),
(153, 7, 22, '2025-08-20 07:59:52'),
(154, 7, 23, '2025-08-20 07:59:52'),
(155, 7, 19, '2025-08-20 07:59:52'),
(156, 7, 25, '2025-08-20 07:59:52'),
(157, 7, 26, '2025-08-20 07:59:52'),
(158, 7, 24, '2025-08-20 07:59:52');

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
(13, 60, 'En tránsito', NULL, 'Asignación repartidor', 30, '2025-08-15 06:40:35'),
(14, 63, 'En tránsito', NULL, 'Asignación repartidor', 30, '2025-08-20 10:09:23'),
(15, 15, 'Recibido bodega', 'Bodega', 'Ingreso a bodega', 31, '2025-08-20 21:14:23'),
(16, 16, 'Recibido bodega', 'Bodega', 'Ingreso a bodega', 31, '2025-08-20 21:14:45'),
(17, 15, 'Recibido bodega', 'Bodega', 'Ingreso a bodega', 31, '2025-08-22 06:41:01'),
(18, 15, 'Recibido bodega', 'Bodega', 'Ingreso a bodega', 31, '2025-08-22 06:41:09'),
(19, 17, 'Recibido bodega', 'Bodega', 'Ingreso a bodega', 31, '2025-08-22 06:41:17'),
(20, 67, 'En tránsito', NULL, 'Asignación repartidor', 30, '2025-08-22 21:25:13'),
(21, 65, 'En tránsito', NULL, 'Asignación repartidor', 30, '2025-08-23 07:50:04'),
(22, 15, 'En ruta', 'Actualización de prueba', 'Estado cambiado de \'En tránsito\' a \'En ruta\' (prueba automática)', 1, '2025-09-21 20:27:54'),
(23, 15, 'En ruta', 'Actualización de prueba', 'Estado cambiado de \'En ruta\' a \'En ruta\' (prueba automática)', 1, '2025-09-21 20:29:08'),
(24, 15, 'En ruta', 'Actualización de prueba', 'Estado cambiado de \'si\' a \'En ruta\' (prueba automática)', 1, '2025-09-21 20:32:13'),
(25, 15, 'En ruta', 'Actualización de prueba', 'Estado cambiado de \'no\' a \'En ruta\' (prueba automática)', 1, '2025-09-21 20:49:59');

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
(22, NULL, 'Perico', 'ricardogamboabaas@gmail.com', '$2y$10$nDKjztpyiSSCNG76ZNhHfu.5xAy2syeb31XsvJR1iFvKTnNehfD66', '2025-04-17 20:47:43', 2, 'activo'),
(23, NULL, ' xela', '1234@gmail.com', '$2y$10$QYYIKZBRDiilnaukXVb9HecdWzUiMN02yFWG9iQiaiAj5MoGvoJ42', '2025-04-17 23:50:06', 3, 'activo'),
(24, NULL, 'Josué Gamboa', 'gamboajosue541@gmail.com', '$2y$10$aTkwr4C.BKbXLhD7a3MQYuC13GIcmZe2pXZ7B0ptT7pxQC/KqU2GW', '2025-04-18 02:41:57', 2, 'activo'),
(26, NULL, 'awaderuss', 'LE21080769@merida.tecnm.mx', '$2y$10$jk48HsOZllb51Zgg33UgyuAjmJLccb7jtA/gw0NHrEzUaSeOvV5dm', '2025-04-18 08:44:09', 2, 'activo'),
(27, NULL, 'thales', 'thales995aaa@gmail.com', '$2y$10$CkiVR7JzYqXikKm.MSomqOkEVE6hb.v51cWSoqoeqWO1H4tramHq.', '2025-04-18 08:49:42', 2, 'activo'),
(30, NULL, 'DARK', '12345@gmail.com', '$2y$10$0J/hEalb3Us9RCCDsoLAB.WHObse6Zjr6n8g9eyLp4gz2p8iWPAxi', '2025-04-19 09:11:43', 1, 'activo'),
(31, NULL, 'bodega1', '123@gmail.com', '$2y$10$Td.1oT0YyrI5G4ypvVO2mOIsRXruS.XPrC1HouNKj6/I/gkojTU6u', '2025-08-20 08:31:29', 4, 'activo'),
(32, NULL, 'samantha', '78123178263@gmail.com', '$2y$10$eLQfLCe9sYCz.0JuBMDCf.oqlAyYgtRInHaNJ2zlbs0XzKJxQYHKG', '2025-09-14 08:50:58', 3, 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `validaciones_qr`
--

CREATE TABLE `validaciones_qr` (
  `id` int(11) NOT NULL,
  `envio_id` int(11) NOT NULL,
  `bodeguista_id` int(11) NOT NULL,
  `repartidor_id` int(11) NOT NULL,
  `tipo_validacion` enum('recepcion','carga','entrega','devolucion') NOT NULL,
  `qr_escaneado` varchar(100) NOT NULL,
  `notas` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Estructura Stand-in para la vista `vista_actividad_bodeguistas`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_actividad_bodeguistas` (
`bodeguista_id` int(11)
,`bodeguista_nombre` varchar(50)
,`total_validaciones` bigint(21)
,`recepciones` decimal(22,0)
,`cargas` decimal(22,0)
,`entregas` decimal(22,0)
,`devoluciones` decimal(22,0)
,`ultima_actividad` timestamp
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_bodega_envios_completa`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_bodega_envios_completa` (
`id` int(11)
,`tracking_number` varchar(20)
,`cliente_nombre` varchar(255)
,`destination` text
,`status` varchar(50)
,`created_at` timestamp
,`urgent` tinyint(1)
,`package_type` varchar(50)
,`weight` decimal(10,2)
,`repartidor_id` int(11)
,`repartidor_nombre` varchar(50)
,`vehiculo` varchar(50)
,`placa` varchar(20)
,`fecha_asignacion` timestamp
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_bodega_pendientes`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_bodega_pendientes` (
`id` int(11)
,`tracking_number` varchar(20)
,`name` varchar(255)
,`destination` text
,`status` varchar(50)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_dashboard_estadisticas`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_dashboard_estadisticas` (
`total_envios` bigint(21)
,`procesando` decimal(22,0)
,`en_bodega` decimal(22,0)
,`cargados` decimal(22,0)
,`en_transito` decimal(22,0)
,`en_ruta` decimal(22,0)
,`entregados` decimal(22,0)
,`cancelados` decimal(22,0)
,`urgentes` decimal(22,0)
,`creados_hoy` decimal(22,0)
,`repartidores_activos` bigint(21)
);

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
,`cliente_email` varchar(100)
,`repartidor_usuario_id` int(11)
,`repartidor_nombre` varchar(50)
,`fecha_asignacion` timestamp
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_envios_validados_qr`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_envios_validados_qr` (
`envio_id` int(11)
,`tracking_number` varchar(20)
,`status` varchar(50)
,`tipo_validacion` enum('recepcion','carga','entrega','devolucion')
,`timestamp` timestamp
,`bodeguista` varchar(50)
,`repartidor` varchar(50)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_notificaciones`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_notificaciones` (
`id` int(11)
,`usuario_id` int(11)
,`tipo` varchar(50)
,`titulo` varchar(100)
,`mensaje` text
,`enlace` varchar(255)
,`leida` tinyint(1)
,`created_at` timestamp
,`nombre_usuario` varchar(50)
,`email` varchar(100)
,`tracking_number` varchar(20)
,`envio_status` varchar(50)
,`tracking_code` varchar(255)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_notificaciones_pendientes`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_notificaciones_pendientes` (
`id` int(11)
,`usuario_id` int(11)
,`tipo` varchar(50)
,`titulo` varchar(100)
,`mensaje` text
,`enlace` varchar(255)
,`leida` tinyint(1)
,`created_at` timestamp
,`nombre_usuario` varchar(50)
,`email` varchar(100)
,`tracking_number` varchar(20)
,`envio_status` varchar(50)
,`tracking_code` varchar(255)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_repartidores_activos`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_repartidores_activos` (
`id` int(11)
,`nombre_usuario` varchar(50)
,`email` varchar(100)
,`telefono` varchar(20)
,`vehiculo` varchar(50)
,`placa` varchar(20)
,`capacidad_carga` decimal(5,2)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_repartidor_envios_pendientes`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_repartidor_envios_pendientes` (
`repartidor_id` int(11)
,`envio_id` int(11)
,`tracking_number` varchar(20)
,`cliente_nombre` varchar(255)
,`cliente_telefono` varchar(15)
,`destination` text
,`package_type` varchar(50)
,`weight` decimal(10,2)
,`urgent` tinyint(1)
,`status` varchar(50)
,`created_at` timestamp
,`fecha_asignacion` timestamp
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_resumen_notificaciones`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_resumen_notificaciones` (
`usuario_id` int(11)
,`total` bigint(21)
,`pendientes` decimal(22,0)
,`leidas` decimal(22,0)
,`ultima_fecha` timestamp
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
-- Estructura para la vista `vista_actividad_bodeguistas`
--
DROP TABLE IF EXISTS `vista_actividad_bodeguistas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_actividad_bodeguistas`  AS SELECT `u`.`id` AS `bodeguista_id`, `u`.`nombre_usuario` AS `bodeguista_nombre`, count(distinct `vq`.`id`) AS `total_validaciones`, sum(case when `vq`.`tipo_validacion` = 'recepcion' then 1 else 0 end) AS `recepciones`, sum(case when `vq`.`tipo_validacion` = 'carga' then 1 else 0 end) AS `cargas`, sum(case when `vq`.`tipo_validacion` = 'entrega' then 1 else 0 end) AS `entregas`, sum(case when `vq`.`tipo_validacion` = 'devolucion' then 1 else 0 end) AS `devoluciones`, max(`vq`.`timestamp`) AS `ultima_actividad` FROM (`usuarios` `u` left join `validaciones_qr` `vq` on(`u`.`id` = `vq`.`bodeguista_id`)) WHERE `u`.`rol_id` = 4 GROUP BY `u`.`id`, `u`.`nombre_usuario` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_bodega_envios_completa`
--
DROP TABLE IF EXISTS `vista_bodega_envios_completa`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_bodega_envios_completa`  AS SELECT `e`.`id` AS `id`, `e`.`tracking_number` AS `tracking_number`, `e`.`name` AS `cliente_nombre`, `e`.`destination` AS `destination`, `e`.`status` AS `status`, `e`.`created_at` AS `created_at`, `e`.`urgent` AS `urgent`, `e`.`package_type` AS `package_type`, `e`.`weight` AS `weight`, coalesce(`u`.`id`,0) AS `repartidor_id`, coalesce(`u`.`nombre_usuario`,'') AS `repartidor_nombre`, coalesce(`r`.`vehiculo`,'') AS `vehiculo`, coalesce(`r`.`placa`,'') AS `placa`, `re`.`fecha_asignacion` AS `fecha_asignacion` FROM (((`envios` `e` left join `repartidores_envios` `re` on(`e`.`id` = `re`.`envio_id`)) left join `usuarios` `u` on(`re`.`usuario_id` = `u`.`id`)) left join `repartidores` `r` on(`u`.`id` = `r`.`usuario_id`)) WHERE `e`.`status` in ('Procesando','Recibido bodega','Cargado camión') ORDER BY `e`.`urgent` DESC, `e`.`created_at` ASC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_bodega_pendientes`
--
DROP TABLE IF EXISTS `vista_bodega_pendientes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_bodega_pendientes`  AS SELECT `e`.`id` AS `id`, `e`.`tracking_number` AS `tracking_number`, `e`.`name` AS `name`, `e`.`destination` AS `destination`, `e`.`status` AS `status`, `e`.`created_at` AS `created_at` FROM `envios` AS `e` WHERE `e`.`status` in ('Procesando','Recibido bodega') ORDER BY `e`.`created_at` ASC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_dashboard_estadisticas`
--
DROP TABLE IF EXISTS `vista_dashboard_estadisticas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_dashboard_estadisticas`  AS SELECT count(distinct `e`.`id`) AS `total_envios`, sum(case when `e`.`status` = 'Procesando' then 1 else 0 end) AS `procesando`, sum(case when `e`.`status` = 'Recibido bodega' then 1 else 0 end) AS `en_bodega`, sum(case when `e`.`status` = 'Cargado camión' then 1 else 0 end) AS `cargados`, sum(case when `e`.`status` = 'En tránsito' then 1 else 0 end) AS `en_transito`, sum(case when `e`.`status` = 'En ruta' then 1 else 0 end) AS `en_ruta`, sum(case when `e`.`status` = 'Entregado' then 1 else 0 end) AS `entregados`, sum(case when `e`.`status` = 'Cancelado' then 1 else 0 end) AS `cancelados`, sum(case when `e`.`urgent` = 1 then 1 else 0 end) AS `urgentes`, sum(case when cast(`e`.`created_at` as date) = curdate() then 1 else 0 end) AS `creados_hoy`, count(distinct `re`.`usuario_id`) AS `repartidores_activos` FROM (`envios` `e` left join `repartidores_envios` `re` on(`e`.`id` = `re`.`envio_id`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_envios_detallados`
--
DROP TABLE IF EXISTS `vista_envios_detallados`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_envios_detallados`  AS SELECT `e`.`id` AS `id`, `e`.`usuario_id` AS `usuario_id`, `e`.`name` AS `name`, `e`.`email` AS `email`, `e`.`phone` AS `phone`, `e`.`office_phone` AS `office_phone`, `e`.`origin` AS `origin`, `e`.`destination` AS `destination`, `e`.`description` AS `description`, `e`.`value` AS `value`, `e`.`tracking_number` AS `tracking_number`, `e`.`delivery_date` AS `delivery_date`, `e`.`package_type` AS `package_type`, `e`.`weight` AS `weight`, `e`.`insurance` AS `insurance`, `e`.`urgent` AS `urgent`, `e`.`additional_notes` AS `additional_notes`, `e`.`package_image` AS `package_image`, `e`.`estimated_cost` AS `estimated_cost`, `e`.`status` AS `status`, `e`.`created_at` AS `created_at`, `e`.`lat` AS `lat`, `e`.`lng` AS `lng`, `e`.`estado_pago` AS `estado_pago`, `e`.`fecha_pago` AS `fecha_pago`, `uc`.`nombre_usuario` AS `cliente`, `uc`.`email` AS `cliente_email`, `re`.`usuario_id` AS `repartidor_usuario_id`, `ur`.`nombre_usuario` AS `repartidor_nombre`, `re`.`fecha_asignacion` AS `fecha_asignacion` FROM (((`envios` `e` left join `usuarios` `uc` on(`uc`.`id` = `e`.`usuario_id`)) left join (select `re1`.`envio_id` AS `envio_id`,`re1`.`usuario_id` AS `usuario_id`,`re1`.`fecha_asignacion` AS `fecha_asignacion` from (`repartidores_envios` `re1` join (select `repartidores_envios`.`envio_id` AS `envio_id`,max(`repartidores_envios`.`fecha_asignacion`) AS `maxf` from `repartidores_envios` group by `repartidores_envios`.`envio_id`) `x` on(`x`.`envio_id` = `re1`.`envio_id` and `x`.`maxf` = `re1`.`fecha_asignacion`))) `re` on(`re`.`envio_id` = `e`.`id`)) left join `usuarios` `ur` on(`ur`.`id` = `re`.`usuario_id`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_envios_validados_qr`
--
DROP TABLE IF EXISTS `vista_envios_validados_qr`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_envios_validados_qr`  AS SELECT `e`.`id` AS `envio_id`, `e`.`tracking_number` AS `tracking_number`, `e`.`status` AS `status`, `vq`.`tipo_validacion` AS `tipo_validacion`, `vq`.`timestamp` AS `timestamp`, `ub`.`nombre_usuario` AS `bodeguista`, `ur`.`nombre_usuario` AS `repartidor` FROM (((`validaciones_qr` `vq` join `envios` `e` on(`vq`.`envio_id` = `e`.`id`)) join `usuarios` `ub` on(`vq`.`bodeguista_id` = `ub`.`id`)) join `usuarios` `ur` on(`vq`.`repartidor_id` = `ur`.`id`)) ORDER BY `vq`.`timestamp` DESC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_notificaciones`
--
DROP TABLE IF EXISTS `vista_notificaciones`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_notificaciones`  AS SELECT `n`.`id` AS `id`, `n`.`usuario_id` AS `usuario_id`, `n`.`tipo` AS `tipo`, `n`.`titulo` AS `titulo`, `n`.`mensaje` AS `mensaje`, `n`.`enlace` AS `enlace`, `n`.`leida` AS `leida`, `n`.`created_at` AS `created_at`, `u`.`nombre_usuario` AS `nombre_usuario`, `u`.`email` AS `email`, `e`.`tracking_number` AS `tracking_number`, `e`.`status` AS `envio_status`, CASE WHEN `n`.`tipo` = 'envio_status' THEN substring_index(substring_index(`n`.`enlace`,'tracking=',-1),'&',1) ELSE NULL END AS `tracking_code` FROM ((`notificaciones` `n` left join `usuarios` `u` on(`n`.`usuario_id` = `u`.`id`)) left join `envios` `e` on(case when `n`.`tipo` = 'envio_status' then `e`.`tracking_number` = substring_index(substring_index(`n`.`enlace`,'tracking=',-1),'&',1) else 0 end)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_notificaciones_pendientes`
--
DROP TABLE IF EXISTS `vista_notificaciones_pendientes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_notificaciones_pendientes`  AS SELECT `vista_notificaciones`.`id` AS `id`, `vista_notificaciones`.`usuario_id` AS `usuario_id`, `vista_notificaciones`.`tipo` AS `tipo`, `vista_notificaciones`.`titulo` AS `titulo`, `vista_notificaciones`.`mensaje` AS `mensaje`, `vista_notificaciones`.`enlace` AS `enlace`, `vista_notificaciones`.`leida` AS `leida`, `vista_notificaciones`.`created_at` AS `created_at`, `vista_notificaciones`.`nombre_usuario` AS `nombre_usuario`, `vista_notificaciones`.`email` AS `email`, `vista_notificaciones`.`tracking_number` AS `tracking_number`, `vista_notificaciones`.`envio_status` AS `envio_status`, `vista_notificaciones`.`tracking_code` AS `tracking_code` FROM `vista_notificaciones` WHERE `vista_notificaciones`.`leida` = 0 ORDER BY `vista_notificaciones`.`created_at` DESC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_repartidores_activos`
--
DROP TABLE IF EXISTS `vista_repartidores_activos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_repartidores_activos`  AS SELECT `u`.`id` AS `id`, `u`.`nombre_usuario` AS `nombre_usuario`, `u`.`email` AS `email`, `r`.`telefono` AS `telefono`, `r`.`vehiculo` AS `vehiculo`, `r`.`placa` AS `placa`, `r`.`capacidad_carga` AS `capacidad_carga` FROM (`usuarios` `u` join `repartidores` `r` on(`u`.`id` = `r`.`usuario_id`)) WHERE `r`.`status` = 'activo' AND `u`.`status` = 'activo' ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_repartidor_envios_pendientes`
--
DROP TABLE IF EXISTS `vista_repartidor_envios_pendientes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_repartidor_envios_pendientes`  AS SELECT `re`.`usuario_id` AS `repartidor_id`, `e`.`id` AS `envio_id`, `e`.`tracking_number` AS `tracking_number`, `e`.`name` AS `cliente_nombre`, `e`.`phone` AS `cliente_telefono`, `e`.`destination` AS `destination`, `e`.`package_type` AS `package_type`, `e`.`weight` AS `weight`, `e`.`urgent` AS `urgent`, `e`.`status` AS `status`, `e`.`created_at` AS `created_at`, `re`.`fecha_asignacion` AS `fecha_asignacion` FROM (`repartidores_envios` `re` join `envios` `e` on(`re`.`envio_id` = `e`.`id`)) WHERE `e`.`status` in ('Cargado camión','En tránsito','En ruta') ORDER BY `e`.`urgent` DESC, `re`.`fecha_asignacion` ASC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_resumen_notificaciones`
--
DROP TABLE IF EXISTS `vista_resumen_notificaciones`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_resumen_notificaciones`  AS SELECT `notificaciones`.`usuario_id` AS `usuario_id`, count(0) AS `total`, sum(case when `notificaciones`.`leida` = 0 then 1 else 0 end) AS `pendientes`, sum(case when `notificaciones`.`leida` = 1 then 1 else 0 end) AS `leidas`, max(`notificaciones`.`created_at`) AS `ultima_fecha` FROM `notificaciones` GROUP BY `notificaciones`.`usuario_id` ;

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
-- Indices de la tabla `perfiles_bodeguistas`
--
ALTER TABLE `perfiles_bodeguistas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_permiso` (`modulo`,`accion`);

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
-- Indices de la tabla `roles_permisos`
--
ALTER TABLE `roles_permisos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rol_permiso` (`rol_id`,`permiso_id`),
  ADD KEY `permiso_id` (`permiso_id`);

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
-- Indices de la tabla `validaciones_qr`
--
ALTER TABLE `validaciones_qr`
  ADD PRIMARY KEY (`id`),
  ADD KEY `envio_id` (`envio_id`),
  ADD KEY `bodeguista_id` (`bodeguista_id`,`timestamp`),
  ADD KEY `repartidor_id` (`repartidor_id`,`timestamp`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `perfiles_bodeguistas`
--
ALTER TABLE `perfiles_bodeguistas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de la tabla `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `repartidores`
--
ALTER TABLE `repartidores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `roles_permisos`
--
ALTER TABLE `roles_permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=159;

--
-- AUTO_INCREMENT de la tabla `tracking_history`
--
ALTER TABLE `tracking_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de la tabla `validaciones_qr`
--
ALTER TABLE `validaciones_qr`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Filtros para la tabla `perfiles_bodeguistas`
--
ALTER TABLE `perfiles_bodeguistas`
  ADD CONSTRAINT `perfiles_bodeguistas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

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
-- Filtros para la tabla `roles_permisos`
--
ALTER TABLE `roles_permisos`
  ADD CONSTRAINT `roles_permisos_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `roles_permisos_ibfk_2` FOREIGN KEY (`permiso_id`) REFERENCES `permisos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tracking_history`
--
ALTER TABLE `tracking_history`
  ADD CONSTRAINT `tracking_history_ibfk_1` FOREIGN KEY (`envio_id`) REFERENCES `envios` (`id`),
  ADD CONSTRAINT `tracking_history_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `validaciones_qr`
--
ALTER TABLE `validaciones_qr`
  ADD CONSTRAINT `validaciones_qr_ibfk_1` FOREIGN KEY (`envio_id`) REFERENCES `envios` (`id`),
  ADD CONSTRAINT `validaciones_qr_ibfk_2` FOREIGN KEY (`bodeguista_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `validaciones_qr_ibfk_3` FOREIGN KEY (`repartidor_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
