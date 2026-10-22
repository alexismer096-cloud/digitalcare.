<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');

session_start();
require_once 'conexion.php';
require_once 'database_schema.php';

if (!isset($_SESSION['id_usuario'])) {
    json_response(['success' => false, 'error' => 'Debes iniciar sesión.'], 401);
}

ensure_digitalcare_shop_schema($conexion);

$idUsuario = (int) $_SESSION['id_usuario'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
}

function carrito_items(mysqli $db, int $usuario, bool $lock = false): array
{
    $sql = "SELECT c.id_carrito,c.id_repuesto,c.cantidad,r.nombre,r.descripcion,r.categoria,r.imagen,r.stock,r.precio
            FROM carrito c
            JOIN repuestos r ON r.id_repuesto = c.id_repuesto
            WHERE c.id_usuario = ?
            ORDER BY c.fecha_agregado" . ($lock ? ' FOR UPDATE' : '');
    $stmt = db_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $usuario);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    $items = [];
    while ($rs && $row = mysqli_fetch_assoc($rs)) {
        $row['cantidad'] = (int) $row['cantidad'];
        $row['stock'] = (int) $row['stock'];
        $row['precio'] = (float) $row['precio'];
        $items[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $items;
}

function normalizar_carrito_usuario(mysqli $db, int $usuario): void
{
    $stmt = db_prepare($db, 'SELECT id_repuesto, MIN(id_carrito) principal, SUM(cantidad) cantidad_total
        FROM carrito
        WHERE id_usuario = ?
        GROUP BY id_repuesto
        HAVING COUNT(*) > 1');
    mysqli_stmt_bind_param($stmt, 'i', $usuario);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    $duplicados = [];
    while ($rs && $row = mysqli_fetch_assoc($rs)) {
        $duplicados[] = $row;
    }
    mysqli_stmt_close($stmt);

    foreach ($duplicados as $duplicado) {
        $principal = (int) $duplicado['principal'];
        $repuesto = (int) $duplicado['id_repuesto'];
        $cantidad = (int) $duplicado['cantidad_total'];

        $stmt = db_prepare($db, 'UPDATE carrito SET cantidad = ? WHERE id_carrito = ? AND id_usuario = ?');
        mysqli_stmt_bind_param($stmt, 'iii', $cantidad, $principal, $usuario);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $stmt = db_prepare($db, 'DELETE FROM carrito WHERE id_usuario = ? AND id_repuesto = ? AND id_carrito <> ?');
        mysqli_stmt_bind_param($stmt, 'iii', $usuario, $repuesto, $principal);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function cantidad_carrito(mysqli $db, int $usuario): int
{
    $stmt = db_prepare($db, 'SELECT COALESCE(SUM(cantidad),0) total FROM carrito WHERE id_usuario=?');
    mysqli_stmt_bind_param($stmt, 'i', $usuario);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return (int) ($row['total'] ?? 0);
}

function campo_obligatorio(string $name, string $label, int $max = 150): string
{
    $value = trim((string) ($_POST[$name] ?? ''));
    if ($value === '' || mb_strlen($value, 'UTF-8') > $max) {
        json_response(['success' => false, 'error' => "Completá correctamente: $label."], 422);
    }
    return $value;
}

function numero_operacion_prueba(): string
{
    return 'MP-TEST-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(4)));
}

if ($action === 'estado_pagos') {
    json_response([
        'success' => true,
        'mercado_pago' => ['conectado' => true, 'ambiente' => 'Sandbox/mock'],
        'locales' => ['conectado' => true],
    ]);
}

if ($action === 'contar') {
    normalizar_carrito_usuario($conexion, $idUsuario);
    json_response(['success' => true, 'cantidad' => cantidad_carrito($conexion, $idUsuario)]);
}

if ($action === 'agregar') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['success' => false, 'error' => 'Método no permitido.'], 405);
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) json_response(['success' => false, 'error' => 'Producto inválido.'], 422);

    $stmt = db_prepare($conexion, 'SELECT stock FROM repuestos WHERE id_repuesto=?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $producto = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$producto) json_response(['success' => false, 'error' => 'Producto no encontrado.'], 404);
    if ((int) $producto['stock'] < 1) json_response(['success' => false, 'error' => 'Este producto no tiene stock.'], 422);

    normalizar_carrito_usuario($conexion, $idUsuario);

    $stmt = db_prepare($conexion, 'SELECT id_carrito,cantidad FROM carrito WHERE id_usuario=? AND id_repuesto=? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'ii', $idUsuario, $id);
    mysqli_stmt_execute($stmt);
    $enCarrito = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($enCarrito && (int) $enCarrito['cantidad'] >= (int) $producto['stock']) {
        json_response(['success' => false, 'error' => 'Ya alcanzaste el máximo disponible de este producto.'], 422);
    }

    if ($enCarrito) {
        $nuevaCantidad = (int) $enCarrito['cantidad'] + 1;
        $carritoId = (int) $enCarrito['id_carrito'];
        $stmt = db_prepare($conexion, 'UPDATE carrito SET cantidad=? WHERE id_carrito=? AND id_usuario=?');
        mysqli_stmt_bind_param($stmt, 'iii', $nuevaCantidad, $carritoId, $idUsuario);
    } else {
        $stmt = db_prepare($conexion, 'INSERT INTO carrito (id_usuario,id_repuesto,cantidad) VALUES (?,?,1)');
        mysqli_stmt_bind_param($stmt, 'ii', $idUsuario, $id);
    }
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    json_response(['success' => $ok, 'mensaje' => 'Producto agregado al carrito.', 'cantidad' => cantidad_carrito($conexion, $idUsuario)]);
}

if (in_array($action, ['cambiar_cantidad', 'eliminar'], true)) {
    $id = filter_input(INPUT_POST, 'key', FILTER_VALIDATE_INT);
    if (!$id) json_response(['success' => false, 'error' => 'Ítem inválido.'], 422);

    if ($action === 'eliminar') {
        $stmt = db_prepare($conexion, 'DELETE FROM carrito WHERE id_carrito=? AND id_usuario=?');
        mysqli_stmt_bind_param($stmt, 'ii', $id, $idUsuario);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        json_response(['success' => $ok, 'cantidad' => cantidad_carrito($conexion, $idUsuario)]);
    }

    $cambio = filter_input(INPUT_POST, 'cambio', FILTER_VALIDATE_INT);
    if (!$cambio) json_response(['success' => false, 'error' => 'Cantidad inválida.'], 422);

    $stmt = db_prepare($conexion, 'SELECT c.cantidad,r.stock FROM carrito c JOIN repuestos r ON r.id_repuesto=c.id_repuesto WHERE c.id_carrito=? AND c.id_usuario=?');
    mysqli_stmt_bind_param($stmt, 'ii', $id, $idUsuario);
    mysqli_stmt_execute($stmt);
    $item = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$item) json_response(['success' => false, 'error' => 'Ítem no encontrado.'], 404);
    $nuevaCantidad = (int) $item['cantidad'] + $cambio;

    if ($nuevaCantidad <= 0) {
        $stmt = db_prepare($conexion, 'DELETE FROM carrito WHERE id_carrito=? AND id_usuario=?');
        mysqli_stmt_bind_param($stmt, 'ii', $id, $idUsuario);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } elseif ($nuevaCantidad > (int) $item['stock']) {
        json_response(['success' => false, 'error' => 'No hay stock suficiente.'], 422);
    } else {
        $stmt = db_prepare($conexion, 'UPDATE carrito SET cantidad=? WHERE id_carrito=? AND id_usuario=?');
        mysqli_stmt_bind_param($stmt, 'iii', $nuevaCantidad, $id, $idUsuario);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    json_response(['success' => $ok, 'cantidad' => cantidad_carrito($conexion, $idUsuario)]);
}

if ($action === 'limpiar') {
    $stmt = db_prepare($conexion, 'DELETE FROM carrito WHERE id_usuario=?');
    mysqli_stmt_bind_param($stmt, 'i', $idUsuario);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    json_response(['success' => $ok, 'cantidad' => 0]);
}

if ($action !== 'finalizar_compra') {
    json_response(['success' => false, 'error' => 'Acción no válida.'], 400);
}

$nombre = campo_obligatorio('nombre_completo', 'Nombre completo');
$dni = campo_obligatorio('dni', 'DNI', 30);
$telefono = campo_obligatorio('telefono', 'Teléfono', 30);
$email = campo_obligatorio('email', 'Correo electrónico');
$direccion = campo_obligatorio('direccion', 'Dirección', 180);
$ciudad = campo_obligatorio('ciudad', 'Ciudad', 100);
$provincia = campo_obligatorio('provincia', 'Provincia', 100);
$codigoPostal = campo_obligatorio('codigo_postal', 'Código postal', 20);
$metodoPago = campo_obligatorio('metodo_pago', 'Método de pago', 30);
$checkoutIntent = trim((string) ($_POST['checkout_intent'] ?? ''));

if ($metodoPago !== 'mercado_pago') {
    json_response(['success' => false, 'error' => 'Este checkout de tesis está habilitado solo para Mercado Pago en entorno de prueba.'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[0-9. \-]{6,20}$/', $dni) || !preg_match('/^[0-9 +()\-]{6,30}$/', $telefono)) {
    json_response(['success' => false, 'error' => 'Revisá el DNI, teléfono y correo electrónico.'], 422);
}
if (!preg_match('/^[a-f0-9]{64}$/', $checkoutIntent)) {
    json_response(['success' => false, 'error' => 'La operación de checkout no es válida. Volvé al carrito e intentá nuevamente.'], 422);
}

mysqli_begin_transaction($conexion);

$stmt = db_prepare($conexion, 'SELECT id,numero_operacion,estado_pago FROM pedidos WHERE cliente_id=? AND checkout_intent=? LIMIT 1 FOR UPDATE');
mysqli_stmt_bind_param($stmt, 'is', $idUsuario, $checkoutIntent);
mysqli_stmt_execute($stmt);
$pedidoExistente = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if ($pedidoExistente) {
    mysqli_commit($conexion);
    json_response([
        'success' => true,
        'mensaje' => 'La operación de prueba ya estaba generada.',
        'pedido_id' => (int) $pedidoExistente['id'],
        'numero_operacion' => (string) $pedidoExistente['numero_operacion'],
        'estado_pago' => (string) $pedidoExistente['estado_pago'],
        'url_pago' => 'checkout_mercado_pago_prueba.php?op=' . urlencode((string) $pedidoExistente['numero_operacion']),
    ]);
}

if (!hash_equals((string) ($_SESSION['checkout_intent'] ?? ''), $checkoutIntent)) {
    mysqli_rollback($conexion);
    json_response(['success' => false, 'error' => 'La sesión de checkout venció. Recargá el carrito e intentá nuevamente.'], 419);
}

$items = carrito_items($conexion, $idUsuario, true);
if (!$items) {
    mysqli_rollback($conexion);
    json_response(['success' => false, 'error' => 'El carrito está vacío.'], 422);
}

$total = 0.0;
foreach ($items as $item) {
    if ($item['cantidad'] < 1 || $item['cantidad'] > $item['stock']) {
        mysqli_rollback($conexion);
        json_response(['success' => false, 'error' => 'Stock insuficiente para ' . $item['nombre'] . '.'], 422);
    }
    $total += $item['cantidad'] * $item['precio'];
}

$numeroOperacion = numero_operacion_prueba();
$estadoPedido = 'Pendiente';
$estadoPago = 'Pendiente';
$labelMetodo = 'Mercado Pago - Entorno de prueba';

$stmt = db_prepare($conexion, 'INSERT INTO pedidos (cliente_id,total,metodo_pago,estado,nombre_completo,dni,telefono,email,direccion,ciudad,provincia,codigo_postal,numero_operacion,checkout_intent,estado_pago) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
mysqli_stmt_bind_param($stmt, 'idsssssssssssss', $idUsuario, $total, $labelMetodo, $estadoPedido, $nombre, $dni, $telefono, $email, $direccion, $ciudad, $provincia, $codigoPostal, $numeroOperacion, $checkoutIntent, $estadoPago);
$ok = mysqli_stmt_execute($stmt);
$pedidoId = (int) mysqli_insert_id($conexion);
mysqli_stmt_close($stmt);

if (!$ok || $pedidoId < 1) {
    mysqli_rollback($conexion);
    json_response(['success' => false, 'error' => 'No se pudo generar el pedido.'], 500);
}

foreach ($items as $item) {
    $subtotal = $item['cantidad'] * $item['precio'];
    $stmt = db_prepare($conexion, 'INSERT INTO detalle_pedido (pedido_id,producto_id,cantidad,precio_unitario,subtotal) VALUES (?,?,?,?,?)');
    mysqli_stmt_bind_param($stmt, 'iiidd', $pedidoId, $item['id_repuesto'], $item['cantidad'], $item['precio'], $subtotal);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    if (!$ok) {
        mysqli_rollback($conexion);
        json_response(['success' => false, 'error' => 'No se pudo guardar el detalle del pedido.'], 500);
    }
}

$titular = $nombre;
$emailPago = $email;
$metodo = 'Tarjeta de prueba';
$pais = 'Argentina';
$stmt = db_prepare($conexion, 'INSERT INTO pagos (pedido_id,id_cliente,proveedor,numero_operacion,titular,email,metodo,pais,total,monto,metodo_pago,estado,ambiente) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,"Sandbox")');
mysqli_stmt_bind_param($stmt, 'iissssssddss', $pedidoId, $idUsuario, $labelMetodo, $numeroOperacion, $titular, $emailPago, $metodo, $pais, $total, $total, $labelMetodo, $estadoPago);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$ok) {
    mysqli_rollback($conexion);
    json_response(['success' => false, 'error' => 'No se pudo registrar el intento de pago.'], 500);
}

mysqli_commit($conexion);
$_SESSION['checkout_intent'] = bin2hex(random_bytes(32));

json_response([
    'success' => true,
    'mensaje' => 'Pedido generado correctamente en modo prueba.',
    'pedido_id' => $pedidoId,
    'numero_operacion' => $numeroOperacion,
    'estado_pago' => $estadoPago,
    'url_pago' => 'checkout_mercado_pago_prueba.php?op=' . urlencode($numeroOperacion),
]);
