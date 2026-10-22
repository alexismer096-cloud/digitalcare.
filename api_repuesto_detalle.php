<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    json_response(['success' => false, 'error' => 'No autorizado'], 401);
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    json_response(['success' => false, 'error' => 'ID inválido'], 422);
}

$stmt = mysqli_prepare($conexion, "SELECT id_repuesto, nombre, descripcion, categoria, imagen, stock, precio FROM repuestos WHERE id_repuesto = ? LIMIT 1");

if (!$stmt) {
    json_response(['success' => false, 'error' => 'No se pudo preparar la consulta'], 500);
}

mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$repuesto = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$repuesto) {
    json_response(['success' => false, 'error' => 'Repuesto no encontrado'], 404);
}

$repuesto['id_repuesto'] = (int) $repuesto['id_repuesto'];
$repuesto['stock'] = (int) $repuesto['stock'];
$repuesto['precio'] = (float) $repuesto['precio'];

json_response(['success' => true, 'repuesto' => $repuesto]);
?>
