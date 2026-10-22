<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    json_response(['success' => false, 'error' => 'No autorizado'], 401);
}
verify_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Método no permitido'], 405);
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$cambio = filter_input(INPUT_POST, 'cambio', FILTER_VALIDATE_INT);

if (!$id || $id < 1 || $cambio === false || $cambio === null || $cambio === 0) {
    json_response(['success' => false, 'error' => 'Datos de stock inválidos'], 422);
}

mysqli_begin_transaction($conexion);

$stmt = db_prepare($conexion, 'SELECT nombre, stock, stock_minimo FROM repuestos WHERE id_repuesto = ? FOR UPDATE');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$row) {
    mysqli_rollback($conexion);
    json_response(['success' => false, 'error' => 'Repuesto no encontrado'], 404);
}

$nuevoStock = (int) $row['stock'] + $cambio;
$stockMinimo = (int) ($row['stock_minimo'] ?? 5);

if ($nuevoStock < 0) {
    mysqli_rollback($conexion);
    json_response(['success' => false, 'error' => 'No hay stock suficiente para esa baja'], 422);
}

$stmt = db_prepare($conexion, 'UPDATE repuestos SET stock = ? WHERE id_repuesto = ?');
mysqli_stmt_bind_param($stmt, 'ii', $nuevoStock, $id);
$updated = mysqli_stmt_execute($stmt);
$error = mysqli_stmt_error($stmt);
mysqli_stmt_close($stmt);

if (!$updated) {
    mysqli_rollback($conexion);
    json_response(['success' => false, 'error' => $error ?: 'No se pudo actualizar el stock'], 500);
}

if ($nuevoStock <= $stockMinimo) {
    $mensaje = 'Stock crítico: ' . ($row['nombre'] ?? 'Repuesto') . ' quedó en ' . $nuevoStock . ' unidades.';
    $stmt = db_prepare($conexion, 'INSERT INTO alertas_stock (producto_id, mensaje, estado) VALUES (?, ?, "Pendiente")');
    mysqli_stmt_bind_param($stmt, 'is', $id, $mensaje);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

mysqli_commit($conexion);
json_response(['success' => true, 'stock' => $nuevoStock, 'alerta' => $nuevoStock <= $stockMinimo]);
