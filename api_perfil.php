<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
require_once 'auth.php';
secure_session_start();
require_once 'conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    json_response(['success' => false, 'error' => 'No autorizado.'], 401);
}

verify_csrf();
$id = (int) $_SESSION['id_usuario'];
$action = $_POST['action'] ?? '';

if ($action === 'guardar') {
    $nombre = trim($_POST['nombre'] ?? '');
    if ($nombre === '') json_response(['success' => false, 'error' => 'El nombre es obligatorio.'], 422);
    $apellido = trim($_POST['apellido'] ?? '');
    $usuario = trim($_POST['nombre_usuario'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $provincia = trim($_POST['provincia'] ?? '');
    $pais = trim($_POST['pais'] ?? '');
    $foto = trim($_POST['foto_perfil'] ?? '');
    foreach ([$nombre, $apellido, $usuario, $telefono, $dni, $direccion, $ciudad, $provincia, $pais, $foto] as $value) {
        if (mb_strlen($value, 'UTF-8') > 255) {
            json_response(['success' => false, 'error' => 'Un campo supera el maximo permitido.'], 422);
        }
    }
    $stmt = db_prepare($conexion, 'UPDATE usuarios SET nombre=?,apellido=?,nombre_usuario=?,telefono=?,dni=?,fecha_nacimiento=NULLIF(?,""),direccion=?,ciudad=?,provincia=?,pais=?,foto_perfil=? WHERE id_usuario=?');
    mysqli_stmt_bind_param($stmt, 'sssssssssssi', $nombre, $apellido, $usuario, $telefono, $dni, $nacimiento, $direccion, $ciudad, $provincia, $pais, $foto, $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    if ($ok) $_SESSION['nombre'] = $nombre;
    json_response(['success' => $ok, 'mensaje' => 'Perfil actualizado correctamente.']);
}

if ($action === 'password') {
    $actual = $_POST['actual'] ?? '';
    $nueva = $_POST['nueva'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';
    if ($actual === '' || $nueva === '' || $confirmar === '') {
        json_response(['success' => false, 'error' => 'Completa todos los campos de seguridad.'], 422);
    }
    if ($nueva !== $confirmar) {
        json_response(['success' => false, 'error' => 'Las contrasenas no coinciden.'], 422);
    }
    $errors = password_policy_errors($nueva);
    if ($errors) {
        json_response(['success' => false, 'error' => 'La contrasena debe incluir ' . implode(', ', $errors) . '.'], 422);
    }
    $stmt = db_prepare($conexion, 'SELECT nombre, email, password, session_version FROM usuarios WHERE id_usuario=? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$row || !password_verify($actual, $row['password'])) {
        json_response(['success' => false, 'error' => 'La contrasena actual no coincide.'], 422);
    }
    if (password_verify($nueva, $row['password'])) {
        json_response(['success' => false, 'error' => 'La nueva contrasena debe ser diferente a la actual.'], 422);
    }
    $hash = password_hash($nueva, PASSWORD_DEFAULT);
    $stmt = db_prepare($conexion, 'UPDATE usuarios SET password=?, password_changed_at=NOW(), session_version=session_version+1 WHERE id_usuario=?');
    mysqli_stmt_bind_param($stmt, 'si', $hash, $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    if ($ok) {
        $_SESSION['session_version'] = (int) ($row['session_version'] ?? 1) + 1;
        session_regenerate_id(true);
        send_password_changed_notice((string) $row['email'], (string) $row['nombre']);
    }
    json_response(['success' => $ok, 'mensaje' => 'Contrasena actualizada correctamente.']);
}

json_response(['success' => false, 'error' => 'Accion invalida.'], 400);
?>
