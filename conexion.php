<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_OFF);

$serverName = $_SERVER['SERVER_NAME'] ?? '';
$isLocal = PHP_SAPI === 'cli' || in_array($serverName, ['localhost', '127.0.0.1', '::1'], true);

// XAMPP no puede acceder a las bases de InfinityFree: usa MariaDB local.
// Las variables de entorno permiten sobrescribir estos datos en cualquier entorno.
$host = getenv('DB_HOST') ?: ($isLocal ? '127.0.0.1' : 'sql200.infinityfree.com');
$user = getenv('DB_USERNAME') ?: ($isLocal ? 'root' : 'if0_42547278');
$password = getenv('DB_PASSWORD') ?: ($isLocal ? '' : 'pechuga123123');
$db = getenv('DB_DATABASE') ?: ($isLocal ? 'digital_care' : 'if0_42547278_alexis');
$port = (int) (getenv('DB_PORT') ?: 3306);

$conexion = mysqli_connect($host, $user, $password, $db, $port);

if (!$conexion) {
    http_response_code(500);
    die('No se pudo conectar a la base de datos: ' . mysqli_connect_error());
}

if (!mysqli_set_charset($conexion, "utf8mb4")) {
    http_response_code(500);
    die("No se pudo configurar UTF-8 en la base de datos.");
}

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('redirect_with_message')) {
    function redirect_with_message(string $url, string $message, string $type = 'error'): void
    {
        $separator = str_contains($url, '?') ? '&' : '?';
        header('Location: ' . $url . $separator . http_build_query([
            'msg' => $message,
            'type' => $type,
        ]));
        exit;
    }
}

if (!function_exists('json_response')) {
    function json_response(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_input')) {
    function csrf_input(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('csrf_meta')) {
    function csrf_meta(): string
    {
        return '<meta name="csrf-token" content="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            if (!headers_sent() && str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
                json_response(['success' => false, 'error' => 'Token de seguridad invalido. Recarga la pagina.'], 419);
            }
            http_response_code(419);
            die('Token de seguridad invalido. Recarga la pagina.');
        }
    }
}

if (!function_exists('db_prepare')) {
    function db_prepare(mysqli $conexion, string $sql): mysqli_stmt
    {
        $stmt = mysqli_prepare($conexion, $sql);
        if ($stmt instanceof mysqli_stmt) {
            return $stmt;
        }

        $message = 'Error SQL al preparar consulta: ' . mysqli_error($conexion) . ' | Consulta: ' . $sql;
        error_log($message);

        if (!headers_sent() && str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            json_response(['success' => false, 'error' => $message], 500);
        }

        http_response_code(500);
        die(e($message));
    }
}
?>
