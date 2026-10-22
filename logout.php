<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
require_once 'auth.php';
secure_session_start();
// Destruir todas las variables de sesión
$_SESSION = array();

// Si se desea destruir la sesión completamente, también hay que borrar la cookie de sesión.
// Nota: ¡Esto destruirá la sesión, y no solo los datos de la sesión!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}

// Finalmente, destruir la sesión.
session_destroy();

// Redirigir al index (login)
header("Location: index.php");
exit;
?>
