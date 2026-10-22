-- Crear tabla de compras
CREATE TABLE IF NOT EXISTS compras (
    id_compra INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    estado VARCHAR(50) DEFAULT 'Pendiente',
    fecha_compra TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla de detalles de compras
CREATE TABLE IF NOT EXISTS detalles_compras (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_compra INT NOT NULL,
    id_repuesto INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (id_compra) REFERENCES compras(id_compra) ON DELETE CASCADE,
    FOREIGN KEY (id_repuesto) REFERENCES repuestos(id_repuesto) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar índices para optimizar búsquedas
CREATE INDEX idx_usuario_compra ON compras(id_usuario);
CREATE INDEX idx_compra_detalle ON detalles_compras(id_compra);

-- Pedidos, pagos y trazabilidad de inventario del checkout profesional
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL,
    metodo_pago VARCHAR(50) NOT NULL,
    estado VARCHAR(50) NOT NULL DEFAULT 'Pendiente',
    nombre_completo VARCHAR(150) NOT NULL, dni VARCHAR(30) NOT NULL,
    telefono VARCHAR(50) NOT NULL, email VARCHAR(150) NOT NULL,
    direccion VARCHAR(180) NOT NULL, ciudad VARCHAR(100) NOT NULL,
    provincia VARCHAR(100) NOT NULL, codigo_postal VARCHAR(20) NOT NULL,
    numero_operacion VARCHAR(80) NOT NULL,
    checkout_intent VARCHAR(64) DEFAULT NULL,
    estado_pago VARCHAR(50) NOT NULL,
    pago_confirmado_en TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_pedidos_operacion (numero_operacion),
    UNIQUE KEY uq_pedidos_checkout_intent (checkout_intent),
    KEY idx_pedidos_cliente_fecha (cliente_id, fecha),
    KEY idx_pedidos_estado_pago (estado_pago),
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS detalle_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY, pedido_id INT NOT NULL, producto_id INT NOT NULL,
    cantidad INT NOT NULL, precio_unitario DECIMAL(10,2) NOT NULL, subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES repuestos(id_repuesto) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    id_cliente INT DEFAULT NULL,
    id_trabajo INT DEFAULT NULL,
    proveedor VARCHAR(50) NOT NULL,
    numero_operacion VARCHAR(80) NOT NULL UNIQUE, referencia_proveedor VARCHAR(120) DEFAULT NULL,
    titular VARCHAR(150) NOT NULL, email VARCHAR(150) NOT NULL, metodo VARCHAR(80) NOT NULL,
    metodo_pago VARCHAR(50) DEFAULT NULL,
    pais VARCHAR(80) DEFAULT NULL,
    total DECIMAL(10,2) NOT NULL,
    monto DECIMAL(10,2) DEFAULT NULL,
    estado VARCHAR(50) NOT NULL,
    tarjeta_ultimos4 VARCHAR(4) DEFAULT NULL,
    tipo_tarjeta VARCHAR(40) DEFAULT NULL,
    documento_ficticio VARCHAR(30) DEFAULT NULL,
    ambiente VARCHAR(30) NOT NULL DEFAULT 'Sandbox',
    fecha_pago TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pagos_estado_fecha (estado, fecha),
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS movimientos_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    id_repuesto INT DEFAULT NULL,
    id_usuario INT DEFAULT NULL,
    pedido_id INT DEFAULT NULL,
    tipo VARCHAR(40) NOT NULL, cantidad INT NOT NULL, stock_anterior INT NOT NULL, stock_nuevo INT NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    motivo VARCHAR(255) DEFAULT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_movimientos_pedido (pedido_id),
    FOREIGN KEY (producto_id) REFERENCES repuestos(id_repuesto) ON DELETE CASCADE,
    FOREIGN KEY (id_repuesto) REFERENCES repuestos(id_repuesto) ON DELETE SET NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS alertas_stock (
    id INT AUTO_INCREMENT PRIMARY KEY, producto_id INT NOT NULL, pedido_id INT DEFAULT NULL,
    mensaje VARCHAR(255) NOT NULL, estado VARCHAR(30) NOT NULL DEFAULT 'Pendiente', fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES repuestos(id_repuesto) ON DELETE CASCADE,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
