<?php
require_once 'auth.php';
secure_session_start();
require_once 'conexion.php';

if (!hash_equals($_SESSION['google_oauth_state'] ?? '', $_GET['state'] ?? '')) {
    redirect_with_message('index.php', 'No se pudo validar la solicitud de Google.');
}

$code = $_GET['code'] ?? '';
if ($code === '') {
    redirect_with_message('index.php', 'Google no devolvio un codigo valido.');
}

$clientId = env_value('GOOGLE_CLIENT_ID');
$clientSecret = env_value('GOOGLE_CLIENT_SECRET');
$redirectUri = env_value('GOOGLE_REDIRECT_URI', app_base_url() . '/google_callback.php');
if (!$clientId || !$clientSecret || !str_ends_with($clientId, '.apps.googleusercontent.com')) {
    redirect_with_message('index.php', 'Google OAuth no esta configurado. Completá las credenciales en .env.');
}

$tokenResponse = google_http_post('https://oauth2.googleapis.com/token', [
    'code' => $code,
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'redirect_uri' => $redirectUri,
    'grant_type' => 'authorization_code',
]);
$tokens = json_decode($tokenResponse, true);
if (!is_array($tokens) || empty($tokens['id_token'])) {
    redirect_with_message('index.php', 'No se pudo iniciar sesion con Google.');
}

$claimsJson = @file_get_contents('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($tokens['id_token']));
$claims = json_decode((string) $claimsJson, true);
if (!is_array($claims)
    || !in_array(($claims['iss'] ?? ''), ['https://accounts.google.com', 'accounts.google.com'], true)
    || ($claims['aud'] ?? '') !== $clientId
    || (int) ($claims['exp'] ?? 0) < time()
    || !hash_equals($_SESSION['google_oauth_nonce'] ?? '', $claims['nonce'] ?? '')
    || ($claims['email_verified'] ?? '') !== 'true') {
    redirect_with_message('index.php', 'Google no pudo verificar tu identidad.');
}

$googleId = (string) ($claims['sub'] ?? '');
$email = strtolower((string) ($claims['email'] ?? ''));
$name = trim((string) ($claims['name'] ?? 'Usuario Google'));
if ($googleId === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_with_message('index.php', 'Google no devolvio una identidad valida.');
}

$stmt = db_prepare($conexion, 'SELECT id_usuario, nombre, email, google_id, session_version FROM usuarios WHERE google_id = ? OR LOWER(email) = LOWER(?) LIMIT 1');
mysqli_stmt_bind_param($stmt, 'ss', $googleId, $email);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if ($user) {
    if (!empty($user['google_id']) && !hash_equals((string) $user['google_id'], $googleId)) {
        redirect_with_message('index.php', 'La cuenta ya esta vinculada a otra identidad de Google.');
    }
    if (empty($user['google_id'])) {
        $id = (int) $user['id_usuario'];
        $stmt = db_prepare($conexion, "UPDATE usuarios SET google_id = ?, auth_provider = 'local_google' WHERE id_usuario = ?");
        mysqli_stmt_bind_param($stmt, 'si', $googleId, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
} else {
    $disabledPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
    $stmt = db_prepare($conexion, "INSERT INTO usuarios (nombre, email, password, google_id, auth_provider) VALUES (?, ?, ?, ?, 'google')");
    mysqli_stmt_bind_param($stmt, 'ssss', $name, $email, $disabledPassword, $googleId);
    mysqli_stmt_execute($stmt);
    $id = mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);
    $user = ['id_usuario' => $id, 'nombre' => $name, 'email' => $email, 'session_version' => 1];
}

unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_nonce']);
auth_login_user($conexion, $user);
header('Location: index.php?view=dashboard');
exit;

function google_http_post(string $url, array $data): string
{
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data),
            'timeout' => 15,
        ],
    ]);
    return (string) @file_get_contents($url, false, $context);
}
?>
