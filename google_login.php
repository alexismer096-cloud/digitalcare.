<?php
require_once 'auth.php';
secure_session_start();
require_once 'conexion.php';

$clientId = env_value('GOOGLE_CLIENT_ID');
$redirectUri = env_value('GOOGLE_REDIRECT_URI', app_base_url() . '/google_callback.php');

if (!$clientId || !str_ends_with($clientId, '.apps.googleusercontent.com')) {
    header('Location: index.php');
    exit;
    redirect_with_message('index.php', 'Google OAuth no esta configurado. Completá GOOGLE_CLIENT_ID en .env.');
}

$_SESSION['google_oauth_state'] = bin2hex(random_bytes(24));
$_SESSION['google_oauth_nonce'] = bin2hex(random_bytes(24));

$params = [
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $_SESSION['google_oauth_state'],
    'nonce' => $_SESSION['google_oauth_nonce'],
    'prompt' => 'select_account',
    'access_type' => 'online',
];

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
exit;
?>
