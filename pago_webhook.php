<?php
declare(strict_types=1);

/* Webhook deshabilitado a propósito: Digital Care usa Mercado Pago en modo sandbox/mock para la tesis. */
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');

require_once 'conexion.php';

http_response_code(200);
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => true,
    'message' => 'Webhook real deshabilitado. Los pagos se simulan en checkout_mercado_pago_prueba.php.',
], JSON_UNESCAPED_UNICODE);
