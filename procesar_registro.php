<?php
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registro.php');
    exit;
}

verify_csrf();

$nombre = trim($_POST['nombre'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$confirmar = $_POST['confirmar'] ?? '';

if ($nombre === '' || strlen($nombre) > 100) {
    redirect_with_message('registro.php', 'Ingresa un nombre valido.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
    redirect_with_message('registro.php', 'Ingresa un correo electronico valido.');
}

if ($password !== $confirmar) {
    redirect_with_message('registro.php', 'Las contrasenas no coinciden.');
}

$passwordErrors = password_policy_errors($password);
if ($passwordErrors) {
    redirect_with_message('registro.php', 'La contrasena debe incluir ' . implode(', ', $passwordErrors) . '.');
}

$stmt = db_prepare($conexion, "SELECT id_usuario FROM usuarios WHERE LOWER(email) = LOWER(?) LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$exists = $result && mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($exists) {
    redirect_with_message('registro.php', 'El correo ya esta registrado.');
}

$passwordHashed = password_hash($password, PASSWORD_DEFAULT);
$stmt = db_prepare($conexion, "INSERT INTO usuarios (nombre, email, password, password_changed_at) VALUES (?, ?, ?, NOW())");
mysqli_stmt_bind_param($stmt, 'sss', $nombre, $email, $passwordHashed);
$saved = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$saved) {
    redirect_with_message('registro.php', 'No se pudo crear la cuenta. Revisa los datos e intenta nuevamente.');
}

redirect_with_message('index.php', 'Cuenta creada correctamente. Inicia sesion.', 'success');
?>
