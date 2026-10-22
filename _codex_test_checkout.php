<?php
declare(strict_types=1);

$action = $argv[1] ?? '';
$sessionId = 'codexmispedidos';

session_save_path(__DIR__ . '/tmp_sessions');
session_id($sessionId);
session_start();
$_SESSION['id_usuario'] = 15;
$_SESSION['csrf_token'] = 'csrf-test';
$checkoutIntent = $action === 'finalizar' ? bin2hex(random_bytes(32)) : ($_SESSION['checkout_intent'] ?? bin2hex(random_bytes(32)));
$_SESSION['checkout_intent'] = $checkoutIntent;
session_write_close();

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_ACCEPT'] = 'application/json';

if ($action === 'agregar') {
    $_POST = [
        'csrf_token' => 'csrf-test',
        'action' => 'agregar',
        'id' => '1',
    ];
    require __DIR__ . '/api_carrito.php';
}

if ($action === 'finalizar') {
    $_POST = [
        'csrf_token' => 'csrf-test',
        'action' => 'finalizar_compra',
        'checkout_intent' => $checkoutIntent,
        'nombre_completo' => 'QA Digital Care',
        'dni' => '12345678',
        'telefono' => '3515551234',
        'email' => 'tesis.qa.digitalcare@example.com',
        'direccion' => 'Prueba 123',
        'ciudad' => 'Cordoba',
        'provincia' => 'Cordoba',
        'codigo_postal' => '5000',
        'metodo_pago' => 'mercado_pago',
    ];
    require __DIR__ . '/api_carrito.php';
}

if ($action === 'aprobar') {
    require __DIR__ . '/conexion.php';
    require __DIR__ . '/pagos_simulados.php';

    $operacion = $argv[2] ?? '';
    $resultado = aplicar_resultado_pago_simulado($conexion, 15, $operacion, 'aprobado', [
        'titular' => 'QA Digital Care',
        'ultimos4' => '3704',
        'tipo_tarjeta' => 'Visa',
        'documento' => '12345678',
    ]);

    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    exit;
}

fwrite(STDERR, "Accion de prueba invalida.\n");
exit(1);
