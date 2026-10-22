<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
require_once 'auth.php';
secure_session_start();
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Método no permitido'], 405);
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    json_response(['success' => false, 'message' => 'JSON inválido.'], 400);
}

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    json_response(['success' => false, 'message' => 'Ingresá un correo válido y tu contraseña.'], 422);
}

$stmt = mysqli_prepare($conexion, "SELECT id_usuario, nombre, password, session_version FROM usuarios WHERE LOWER(email) = LOWER(?) LIMIT 1");

if (!$stmt) {
    json_response(['success' => false, 'message' => 'No se pudo preparar la consulta.'], 500);
}

mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$usuario = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$usuario || !password_verify($password, $usuario['password'])) {
    json_response(['success' => false, 'message' => 'Correo o contraseña incorrectos.'], 401);
}

auth_login_user($conexion, $usuario);

json_response([
    'success' => true,
    'user' => [
        'id_usuario' => (int) $usuario['id_usuario'],
        'nombre' => $usuario['nombre'],
    ],
]);
?>
