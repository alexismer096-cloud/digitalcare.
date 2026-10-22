<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');

session_start();
require_once 'conexion.php';
require_once 'database_schema.php';
require_once 'layout.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location:index.php');
    exit;
}

ensure_digitalcare_shop_schema($conexion);

$operacion = trim((string) ($_GET['op'] ?? $_GET['external_reference'] ?? $_GET['orden'] ?? ''));
$pedido = null;
if ($operacion !== '') {
    $usuario = (int) $_SESSION['id_usuario'];
    $stmt = db_prepare($conexion, 'SELECT p.id,p.estado,p.estado_pago,p.total,pg.referencia_proveedor FROM pedidos p JOIN pagos pg ON pg.pedido_id=p.id WHERE p.cliente_id=? AND pg.numero_operacion=? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'is', $usuario, $operacion);
    mysqli_stmt_execute($stmt);
    $pedido = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Resultado de pago | Digital Care</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<main class="dashboard-page">
  <?php digital_care_header($conexion); ?>
  <section class="dashboard-content">
    <article class="module-view payment-result">
      <h2>Resultado del pago de prueba</h2>
      <?php if ($pedido): ?>
        <p>Estado registrado: <b><?= e($pedido['estado_pago']) ?></b></p>
        <p><b>Pedido:</b> #<?= (int) $pedido['id'] ?> · <b>Operación:</b> <?= e($operacion) ?></p>
        <p class="test-warning">ENTORNO DE PRUEBA - No se realizó ningún cobro real.</p>
      <?php else: ?>
        <p>No encontramos una operación de prueba para mostrar.</p>
      <?php endif; ?>
      <a href="mis_pedidos.php">Ver mis pedidos</a>
    </article>
  </section>
</main>
<style>
  .payment-result{text-align:center;padding:55px}.payment-result h2{color:#1e4e95}.payment-result p{color:#576f78}.payment-result a{display:inline-block;margin-top:15px;background:linear-gradient(90deg,#2397dd,#2a65de);color:#fff;padding:12px 22px;border-radius:999px;text-decoration:none;font-weight:800}.test-warning{background:#fff4d8;border:1px solid #e8bd55;color:#8a5b00;border-radius:8px;padding:10px 12px;font-weight:800}
</style>
</body>
</html>
