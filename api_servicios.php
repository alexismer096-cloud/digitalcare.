<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    json_response(['success' => false, 'error' => 'No autorizado.'], 401);
}
verify_csrf();

$action = $_POST['action'] ?? '';
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

function servicio_campo(string $key, string $label, int $max = 255): string
{
    $value = trim($_POST[$key] ?? '');
    if ($value === '' || mb_strlen($value, 'UTF-8') > $max) {
        json_response(['success' => false, 'error' => "Completá correctamente $label."], 422);
    }
    return $value;
}

if ($action === 'guardar') {
    $nombre = servicio_campo('nombre', 'el nombre', 150);
    $descripcion = servicio_campo('descripcion', 'la descripción', 2000);
    $categoria = servicio_campo('categoria', 'la categoría', 100);
    $tiempo = servicio_campo('tiempo_estimado', 'el tiempo estimado', 80);
    $precio = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
    $estado = $_POST['estado'] ?? 'Activo';
    $imagen = trim($_POST['imagen'] ?? '');

    if ($precio === false || $precio === null || $precio < 0 || !in_array($estado, ['Activo', 'Inactivo'], true)) {
        json_response(['success' => false, 'error' => 'Revisá el precio y el estado.'], 422);
    }

    if ($id) {
        $stmt = db_prepare($conexion, 'UPDATE servicios SET nombre = ?, descripcion = ?, categoria = ?, precio = ?, tiempo_estimado = ?, estado = ?, imagen = ? WHERE id_servicio = ?');
        mysqli_stmt_bind_param($stmt, 'sssdsssi', $nombre, $descripcion, $categoria, $precio, $tiempo, $estado, $imagen, $id);
    } else {
        $stmt = db_prepare($conexion, 'INSERT INTO servicios (nombre, descripcion, categoria, precio, tiempo_estimado, estado, imagen) VALUES (?, ?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'sssdsss', $nombre, $descripcion, $categoria, $precio, $tiempo, $estado, $imagen);
    }

    $ok = mysqli_stmt_execute($stmt);
    $error = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    json_response(['success' => $ok, 'mensaje' => $id ? 'Servicio actualizado.' : 'Servicio creado.', 'error' => $ok ? '' : $error]);
}

if ($action === 'eliminar' && $id) {
    $stmt = db_prepare($conexion, 'DELETE FROM servicios WHERE id_servicio = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    $error = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    json_response(['success' => $ok, 'error' => $ok ? '' : ($error ?: 'No se puede eliminar un servicio asociado a un trabajo.')]);
}

if ($action === 'estado' && $id) {
    $estado = $_POST['estado'] ?? '';
    if (!in_array($estado, ['Activo', 'Inactivo'], true)) {
        json_response(['success' => false, 'error' => 'Estado inválido.'], 422);
    }
    $stmt = db_prepare($conexion, 'UPDATE servicios SET estado = ? WHERE id_servicio = ?');
    mysqli_stmt_bind_param($stmt, 'si', $estado, $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    json_response(['success' => $ok]);
}

json_response(['success' => false, 'error' => 'Acción inválida.'], 400);
