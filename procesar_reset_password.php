<?php
require_once 'conexion.php';
secure_session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: recuperar.php');
    exit;
}

verify_csrf();

$token = trim($_POST['token'] ?? '');
$password = $_POST['password'] ?? '';
$confirmar = $_POST['confirmar'] ?? '';

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    redirect_with_message('recuperar.php', 'El enlace no es valido o expiro.');
}
if ($password !== $confirmar) {
    redirect_with_message('reset_password.php?token=' . urlencode($token), 'Las contrasenas no coinciden.');
}
$errors = password_policy_errors($password);
if ($errors) {
    redirect_with_message('reset_password.php?token=' . urlencode($token), 'La contrasena debe incluir ' . implode(', ', $errors) . '.');
}

$tokenHash = hash('sha256', $token);
$stmt = db_prepare($conexion, "SELECT id_usuario, nombre, email FROM usuarios WHERE reset_token_hash = ? AND reset_token_used_at IS NULL AND reset_token_expires_at > NOW() LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $tokenHash);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$user) {
    redirect_with_message('recuperar.php', 'El enlace no es valido o expiro.');
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$id = (int) $user['id_usuario'];
$stmt = db_prepare($conexion, "UPDATE usuarios
    SET password = ?, reset_token_used_at = NOW(), reset_token_hash = NULL, reset_token_expires_at = NULL,
        password_changed_at = NOW(), auth_provider = IF(auth_provider = 'google', 'google', 'local'),
        session_version = session_version + 1
    WHERE id_usuario = ?");
mysqli_stmt_bind_param($stmt, 'si', $hash, $id);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$ok) {
    redirect_with_message('reset_password.php?token=' . urlencode($token), 'No se pudo actualizar la contrasena.');
}

send_password_changed_notice((string) $user['email'], (string) $user['nombre']);
redirect_with_message('index.php', 'Contrasena actualizada correctamente.', 'success');
?>
