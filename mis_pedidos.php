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

$usuario = (int) $_SESSION['id_usuario'];
$stmt = db_prepare($conexion, 'SELECT p.*,pg.estado pago_estado,pg.referencia_proveedor,pg.tarjeta_ultimos4,pg.tipo_tarjeta,pg.ambiente,det.productos
    FROM pedidos p
    LEFT JOIN pagos pg ON pg.pedido_id = p.id
    LEFT JOIN (
        SELECT d.pedido_id,
            GROUP_CONCAT(CONCAT(r.nombre," - Cantidad: ",d.cantidad," - $",FORMAT(d.precio_unitario,2)," c/u - Subtotal $",FORMAT(d.subtotal,2)) ORDER BY d.id SEPARATOR "|") productos
        FROM detalle_pedido d
        JOIN repuestos r ON r.id_repuesto = d.producto_id
        GROUP BY d.pedido_id
    ) det ON det.pedido_id = p.id
    WHERE p.cliente_id = ?
    ORDER BY p.fecha DESC');
mysqli_stmt_bind_param($stmt, 'i', $usuario);
mysqli_stmt_execute($stmt);
$pedidos = mysqli_stmt_get_result($stmt);

function color_estado_pedido(string $estado): string
{
    if (in_array($estado, ['En preparación', 'Enviado'], true)) {
        return '#2397dd';
    }

    return match ($estado) {
        'Confirmado', 'Entregado', 'Pagado', 'Completado' => '#17b67f',
        'Cancelado', 'Rechazado' => '#d94b4b',
        'En preparación', 'En reparación' => '#2397dd',
        default => '#d75a00',
    };
}

function normalizar_pago(?string $estado): string
{
    return match ($estado) {
        'Aprobado', 'Pagado' => 'Aprobado',
        'Rechazado' => 'Rechazado',
        default => 'Pendiente',
    };
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Mis pedidos | Digital Care</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<main class="dashboard-page">
  <?php digital_care_header($conexion); ?>
  <section class="dashboard-content">
    <article class="module-view">
      <div class="orders-head">
        <div>
          <h2>Mis pedidos</h2>
          <p>Historial de compras, pagos de prueba y estado actualizado.</p>
        </div>
        <a class="primary" href="module.php?module=repuestos">Comprar repuestos</a>
      </div>
      <div class="orders-list">
        <?php if (!$pedidos || mysqli_num_rows($pedidos) === 0): ?>
          <div class="empty-state">Aún no tenés pedidos registrados.</div>
        <?php endif; ?>
        <?php while ($pedido = $pedidos ? mysqli_fetch_assoc($pedidos) : null): ?>
          <?php $estadoPago = normalizar_pago($pedido['pago_estado'] ?? $pedido['estado_pago'] ?? 'Pendiente'); ?>
          <article class="order-card">
            <div class="order-top">
              <div>
                <strong>Pedido #<?= (int) $pedido['id'] ?></strong>
                <small><?= date('d/m/Y H:i', strtotime((string) $pedido['fecha'])) ?></small>
              </div>
              <span style="color:<?= color_estado_pedido((string) $pedido['estado']) ?>"><?= e($pedido['estado']) ?></span>
            </div>
            <div class="order-detail">
              <p><b>Productos</b></p>
              <ul>
                <?php foreach (explode('|', (string) ($pedido['productos'] ?: 'Sin detalle')) as $producto): ?>
                  <li><?= e($producto) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="order-bottom">
              <span>Método: <b><?= e($pedido['metodo_pago']) ?></b></span>
              <span>Pago: <b><?= e($estadoPago) ?></b></span>
              <strong>$<?= number_format((float) $pedido['total'], 2, ',', '.') ?></strong>
            </div>
            <div class="payment-meta">
              <small>Operación: <?= e($pedido['numero_operacion']) ?></small>
              <?php if (!empty($pedido['referencia_proveedor'])): ?><small>Transacción de prueba: <?= e($pedido['referencia_proveedor']) ?></small><?php endif; ?>
              <?php if (!empty($pedido['tarjeta_ultimos4'])): ?><small><?= e($pedido['tipo_tarjeta'] ?: 'Tarjeta') ?> terminada en <?= e($pedido['tarjeta_ultimos4']) ?></small><?php endif; ?>
              <small>Ambiente: <?= e($pedido['ambiente'] ?: 'Sandbox') ?></small>
            </div>
          </article>
        <?php endwhile; ?>
      </div>
    </article>
  </section>
</main>
<style>
  .orders-head,.order-top,.order-bottom{display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;align-items:flex-start}.orders-head h2{margin:0;color:#1e4e95}.primary{background:linear-gradient(90deg,#2397dd,#2a65de);color:white;border-radius:999px;padding:11px 19px;text-decoration:none;font-weight:800}.orders-list{display:grid;gap:14px;margin-top:20px}.order-card{background:#fff;border:1px solid #d7e3e8;border-radius:8px;padding:19px}.order-top strong{display:block;color:#1e4e95;font-size:1.08rem}.order-top small,.order-card small{display:block;color:#576f78;margin-top:5px}.order-top span{background:#f0f8ff;border-radius:999px;padding:7px 12px;font-weight:800}.order-detail{color:#1f3762;margin:14px 0 7px}.order-detail p{margin:0 0 6px}.order-detail ul{margin:0;padding-left:18px}.order-detail li{margin:4px 0}.order-bottom{border-top:1px solid #d7e3e8;padding-top:12px;color:#576f78}.order-bottom strong{color:#17b67f}.payment-meta{display:flex;gap:10px;flex-wrap:wrap;border-top:1px solid #edf3f6;margin-top:12px;padding-top:10px}.empty-state{text-align:center;background:#f0f8ff;padding:55px;border-radius:8px;color:#576f78}
</style>
</body>
</html>
