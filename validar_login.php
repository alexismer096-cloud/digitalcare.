<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
require_once 'auth.php';
require_once 'conexion.php';
secure_session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    redirect_with_message('index.php', 'Ingresá un correo válido y tu contraseña.');
}

$stmt = mysqli_prepare($conexion, "SELECT id_usuario, nombre, password, session_version FROM usuarios WHERE LOWER(email) = LOWER(?) LIMIT 1");

if (!$stmt) {
    redirect_with_message('index.php', 'No se pudo validar el usuario. Intentá nuevamente.');
}

mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$usuario = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$usuario || !password_verify($password, $usuario['password'])) {
    redirect_with_message('index.php', 'Correo o contraseña incorrectos.');
}

auth_login_user($conexion, $usuario);

header('Location: index.php?view=dashboard');
exit;
?>
