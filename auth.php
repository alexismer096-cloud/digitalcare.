<?php
declare(strict_types=1);

if (!function_exists('load_env_file')) {
    function load_env_file(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $key) || getenv($key) !== false) {
                continue;
            }
            if (strlen($value) >= 2 && $value[0] === $value[strlen($value) - 1] && in_array($value[0], ['"', "'"], true)) {
                $value = substr($value, 1, -1);
            }
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

load_env_file(__DIR__ . DIRECTORY_SEPARATOR . '.env');

if (!function_exists('env_value')) {
    function env_value(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);
        if ($value === false && isset($_ENV[$key])) {
            $value = $_ENV[$key];
        }
        return $value === false || $value === '' ? $default : (string) $value;
    }
}

if (!function_exists('secure_session_start')) {
    function secure_session_start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $savePath = session_save_path();
        $savePath = $savePath !== '' ? $savePath : sys_get_temp_dir();
        if (!is_dir($savePath) || !is_writable($savePath)) {
            $localPath = __DIR__ . DIRECTORY_SEPARATOR . 'tmp_sessions';
            if (!is_dir($localPath)) {
                @mkdir($localPath, 0775, true);
            }
            if (is_dir($localPath) && is_writable($localPath)) {
                session_save_path($localPath);
            }
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

if (!function_exists('password_policy_errors')) {
    function password_policy_errors(string $password): array
    {
        $errors = [];
        if (strlen($password) < 8) $errors[] = '8 caracteres';
        if (!preg_match('/[A-Z]/', $password)) $errors[] = 'una letra mayuscula';
        if (!preg_match('/[a-z]/', $password)) $errors[] = 'una letra minuscula';
        if (!preg_match('/[0-9]/', $password)) $errors[] = 'un numero';
        if (!preg_match('/[!@#$%&*?]/', $password)) $errors[] = 'un caracter especial (! @ # $ % & * ?)';
        return $errors;
    }
}

if (!function_exists('password_policy_valid')) {
    function password_policy_valid(string $password): bool
    {
        return password_policy_errors($password) === [];
    }
}

if (!function_exists('app_base_url')) {
    function app_base_url(): string
    {
        $configured = env_value('APP_URL');
        if ($configured) {
            return rtrim($configured, '/');
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443');
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        return ($https ? 'https://' : 'http://') . $host . ($dir === '' ? '' : $dir);
    }
}

if (!function_exists('send_digital_care_mail')) {
    function send_digital_care_mail(string $to, string $subject, string $html, string $text = ''): bool
    {
        $from = env_value('SMTP_FROM', env_value('MAIL_FROM', 'no-reply@digital-care.local'));
        $fromName = env_value('SMTP_FROM_NAME', 'Digital Care');
        $host = env_value('SMTP_HOST');
        if ($host) {
            return send_smtp_mail($to, $subject, $html, $text, $from, $fromName);
        }
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $fromName . ' <' . $from . '>',
        ];
        return @mail($to, $subject, $html, implode("\r\n", $headers));
    }
}

if (!function_exists('send_smtp_mail')) {
    function send_smtp_mail(string $to, string $subject, string $html, string $text, string $from, string $fromName): bool
    {
        $host = env_value('SMTP_HOST', '');
        $port = (int) env_value('SMTP_PORT', '587');
        $user = env_value('SMTP_USERNAME');
        $pass = env_value('SMTP_PASSWORD');
        $secure = strtolower(env_value('SMTP_SECURE', $port === 465 ? 'ssl' : 'tls'));
        $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $socket = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
        if (!$socket) {
            error_log('SMTP connection failed: ' . $errstr);
            return false;
        }
        stream_set_timeout($socket, 15);
        $read = fn() => fgets($socket, 1024);
        $cmd = function (string $line) use ($socket, $read): string {
            fwrite($socket, $line . "\r\n");
            $response = '';
            do {
                $part = $read();
                $response .= (string) $part;
            } while (is_string($part) && strlen($part) > 3 && $part[3] === '-');
            return $response;
        };
        $read();
        $cmd('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'digital-care.local'));
        if ($secure === 'tls') {
            $cmd('STARTTLS');
            if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                return false;
            }
            $cmd('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'digital-care.local'));
        }
        if ($user && $pass) {
            $cmd('AUTH LOGIN');
            $cmd(base64_encode($user));
            $cmd(base64_encode($pass));
        }
        $boundary = 'dc_' . bin2hex(random_bytes(12));
        $safeSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $body = "From: $fromName <$from>\r\nTo: <$to>\r\nSubject: $safeSubject\r\nMIME-Version: 1.0\r\nContent-Type: multipart/alternative; boundary=\"$boundary\"\r\n\r\n";
        $body .= "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n" . ($text ?: strip_tags($html)) . "\r\n";
        $body .= "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n$html\r\n--$boundary--\r\n.";
        $cmd('MAIL FROM:<' . $from . '>');
        $cmd('RCPT TO:<' . $to . '>');
        $cmd('DATA');
        $cmd($body);
        $cmd('QUIT');
        fclose($socket);
        return true;
    }
}

if (!function_exists('auth_login_user')) {
    function auth_login_user(mysqli $conexion, array $usuario): void
    {
        secure_session_start();
        session_regenerate_id(true);
        $_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
        $_SESSION['nombre'] = (string) $usuario['nombre'];
        $_SESSION['session_version'] = (int) ($usuario['session_version'] ?? 1);
        $stmt = db_prepare($conexion, 'UPDATE usuarios SET last_login = NOW() WHERE id_usuario = ?');
        $id = (int) $usuario['id_usuario'];
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('auth_validate_active_session')) {
    function auth_validate_active_session(mysqli $conexion): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['id_usuario'])) {
            return;
        }
        $id = (int) $_SESSION['id_usuario'];
        $stmt = mysqli_prepare($conexion, 'SELECT session_version FROM usuarios WHERE id_usuario = ? LIMIT 1');
        if (!$stmt) return;
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$row || (int) ($row['session_version'] ?? 1) !== (int) ($_SESSION['session_version'] ?? 0)) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
            header('Location: index.php?msg=' . urlencode('Tu sesion caduco por seguridad. Inicia sesion nuevamente.') . '&type=error');
            exit;
        }
    }
}

if (!function_exists('send_password_changed_notice')) {
    function send_password_changed_notice(string $email, string $name): void
    {
        $html = '<div style="font-family:Arial,sans-serif;color:#1f3762;line-height:1.5"><h2>Digital Care</h2><h3>Tu contrasena fue modificada</h3><p>Hola ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ', tu contrasena de Digital Care fue modificada correctamente.</p><p>Si no realizaste este cambio, contacta al administrador mediante los canales disponibles.</p></div>';
        send_digital_care_mail($email, 'Tu contrasena de Digital Care fue modificada', $html);
    }
}
?>
