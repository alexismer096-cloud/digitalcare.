<?php
declare(strict_types=1);

if (!function_exists('detectar_tipo_tarjeta_prueba')) {
    function detectar_tipo_tarjeta_prueba(string $numero): string
    {
        $digits = preg_replace('/\D+/', '', $numero);
        if (str_starts_with($digits, '4')) return 'Visa';
        if (preg_match('/^5[1-5]/', $digits) || preg_match('/^2[2-7]/', $digits)) return 'Mastercard';
        if (preg_match('/^3[47]/', $digits)) return 'American Express';
        return 'Tarjeta de prueba';
    }
}

if (!function_exists('luhn_valido')) {
    function luhn_valido(string $numero): bool
    {
        $digits = preg_replace('/\D+/', '', $numero);
        if (strlen($digits) < 13 || strlen($digits) > 19) return false;
        $sum = 0;
        $alternate = false;
        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $n = (int) $digits[$i];
            if ($alternate) {
                $n *= 2;
                if ($n > 9) $n -= 9;
            }
            $sum += $n;
            $alternate = !$alternate;
        }
        return $sum % 10 === 0;
    }
}

if (!function_exists('validar_tarjeta_prueba')) {
    function validar_tarjeta_prueba(array $data): array
    {
        $titular = trim((string) ($data['titular'] ?? ''));
        $numero = preg_replace('/\D+/', '', (string) ($data['numero_tarjeta'] ?? ''));
        $vencimiento = trim((string) ($data['vencimiento'] ?? ''));
        $cvv = trim((string) ($data['cvv'] ?? ''));
        $documento = trim((string) ($data['documento'] ?? ''));
        $resultado = trim((string) ($data['resultado'] ?? ''));

        if ($titular === '' || $numero === '' || $vencimiento === '' || $cvv === '' || $documento === '' || $resultado === '') {
            return ['ok' => false, 'error' => 'Completá todos los datos de prueba.'];
        }
        if (!luhn_valido($numero)) {
            return ['ok' => false, 'error' => 'El número de tarjeta de prueba no tiene un formato válido.'];
        }
        if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $vencimiento)) {
            return ['ok' => false, 'error' => 'El vencimiento debe tener formato MM/AA.'];
        }
        [$mes, $anio] = array_map('intval', explode('/', $vencimiento));
        $vence = DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-01', 2000 + $anio, $mes));
        if (!$vence || $vence < new DateTime('first day of this month')) {
            return ['ok' => false, 'error' => 'La tarjeta de prueba está vencida.'];
        }
        if (!preg_match('/^\d{3,4}$/', $cvv)) {
            return ['ok' => false, 'error' => 'El CVV de prueba debe tener 3 o 4 dígitos.'];
        }
        if (!preg_match('/^[0-9.\- ]{6,20}$/', $documento)) {
            return ['ok' => false, 'error' => 'El documento ficticio no tiene un formato válido.'];
        }
        if (!in_array($resultado, ['aprobado', 'rechazado', 'pendiente'], true)) {
            return ['ok' => false, 'error' => 'Seleccioná un resultado de prueba válido.'];
        }

        return [
            'ok' => true,
            'titular' => $titular,
            'ultimos4' => substr($numero, -4),
            'tipo_tarjeta' => detectar_tipo_tarjeta_prueba($numero),
            'documento' => $documento,
            'resultado' => $resultado,
        ];
    }
}

if (!function_exists('aplicar_resultado_pago_simulado')) {
    function aplicar_resultado_pago_simulado(mysqli $conexion, int $usuarioId, string $operacion, string $resultado, array $tarjeta): array
    {
        $mapPago = ['aprobado' => 'Aprobado', 'rechazado' => 'Rechazado', 'pendiente' => 'Pendiente'];
        $estadoPago = $mapPago[$resultado] ?? 'Pendiente';
        $estadoPedido = $resultado === 'aprobado' ? 'Confirmado' : 'Pendiente';
        $referencia = 'TEST-' . strtoupper(bin2hex(random_bytes(6)));

        mysqli_begin_transaction($conexion);

        $stmt = db_prepare($conexion, 'SELECT p.id,p.estado,p.estado_pago,p.total,pg.referencia_proveedor FROM pedidos p JOIN pagos pg ON pg.pedido_id=p.id WHERE p.cliente_id=? AND pg.numero_operacion=? FOR UPDATE');
        mysqli_stmt_bind_param($stmt, 'is', $usuarioId, $operacion);
        mysqli_stmt_execute($stmt);
        $pedido = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$pedido) {
            mysqli_rollback($conexion);
            return ['ok' => false, 'error' => 'No se encontró la operación de prueba.'];
        }

        $pedidoId = (int) $pedido['id'];
        $yaPagado = in_array((string) $pedido['estado_pago'], ['Pagado', 'Aprobado'], true);

        if ($yaPagado) {
            mysqli_commit($conexion);
            return [
                'ok' => true,
                'pedido_id' => $pedidoId,
                'numero_operacion' => $operacion,
                'estado_pago' => 'Aprobado',
                'referencia' => (string) ($pedido['referencia_proveedor'] ?: 'Aprobado previamente'),
            ];
        }

        if ((string) $pedido['estado_pago'] === 'Rechazado') {
            mysqli_commit($conexion);
            return [
                'ok' => true,
                'pedido_id' => $pedidoId,
                'numero_operacion' => $operacion,
                'estado_pago' => 'Rechazado',
                'referencia' => (string) ($pedido['referencia_proveedor'] ?: 'Rechazado previamente'),
            ];
        }

        $stmt = db_prepare($conexion, 'UPDATE pagos SET estado=?, referencia_proveedor=?, titular=?, tarjeta_ultimos4=?, tipo_tarjeta=?, documento_ficticio=?, ambiente="Sandbox" WHERE numero_operacion=?');
        mysqli_stmt_bind_param($stmt, 'sssssss', $estadoPago, $referencia, $tarjeta['titular'], $tarjeta['ultimos4'], $tarjeta['tipo_tarjeta'], $tarjeta['documento'], $operacion);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($resultado === 'aprobado') {
            $stmt = db_prepare($conexion, 'SELECT d.producto_id,d.cantidad,r.nombre,r.stock,r.stock_minimo FROM detalle_pedido d JOIN repuestos r ON r.id_repuesto=d.producto_id WHERE d.pedido_id=? FOR UPDATE');
            mysqli_stmt_bind_param($stmt, 'i', $pedidoId);
            mysqli_stmt_execute($stmt);
            $rs = mysqli_stmt_get_result($stmt);
            $detalles = [];
            while ($row = $rs ? mysqli_fetch_assoc($rs) : null) $detalles[] = $row;
            mysqli_stmt_close($stmt);

            foreach ($detalles as $detalle) {
                if ((int) $detalle['stock'] < (int) $detalle['cantidad']) {
                    mysqli_rollback($conexion);
                    return ['ok' => false, 'error' => 'No hay stock suficiente para aprobar esta operación.'];
                }
            }

            foreach ($detalles as $detalle) {
                $productoId = (int) $detalle['producto_id'];
                $cantidad = (int) $detalle['cantidad'];
                $anterior = (int) $detalle['stock'];
                $nuevo = $anterior - $cantidad;
                $tipoMovimiento = 'egreso';
                $cantidadMovimiento = -$cantidad;
                $descripcion = 'Descuento por pago aprobado de prueba pedido #' . $pedidoId;

                $stmt = db_prepare($conexion, 'UPDATE repuestos SET stock=? WHERE id_repuesto=?');
                mysqli_stmt_bind_param($stmt, 'ii', $nuevo, $productoId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                $stmt = db_prepare($conexion, 'INSERT INTO movimientos_stock (producto_id,id_repuesto,pedido_id,tipo,cantidad,stock_anterior,stock_nuevo,descripcion,motivo) VALUES (?,?,?,?,?,?,?,?,?)');
                mysqli_stmt_bind_param($stmt, 'iiisiiiss', $productoId, $productoId, $pedidoId, $tipoMovimiento, $cantidadMovimiento, $anterior, $nuevo, $descripcion, $descripcion);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                if ($nuevo <= (int) $detalle['stock_minimo']) {
                    $mensaje = 'Stock crítico: ' . ($detalle['nombre'] ?? 'Repuesto') . ' quedó en ' . $nuevo . ' unidades.';
                    $stmt = db_prepare($conexion, 'INSERT INTO alertas_stock (producto_id,pedido_id,mensaje,estado) VALUES (?,?,?,"Pendiente")');
                    mysqli_stmt_bind_param($stmt, 'iis', $productoId, $pedidoId, $mensaje);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            }

            $stmt = db_prepare($conexion, 'DELETE FROM carrito WHERE id_usuario=?');
            mysqli_stmt_bind_param($stmt, 'i', $usuarioId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        $confirmado = $resultado === 'aprobado' ? 'NOW()' : 'NULL';
        $stmt = db_prepare($conexion, "UPDATE pedidos SET estado=?, estado_pago=?, pago_confirmado_en=$confirmado WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'ssi', $estadoPedido, $estadoPago, $pedidoId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        mysqli_commit($conexion);

        return [
            'ok' => true,
            'pedido_id' => $pedidoId,
            'numero_operacion' => $operacion,
            'estado_pago' => $estadoPago,
            'referencia' => $referencia,
        ];
    }
}
