<?php
require_once 'conexion.php';
secure_session_start();

$generic = 'Si el correo esta registrado, recibiras instrucciones para restablecer tu contrasena.';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: recuperar.php');
    exit;
}

verify_csrf();

$email = strtolower(trim($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
    redirect_with_message('recuperar.php', $generic, 'success');
}

$emailHash = hash('sha256', $email);
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ipHash = hash('sha256', $ip);
$stmt = db_prepare($conexion, "DELETE FROM password_reset_requests WHERE requested_at < (NOW() - INTERVAL 1 DAY)");
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

$stmt = db_prepare($conexion, "SELECT
    SUM(email_hash = ? AND requested_at > (NOW() - INTERVAL 1 HOUR)) AS email_count,
    SUM(ip_hash = ? AND requested_at > (NOW() - INTERVAL 1 HOUR)) AS ip_count
    FROM password_reset_requests");
mysqli_stmt_bind_param($stmt, 'ss', $emailHash, $ipHash);
mysqli_stmt_execute($stmt);
$limits = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: ['email_count' => 0, 'ip_count' => 0];
mysqli_stmt_close($stmt);

$stmt = db_prepare($conexion, "INSERT INTO password_reset_requests (email_hash, ip_hash) VALUES (?, ?)");
mysqli_stmt_bind_param($stmt, 'ss', $emailHash, $ipHash);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ((int) $limits['email_count'] >= 5 || (int) $limits['ip_count'] >= 20) {
    redirect_with_message('recuperar.php', $generic, 'success');
}

$stmt = db_prepare($conexion, "SELECT id_usuario, nombre, email FROM usuarios WHERE LOWER(email) = LOWER(?) LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if ($user) {
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $stmt = db_prepare($conexion, "UPDATE usuarios
        SET reset_token_hash = ?, reset_token_expires_at = (NOW() + INTERVAL 30 MINUTE), reset_token_used_at = NULL
        WHERE id_usuario = ?");
    $id = (int) $user['id_usuario'];
    mysqli_stmt_bind_param($stmt, 'si', $tokenHash, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $link = app_base_url() . '/reset_password.php?token=' . urlencode($token);
    $safeName = htmlspecialchars((string) $user['nombre'], ENT_QUOTES, 'UTF-8');
    $html = '<div style="font-family:Arial,sans-serif;color:#1f3762;line-height:1.5;max-width:560px">'
        . '<h2 style="color:#1f4f95">Digital <span style="color:#16a49a">Care</span></h2>'
        . '<h3>Solicitud de recuperacion de contrasena</h3>'
        . '<p>Hola ' . $safeName . ', recibimos una solicitud para restablecer tu contrasena.</p>'
        . '<p>Si realizaste esta solicitud, utiliza el boton para establecer una nueva contrasena.</p>'
        . '<p><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;background:#2397dd;color:#fff;text-decoration:none;border-radius:999px;padding:12px 18px;font-weight:bold">Establecer nueva contrasena</a></p>'
        . '<p>Este enlace es temporal y dejara de funcionar despues de 30 minutos. Si no solicitaste este cambio, ignora este correo.</p>'
        . '</div>';
    send_digital_care_mail((string) $user['email'], 'Solicitud de recuperacion de contrasena', $html);
}

redirect_with_message('recuperar.php', $generic, 'success');
?>
