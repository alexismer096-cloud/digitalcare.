<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    json_response(['success' => false, 'error' => 'No autorizado.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
}

$actor = (int) $_SESSION['id_usuario'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

function cliente_texto(string $key, string $label, int $max = 150, bool $required = true): string
{
    $value = trim($_POST[$key] ?? '');
    if (($required && $value === '') || mb_strlen($value, 'UTF-8') > $max) {
        json_response(['success' => false, 'error' => "Completá correctamente $label."], 422);
    }
    return $value;
}

if ($action === 'historial' && $id) {
    $stmt = db_prepare($conexion, 'SELECT t.id_trabajo,t.descripcion,t.estado,t.fecha_inicio,t.costo,s.tipo_equipo,s.problema
        FROM trabajos t
        LEFT JOIN solicitudes s ON s.id_solicitud = t.id_solicitud
        WHERE t.id_usuario = ?
        ORDER BY t.fecha_inicio DESC, t.id_trabajo DESC');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_stmt_get_result($stmt);
    $html = '<div style="display:grid;gap:10px">';
    $count = 0;
    while ($row = $rows ? mysqli_fetch_assoc($rows) : null) {
        $count++;
        $html .= '<article style="border:1px solid #d7e3e8;border-radius:12px;padding:12px">';
        $html .= '<strong>#' . (int) $row['id_trabajo'] . ' - ' . e($row['tipo_equipo'] ?? 'Equipo') . '</strong>';
        $html .= '<p>' . e($row['descripcion'] ?? '') . '</p>';
        $html .= '<small>Estado: ' . e($row['estado']) . ' · Fecha: ' . e($row['fecha_inicio']) . ' · Costo: $' . number_format((float) $row['costo'], 2, ',', '.') . '</small>';
        $html .= '</article>';
    }
    $html .= $count ? '</div>' : '<p>Este cliente todavía no tiene trabajos registrados.</p>';
    mysqli_stmt_close($stmt);
    json_response(['success' => true, 'html' => $html]);
}

if ($action === 'guardar') {
    $nombre = cliente_texto('nombre', 'el nombre', 100);
    $email = strtolower(cliente_texto('email', 'el correo', 150));
    $telefono = trim($_POST['telefono'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $usuario = trim($_POST['nombre_usuario'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['success' => false, 'error' => 'El correo no es válido.'], 422);
    }

    $zero = $id ?: 0;
    $stmt = db_prepare($conexion, 'SELECT id_usuario FROM usuarios WHERE LOWER(email) = LOWER(?) AND id_usuario <> ?');
    mysqli_stmt_bind_param($stmt, 'si', $email, $zero);
    mysqli_stmt_execute($stmt);
    $dup = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($dup) {
        json_response(['success' => false, 'error' => 'Ese correo ya está registrado.'], 422);
    }

    if ($id) {
        $stmt = db_prepare($conexion, 'UPDATE usuarios SET nombre = ?, apellido = ?, nombre_usuario = ?, email = ?, telefono = ?, direccion = ? WHERE id_usuario = ?');
        mysqli_stmt_bind_param($stmt, 'ssssssi', $nombre, $apellido, $usuario, $email, $telefono, $direccion, $id);
    } else {
        $clave = $_POST['password'] ?? '';
        if (strlen($clave) < 8) {
            json_response(['success' => false, 'error' => 'La contraseña inicial debe tener 8 caracteres.'], 422);
        }
        $hash = password_hash($clave, PASSWORD_DEFAULT);
        $stmt = db_prepare($conexion, 'INSERT INTO usuarios (nombre, apellido, nombre_usuario, email, telefono, direccion, password) VALUES (?, ?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'sssssss', $nombre, $apellido, $usuario, $email, $telefono, $direccion, $hash);
    }

    $ok = mysqli_stmt_execute($stmt);
    $error = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    json_response(['success' => $ok, 'mensaje' => $id ? 'Cliente actualizado.' : 'Cliente creado.', 'error' => $ok ? '' : $error]);
}

if ($action === 'eliminar' && $id) {
    if ($id === $actor) {
        json_response(['success' => false, 'error' => 'No podés eliminar tu propia cuenta.'], 422);
    }

    $stmt = db_prepare($conexion, 'SELECT COUNT(*) total FROM trabajos WHERE id_usuario = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $trabajos = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0);
    mysqli_stmt_close($stmt);

    if ($trabajos > 0) {
        json_response(['success' => false, 'error' => 'No se puede eliminar el cliente porque tiene trabajos asociados. Revisá su historial antes de borrarlo.'], 422);
    }

    $stmt = db_prepare($conexion, 'DELETE FROM usuarios WHERE id_usuario = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    $error = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    json_response(['success' => $ok, 'mensaje' => 'Cliente eliminado.', 'error' => $ok ? '' : ($error ?: 'No se pudo eliminar el cliente porque tiene registros asociados.')]);
}

json_response(['success' => false, 'error' => 'Acción inválida.'], 400);
