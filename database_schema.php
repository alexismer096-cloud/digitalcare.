<?php

require_once __DIR__ . '/conexion.php';

if (!function_exists('table_columns')) {
    function table_columns(mysqli $conexion, string $table): array
    {
        $columns = [];
        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $result = mysqli_query($conexion, "SHOW COLUMNS FROM `$safeTable`");
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $columns[$row['Field']] = true;
        }
        return $columns;
    }
}

if (!function_exists('add_column_if_missing')) {
    function add_column_if_missing(mysqli $conexion, string $table, string $column, string $definition): void
    {
        $columns = table_columns($conexion, $table);
        if (!isset($columns[$column])) {
            $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
            $safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
            mysqli_query($conexion, "ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition");
        }
    }
}

if (!function_exists('ensure_core_schema')) {
    function ensure_core_schema(mysqli $conexion): void
    {
        mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS usuarios (
            id_usuario INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            apellido VARCHAR(100) DEFAULT NULL,
            nombre_usuario VARCHAR(100) DEFAULT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            google_id VARCHAR(120) DEFAULT NULL,
            auth_provider VARCHAR(30) NOT NULL DEFAULT 'local',
            telefono VARCHAR(50) DEFAULT NULL,
            dni VARCHAR(30) DEFAULT NULL,
            direccion VARCHAR(180) DEFAULT NULL,
            ciudad VARCHAR(100) DEFAULT NULL,
            provincia VARCHAR(100) DEFAULT NULL,
            pais VARCHAR(80) DEFAULT 'Argentina',
            foto_perfil VARCHAR(255) DEFAULT NULL,
            rol VARCHAR(50) NOT NULL DEFAULT 'Cliente',
            fecha_nacimiento DATE DEFAULT NULL,
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_usuarios_google_id (google_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS tecnicos (
            id_tecnico INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            especialidad VARCHAR(100) DEFAULT NULL,
            telefono VARCHAR(30) DEFAULT NULL,
            email VARCHAR(150) DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS repuestos (
            id_repuesto INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(150) NOT NULL,
            descripcion TEXT DEFAULT NULL,
            stock INT NOT NULL DEFAULT 0,
            stock_minimo INT NOT NULL DEFAULT 5,
            proveedor VARCHAR(120) DEFAULT NULL,
            precio DECIMAL(10,2) NOT NULL DEFAULT 0,
            costo DECIMAL(10,2) NOT NULL DEFAULT 0,
            categoria VARCHAR(80) NOT NULL DEFAULT 'Repuesto',
            marca VARCHAR(80) DEFAULT NULL,
            modelo VARCHAR(80) DEFAULT NULL,
            codigo VARCHAR(80) DEFAULT NULL,
            imagen VARCHAR(255) DEFAULT NULL,
            estado ENUM('Activo','Inactivo') NOT NULL DEFAULT 'Activo',
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_repuestos_codigo (codigo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS solicitudes (
            id_solicitud INT AUTO_INCREMENT PRIMARY KEY,
            tipo_equipo VARCHAR(120) NOT NULL,
            problema TEXT NOT NULL,
            estado VARCHAR(50) DEFAULT 'Pendiente',
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            id_usuario INT DEFAULT NULL,
            KEY idx_solicitudes_usuario (id_usuario),
            CONSTRAINT fk_solicitudes_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS trabajos (
            id_trabajo INT AUTO_INCREMENT PRIMARY KEY,
            descripcion TEXT DEFAULT NULL,
            diagnostico TEXT DEFAULT NULL,
            prioridad ENUM('Baja','Media','Alta','Urgente') NOT NULL DEFAULT 'Media',
            observaciones TEXT DEFAULT NULL,
            estado VARCHAR(50) DEFAULT 'Pendiente',
            fecha_inicio DATE DEFAULT NULL,
            fecha_fin DATE DEFAULT NULL,
            costo DECIMAL(10,2) DEFAULT 0,
            id_usuario INT DEFAULT NULL,
            id_tecnico INT DEFAULT NULL,
            id_solicitud INT DEFAULT NULL,
            KEY idx_trabajos_usuario (id_usuario),
            KEY idx_trabajos_tecnico (id_tecnico),
            KEY idx_trabajos_solicitud (id_solicitud),
            CONSTRAINT fk_trabajos_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
            CONSTRAINT fk_trabajos_tecnico FOREIGN KEY (id_tecnico) REFERENCES tecnicos(id_tecnico) ON DELETE SET NULL,
            CONSTRAINT fk_trabajos_solicitud FOREIGN KEY (id_solicitud) REFERENCES solicitudes(id_solicitud) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS mantenimiento (
            id_mantenimiento INT AUTO_INCREMENT PRIMARY KEY,
            id_trabajo INT DEFAULT NULL,
            tipo VARCHAR(100) DEFAULT NULL,
            descripcion TEXT DEFAULT NULL,
            estado VARCHAR(50) DEFAULT 'Pendiente',
            fecha_programada DATE DEFAULT NULL,
            completado_en DATETIME DEFAULT NULL,
            id_usuario INT DEFAULT NULL,
            KEY idx_mantenimiento_trabajo (id_trabajo),
            KEY idx_mantenimiento_usuario (id_usuario),
            CONSTRAINT fk_mantenimiento_trabajo FOREIGN KEY (id_trabajo) REFERENCES trabajos(id_trabajo) ON DELETE SET NULL,
            CONSTRAINT fk_mantenimiento_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS notificaciones (
            id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
            mensaje TEXT NOT NULL,
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            leida TINYINT(1) DEFAULT 0,
            id_usuario INT DEFAULT NULL,
            KEY idx_notificaciones_usuario (id_usuario),
            CONSTRAINT fk_notificaciones_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS configuraciones (
            clave VARCHAR(80) PRIMARY KEY,
            valor TEXT DEFAULT NULL,
            actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS metodos_pago (
            id_metodo INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(80) NOT NULL UNIQUE,
            estado ENUM('Activo','Inactivo') NOT NULL DEFAULT 'Activo',
            requiere_validacion TINYINT(1) NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS actividad_reciente (
            id_actividad INT AUTO_INCREMENT PRIMARY KEY,
            tipo VARCHAR(80) NOT NULL,
            descripcion VARCHAR(255) NOT NULL,
            referencia_id INT DEFAULT NULL,
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            id_usuario INT DEFAULT NULL,
            KEY idx_actividad_fecha (fecha),
            CONSTRAINT fk_actividad_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS reportes (
            id_reporte INT AUTO_INCREMENT PRIMARY KEY,
            tipo VARCHAR(80) NOT NULL,
            fecha_desde DATE NOT NULL,
            fecha_hasta DATE NOT NULL,
            generado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            id_usuario INT DEFAULT NULL,
            CONSTRAINT fk_reportes_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        foreach ([
            'apellido' => "VARCHAR(100) DEFAULT NULL",
            'nombre_usuario' => "VARCHAR(100) DEFAULT NULL",
            'dni' => "VARCHAR(30) DEFAULT NULL",
            'direccion' => "VARCHAR(180) DEFAULT NULL",
            'ciudad' => "VARCHAR(100) DEFAULT NULL",
            'provincia' => "VARCHAR(100) DEFAULT NULL",
            'pais' => "VARCHAR(80) DEFAULT 'Argentina'",
            'foto_perfil' => "VARCHAR(255) DEFAULT NULL",
            'rol' => "VARCHAR(50) NOT NULL DEFAULT 'Cliente'",
            'fecha_nacimiento' => "DATE DEFAULT NULL",
            'tema' => "VARCHAR(30) NOT NULL DEFAULT 'Claro'",
            'seguridad_2fa' => "TINYINT(1) NOT NULL DEFAULT 0",
            'google_id' => "VARCHAR(120) DEFAULT NULL",
            'auth_provider' => "VARCHAR(30) NOT NULL DEFAULT 'local'",
        ] as $column => $definition) {
            add_column_if_missing($conexion, 'usuarios', $column, $definition);
        }

        $googleIndex = mysqli_query($conexion, "SHOW INDEX FROM usuarios WHERE Key_name = 'uq_usuarios_google_id'");
        if (!$googleIndex || mysqli_num_rows($googleIndex) === 0) {
            mysqli_query($conexion, 'ALTER TABLE usuarios ADD UNIQUE KEY uq_usuarios_google_id (google_id)');
        }

        foreach ([
            'stock_minimo' => "INT NOT NULL DEFAULT 5",
            'proveedor' => "VARCHAR(120) DEFAULT NULL",
            'costo' => "DECIMAL(10,2) NOT NULL DEFAULT 0",
            'categoria' => "VARCHAR(80) NOT NULL DEFAULT 'Repuesto'",
            'marca' => "VARCHAR(80) DEFAULT NULL",
            'modelo' => "VARCHAR(80) DEFAULT NULL",
            'codigo' => "VARCHAR(80) DEFAULT NULL",
            'imagen' => "VARCHAR(255) DEFAULT NULL",
            'estado' => "ENUM('Activo','Inactivo') NOT NULL DEFAULT 'Activo'",
            'fecha_creacion' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        ] as $column => $definition) {
            add_column_if_missing($conexion, 'repuestos', $column, $definition);
        }

        foreach ([
            'diagnostico' => "TEXT DEFAULT NULL",
            'prioridad' => "ENUM('Baja','Media','Alta','Urgente') NOT NULL DEFAULT 'Media'",
            'observaciones' => "TEXT DEFAULT NULL",
        ] as $column => $definition) {
            add_column_if_missing($conexion, 'trabajos', $column, $definition);
        }

        foreach ([
            'descripcion' => "TEXT NOT NULL",
            'categoria' => "VARCHAR(100) NOT NULL DEFAULT 'General'",
            'precio' => "DECIMAL(10,2) NOT NULL DEFAULT 0",
            'tiempo_estimado' => "VARCHAR(80) NOT NULL DEFAULT 'A definir'",
            'estado' => "ENUM('Activo','Inactivo') NOT NULL DEFAULT 'Activo'",
            'imagen' => "VARCHAR(255) DEFAULT NULL",
            'fecha_creacion' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        ] as $column => $definition) {
            add_column_if_missing($conexion, 'servicios', $column, $definition);
        }

        add_column_if_missing($conexion, 'mantenimiento', 'id_trabajo', "INT DEFAULT NULL");
        add_column_if_missing($conexion, 'mantenimiento', 'completado_en', "DATETIME DEFAULT NULL");

        $stmt = db_prepare($conexion, "INSERT INTO metodos_pago (nombre,estado,requiere_validacion) VALUES
            ('Mercado Pago','Activo',0),('AstroPay','Activo',0),('Tarjeta','Activo',1),('Transferencia','Activo',0),('Efectivo','Activo',0)
            ON DUPLICATE KEY UPDATE estado=VALUES(estado), requiere_validacion=VALUES(requiere_validacion)");
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('foreign_key_exists')) {
    function foreign_key_exists(mysqli $conexion, string $table, string $column, string $referencedTable, string $referencedColumn): bool
    {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
        $referencedTable = preg_replace('/[^a-zA-Z0-9_]/', '', $referencedTable);
        $referencedColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $referencedColumn);
        $sql = "SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = '$table'
              AND COLUMN_NAME = '$column'
              AND REFERENCED_TABLE_NAME = '$referencedTable'
              AND REFERENCED_COLUMN_NAME = '$referencedColumn'
            LIMIT 1";
        $result = mysqli_query($conexion, $sql);
        return $result && mysqli_num_rows($result) > 0;
    }
}

if (!function_exists('add_foreign_key_if_valid')) {
    function add_foreign_key_if_valid(mysqli $conexion, string $constraint, string $table, string $column, string $referencedTable, string $referencedColumn, string $onDelete): bool
    {
        if (foreign_key_exists($conexion, $table, $column, $referencedTable, $referencedColumn)) {
            return true;
        }

        foreach (['constraint', 'table', 'column', 'referencedTable', 'referencedColumn'] as $name) {
            $$name = preg_replace('/[^a-zA-Z0-9_]/', '', $$name);
        }
        $onDelete = in_array($onDelete, ['CASCADE', 'RESTRICT', 'SET NULL'], true) ? $onDelete : 'RESTRICT';

        $orphanSql = "SELECT COUNT(*) AS total FROM `$table` AS child
            LEFT JOIN `$referencedTable` AS parent ON parent.`$referencedColumn` = child.`$column`
            WHERE child.`$column` IS NOT NULL AND parent.`$referencedColumn` IS NULL";
        $orphanResult = mysqli_query($conexion, $orphanSql);
        $orphans = $orphanResult ? (int) (mysqli_fetch_assoc($orphanResult)['total'] ?? 0) : 1;
        if ($orphans > 0) {
            error_log("No se creo la relacion $constraint: hay $orphans registros huerfanos en $table.$column.");
            return false;
        }

        $sql = "ALTER TABLE `$table` ADD CONSTRAINT `$constraint`
            FOREIGN KEY (`$column`) REFERENCES `$referencedTable` (`$referencedColumn`) ON DELETE $onDelete";
        if (!mysqli_query($conexion, $sql)) {
            error_log("No se pudo crear la relacion $constraint: " . mysqli_error($conexion));
            return false;
        }
        return true;
    }
}

if (!function_exists('ensure_relationships')) {
    function ensure_relationships(mysqli $conexion): array
    {
        $relationships = [
            ['fk_mantenimiento_trabajo', 'mantenimiento', 'id_trabajo', 'trabajos', 'id_trabajo', 'SET NULL'],
            ['fk_compras_usuario', 'compras', 'id_usuario', 'usuarios', 'id_usuario', 'RESTRICT'],
            ['fk_detalles_compras_compra', 'detalles_compras', 'id_compra', 'compras', 'id_compra', 'CASCADE'],
            ['fk_detalles_compras_repuesto', 'detalles_compras', 'id_repuesto', 'repuestos', 'id_repuesto', 'RESTRICT'],
            ['fk_pagos_usuario', 'pagos', 'id_cliente', 'usuarios', 'id_usuario', 'RESTRICT'],
            ['fk_movimientos_repuesto', 'movimientos_stock', 'id_repuesto', 'repuestos', 'id_repuesto', 'RESTRICT'],
            ['fk_movimientos_usuario', 'movimientos_stock', 'id_usuario', 'usuarios', 'id_usuario', 'SET NULL'],
            ['fk_movimientos_producto', 'movimientos_stock', 'producto_id', 'repuestos', 'id_repuesto', 'RESTRICT'],
            ['fk_movimientos_pedido', 'movimientos_stock', 'pedido_id', 'pedidos', 'id', 'SET NULL'],
        ];
        $pending = [];
        foreach ($relationships as [$constraint, $table, $column, $referencedTable, $referencedColumn, $onDelete]) {
            if (!add_foreign_key_if_valid($conexion, $constraint, $table, $column, $referencedTable, $referencedColumn, $onDelete)) {
                $pending[] = "$table.$column";
            }
        }
        return $pending;
    }
}

if (!function_exists('ensure_purchase_tables')) {
    function ensure_purchase_tables(mysqli $conexion): bool
    {
        $sqlCompras = "CREATE TABLE IF NOT EXISTS compras (
            id_compra INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            total DECIMAL(10, 2) NOT NULL,
            estado VARCHAR(50) DEFAULT 'Pendiente',
            metodo_pago VARCHAR(50) DEFAULT NULL,
            tarjeta_ultimos4 VARCHAR(4) DEFAULT NULL,
            referencia_pago VARCHAR(100) DEFAULT NULL,
            fecha_compra TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sqlDetalles = "CREATE TABLE IF NOT EXISTS detalles_compras (
            id_detalle INT AUTO_INCREMENT PRIMARY KEY,
            id_compra INT NOT NULL,
            id_repuesto INT NOT NULL,
            cantidad INT NOT NULL,
            precio_unitario DECIMAL(10, 2) NOT NULL,
            subtotal DECIMAL(10, 2) NOT NULL,
            FOREIGN KEY (id_compra) REFERENCES compras(id_compra) ON DELETE CASCADE,
            FOREIGN KEY (id_repuesto) REFERENCES repuestos(id_repuesto) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sqlCarrito = "CREATE TABLE IF NOT EXISTS carrito (
            id_carrito INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            id_repuesto INT NOT NULL,
            cantidad INT NOT NULL DEFAULT 1,
            fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizado TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY carrito_usuario_repuesto (id_usuario, id_repuesto),
            FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
            FOREIGN KEY (id_repuesto) REFERENCES repuestos(id_repuesto) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sqlPedidos = "CREATE TABLE IF NOT EXISTS pedidos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cliente_id INT NOT NULL,
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            total DECIMAL(10, 2) NOT NULL,
            metodo_pago VARCHAR(50) NOT NULL,
            estado VARCHAR(50) NOT NULL DEFAULT 'Pendiente',
            nombre_completo VARCHAR(150) NOT NULL,
            dni VARCHAR(30) NOT NULL,
            telefono VARCHAR(50) NOT NULL,
            email VARCHAR(150) NOT NULL,
            direccion VARCHAR(180) NOT NULL,
            ciudad VARCHAR(100) NOT NULL,
            provincia VARCHAR(100) NOT NULL,
            codigo_postal VARCHAR(20) NOT NULL,
            numero_operacion VARCHAR(80) NOT NULL,
            estado_pago VARCHAR(50) NOT NULL,
            pago_confirmado_en TIMESTAMP NULL DEFAULT NULL,
            FOREIGN KEY (cliente_id) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sqlDetallePedido = "CREATE TABLE IF NOT EXISTS detalle_pedido (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pedido_id INT NOT NULL,
            producto_id INT NOT NULL,
            cantidad INT NOT NULL,
            precio_unitario DECIMAL(10, 2) NOT NULL,
            subtotal DECIMAL(10, 2) NOT NULL,
            FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
            FOREIGN KEY (producto_id) REFERENCES repuestos(id_repuesto) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sqlPagos = "CREATE TABLE IF NOT EXISTS pagos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pedido_id INT NOT NULL,
            id_cliente INT DEFAULT NULL,
            proveedor VARCHAR(50) NOT NULL,
            numero_operacion VARCHAR(80) NOT NULL,
            titular VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL,
            metodo VARCHAR(80) NOT NULL,
            pais VARCHAR(80) DEFAULT NULL,
            total DECIMAL(10, 2) NOT NULL,
            monto DECIMAL(10, 2) DEFAULT NULL,
            metodo_pago VARCHAR(80) DEFAULT NULL,
            estado VARCHAR(50) NOT NULL,
            referencia_proveedor VARCHAR(120) DEFAULT NULL,
            tarjeta_ultimos4 VARCHAR(4) DEFAULT NULL,
            tipo_tarjeta VARCHAR(50) DEFAULT NULL,
            documento_ficticio VARCHAR(30) DEFAULT NULL,
            ambiente VARCHAR(30) DEFAULT 'Sandbox',
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
            FOREIGN KEY (id_cliente) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sqlMovimientos = "CREATE TABLE IF NOT EXISTS movimientos_stock (
            id INT AUTO_INCREMENT PRIMARY KEY,
            producto_id INT NOT NULL,
            id_repuesto INT DEFAULT NULL,
            pedido_id INT DEFAULT NULL,
            id_usuario INT DEFAULT NULL,
            tipo VARCHAR(40) NOT NULL,
            cantidad INT NOT NULL,
            stock_anterior INT NOT NULL,
            stock_nuevo INT NOT NULL,
            descripcion VARCHAR(255) NOT NULL,
            motivo VARCHAR(255) DEFAULT NULL,
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (producto_id) REFERENCES repuestos(id_repuesto) ON DELETE CASCADE,
            FOREIGN KEY (id_repuesto) REFERENCES repuestos(id_repuesto) ON DELETE SET NULL,
            FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sqlAlertas = "CREATE TABLE IF NOT EXISTS alertas_stock (
            id INT AUTO_INCREMENT PRIMARY KEY,
            producto_id INT NOT NULL,
            pedido_id INT DEFAULT NULL,
            mensaje VARCHAR(255) NOT NULL,
            estado VARCHAR(30) NOT NULL DEFAULT 'Pendiente',
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (producto_id) REFERENCES repuestos(id_repuesto) ON DELETE CASCADE,
            FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $ok = mysqli_query($conexion, $sqlCompras)
            && mysqli_query($conexion, $sqlDetalles)
            && mysqli_query($conexion, $sqlCarrito)
            && mysqli_query($conexion, $sqlPedidos)
            && mysqli_query($conexion, $sqlDetallePedido)
            && mysqli_query($conexion, $sqlPagos)
            && mysqli_query($conexion, $sqlMovimientos)
            && mysqli_query($conexion, $sqlAlertas);

        /* Compatibilidad con instalaciones que ya tenian las tablas creadas. */
        @mysqli_query($conexion, "ALTER TABLE carrito ADD COLUMN fecha_actualizado TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        @mysqli_query($conexion, "CREATE UNIQUE INDEX carrito_usuario_repuesto ON carrito (id_usuario, id_repuesto)");
        @mysqli_query($conexion, "ALTER TABLE pedidos ADD COLUMN pago_confirmado_en TIMESTAMP NULL DEFAULT NULL");
        @mysqli_query($conexion, "ALTER TABLE pedidos ADD COLUMN checkout_intent VARCHAR(64) DEFAULT NULL");
        @mysqli_query($conexion, "CREATE UNIQUE INDEX uq_pedidos_operacion ON pedidos (numero_operacion)");
        @mysqli_query($conexion, "CREATE UNIQUE INDEX uq_pedidos_checkout_intent ON pedidos (checkout_intent)");
        @mysqli_query($conexion, "CREATE INDEX idx_pedidos_cliente_fecha ON pedidos (cliente_id, fecha)");
        @mysqli_query($conexion, "CREATE INDEX idx_pedidos_estado_pago ON pedidos (estado_pago)");
        @mysqli_query($conexion, "ALTER TABLE pagos ADD COLUMN id_cliente INT DEFAULT NULL");
        @mysqli_query($conexion, "ALTER TABLE pagos ADD COLUMN monto DECIMAL(10,2) DEFAULT NULL");
        @mysqli_query($conexion, "ALTER TABLE pagos ADD COLUMN metodo_pago VARCHAR(80) DEFAULT NULL");
        @mysqli_query($conexion, "ALTER TABLE pagos ADD COLUMN referencia_proveedor VARCHAR(120) DEFAULT NULL");
        @mysqli_query($conexion, "ALTER TABLE pagos ADD COLUMN tarjeta_ultimos4 VARCHAR(4) DEFAULT NULL");
        @mysqli_query($conexion, "ALTER TABLE pagos ADD COLUMN tipo_tarjeta VARCHAR(50) DEFAULT NULL");
        @mysqli_query($conexion, "ALTER TABLE pagos ADD COLUMN documento_ficticio VARCHAR(30) DEFAULT NULL");
        @mysqli_query($conexion, "ALTER TABLE pagos ADD COLUMN ambiente VARCHAR(30) DEFAULT 'Sandbox'");
        @mysqli_query($conexion, "CREATE UNIQUE INDEX uq_pagos_operacion ON pagos (numero_operacion)");
        @mysqli_query($conexion, "CREATE INDEX idx_pagos_cliente ON pagos (id_cliente)");
        @mysqli_query($conexion, "ALTER TABLE movimientos_stock ADD COLUMN id_repuesto INT DEFAULT NULL");
        @mysqli_query($conexion, "ALTER TABLE movimientos_stock ADD COLUMN id_usuario INT DEFAULT NULL");
        @mysqli_query($conexion, "ALTER TABLE movimientos_stock ADD COLUMN motivo VARCHAR(255) DEFAULT NULL");
        return $ok;
    }
}

if (!function_exists('ensure_digitalcare_shop_schema')) {
    function ensure_digitalcare_shop_schema(mysqli $conexion): void
    {
        ensure_core_schema($conexion);
        ensure_product_columns($conexion);
        ensure_purchase_tables($conexion);
    }
}

if (!function_exists('ensure_product_columns')) {
    function ensure_product_columns(mysqli $conexion): void
    {
        $columns = [];
        $result = mysqli_query($conexion, "SHOW COLUMNS FROM repuestos");
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $columns[$row['Field']] = true;
            }
        }

        if (!isset($columns['categoria'])) {
            mysqli_query($conexion, "ALTER TABLE repuestos ADD COLUMN categoria VARCHAR(80) NOT NULL DEFAULT 'Repuesto'");
        }

        if (!isset($columns['imagen'])) {
            mysqli_query($conexion, "ALTER TABLE repuestos ADD COLUMN imagen VARCHAR(255) DEFAULT NULL");
        }
    }
}

if (!function_exists('infer_product_category')) {
    function infer_product_category(string $nombre): string
    {
        $nombre = mb_strtolower($nombre, 'UTF-8');

        if (str_contains($nombre, 'ssd') || str_contains($nombre, 'hdd') || str_contains($nombre, 'disco')) return 'Almacenamiento';
        if (str_contains($nombre, 'ram') || str_contains($nombre, 'memoria')) return 'Memorias';
        if (str_contains($nombre, 'pantalla') || str_contains($nombre, 'monitor')) return 'Pantallas';
        if (str_contains($nombre, 'bater') || str_contains($nombre, 'ups') || str_contains($nombre, 'cargador') || str_contains($nombre, 'fuente')) return 'Energía';
        if (str_contains($nombre, 'teclado') || str_contains($nombre, 'mouse') || str_contains($nombre, 'webcam') || str_contains($nombre, 'auricular')) return 'Periféricos';
        if (str_contains($nombre, 'cable') || str_contains($nombre, 'adaptador') || str_contains($nombre, 'conector')) return 'Conectividad';
        if (str_contains($nombre, 'cooler') || str_contains($nombre, 'ventilador') || str_contains($nombre, 'refriger')) return 'Refrigeración';
        if (str_contains($nombre, 'placa') || str_contains($nombre, 'procesador') || str_contains($nombre, 'gpu') || str_contains($nombre, 'tarjeta')) return 'Componentes';

        return 'Repuesto';
    }
}

if (!function_exists('hydrate_product_metadata')) {
    function hydrate_product_metadata(mysqli $conexion): void
    {
        $result = mysqli_query($conexion, "SELECT id_repuesto, nombre, categoria FROM repuestos WHERE categoria = 'Repuesto' OR categoria = '' OR categoria IS NULL");
        if (!$result) {
            return;
        }

        $stmt = mysqli_prepare($conexion, "UPDATE repuestos SET categoria = ? WHERE id_repuesto = ?");
        if (!$stmt) {
            return;
        }

        while ($row = mysqli_fetch_assoc($result)) {
            $categoria = infer_product_category($row['nombre'] ?? '');
            $id = (int) $row['id_repuesto'];
            mysqli_stmt_bind_param($stmt, 'si', $categoria, $id);
            mysqli_stmt_execute($stmt);
        }

        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('ensure_users_table')) {
    function ensure_users_table(mysqli $conexion): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS usuarios (
            id_usuario INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            telefono VARCHAR(50) DEFAULT NULL,
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        return mysqli_query($conexion, $sql);
    }
}

if (!function_exists('ensure_business_tables')) {
    function ensure_business_tables(mysqli $conexion): void
    {
        /* Campos opcionales de perfil; se agregan sin afectar cuentas existentes. */
        $userColumns = [];
        $result = mysqli_query($conexion, 'SHOW COLUMNS FROM usuarios');
        while ($result && $column = mysqli_fetch_assoc($result)) $userColumns[$column['Field']] = true;
        $extras = [
            'apellido' => "VARCHAR(100) DEFAULT NULL", 'dni' => "VARCHAR(30) DEFAULT NULL",
            'direccion' => "VARCHAR(180) DEFAULT NULL", 'ciudad' => "VARCHAR(100) DEFAULT NULL",
            'provincia' => "VARCHAR(100) DEFAULT NULL", 'pais' => "VARCHAR(80) DEFAULT 'Argentina'",
            'foto_perfil' => "VARCHAR(255) DEFAULT NULL", 'rol' => "VARCHAR(50) NOT NULL DEFAULT 'Cliente'",
            'nombre_usuario' => "VARCHAR(100) DEFAULT NULL", 'fecha_nacimiento' => "DATE DEFAULT NULL"
        ];
        foreach ($extras as $name => $definition) if (!isset($userColumns[$name])) @mysqli_query($conexion, "ALTER TABLE usuarios ADD COLUMN $name $definition");

        mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS servicios (
            id_servicio INT AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(150) NOT NULL,
            descripcion TEXT NOT NULL, categoria VARCHAR(100) NOT NULL, precio DECIMAL(10,2) NOT NULL,
            tiempo_estimado VARCHAR(80) NOT NULL, estado ENUM('Activo','Inactivo') NOT NULL DEFAULT 'Activo',
            imagen VARCHAR(255) DEFAULT NULL, fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS trabajo_servicio (
            id_trabajo INT NOT NULL, id_servicio INT NOT NULL, cantidad INT NOT NULL DEFAULT 1,
            precio_unitario DECIMAL(10,2) NOT NULL, PRIMARY KEY(id_trabajo,id_servicio),
            FOREIGN KEY (id_trabajo) REFERENCES trabajos(id_trabajo) ON DELETE CASCADE,
            FOREIGN KEY (id_servicio) REFERENCES servicios(id_servicio) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS estado_modulos (
            clave VARCHAR(60) PRIMARY KEY, nombre VARCHAR(100) NOT NULL,
            estado ENUM('Completado','En progreso','Pendiente') NOT NULL DEFAULT 'Pendiente',
            actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS historial_mantenimiento (
            id_historial INT AUTO_INCREMENT PRIMARY KEY,
            id_mantenimiento INT NOT NULL,
            estado_anterior VARCHAR(50) DEFAULT NULL,
            estado_nuevo VARCHAR(50) NOT NULL,
            observacion TEXT DEFAULT NULL,
            id_usuario INT DEFAULT NULL,
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_historial_mantenimiento (id_mantenimiento),
            CONSTRAINT fk_historial_mantenimiento_tarea FOREIGN KEY (id_mantenimiento) REFERENCES mantenimiento(id_mantenimiento) ON DELETE CASCADE,
            CONSTRAINT fk_historial_mantenimiento_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $modules = [
            ['login','Inicio de sesión','Completado'],['registro','Registro de usuarios','Completado'],['clientes','Clientes','Completado'],['trabajos','Ingreso y consulta de trabajos','Completado'],['mantenimiento','Mantenimiento','Completado'],['repuestos','Repuestos e ingreso de stock','Completado'],['stock','Alertas de stock crítico','Completado'],['servicios','Servicios','Completado'],['carrito','Carrito de compras','Completado'],['pedidos','Pedidos','Completado'],['pagos','Métodos de pago','Completado'],['mis_pedidos','Mis pedidos','Completado'],['perfil','Perfil','Completado'],['reportes','Reportes y estadísticas','Completado'],['base_datos','Base de datos','Completado'],['testing','Testing integral','Completado']
        ];
        $stmt = mysqli_prepare($conexion, "INSERT INTO estado_modulos (clave,nombre,estado) VALUES (?,?,?) ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), estado=VALUES(estado)");
        foreach ($modules as $module) { mysqli_stmt_bind_param($stmt, 'sss', $module[0], $module[1], $module[2]); mysqli_stmt_execute($stmt); }
        mysqli_stmt_close($stmt);
    }
}
