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
    header('Location: index.php');
    exit;
}

ensure_digitalcare_shop_schema($conexion);

$usuario = (int) $_SESSION['id_usuario'];
$nombre = $_SESSION['nombre'] ?? 'Cliente';
$_SESSION['checkout_intent'] = $_SESSION['checkout_intent'] ?? bin2hex(random_bytes(32));

$stmt = db_prepare($conexion, 'SELECT c.id_carrito,c.cantidad,r.id_repuesto,r.nombre,r.descripcion,r.categoria,r.imagen,r.stock,r.precio
    FROM carrito c
    JOIN repuestos r ON r.id_repuesto = c.id_repuesto
    WHERE c.id_usuario = ?
    ORDER BY c.fecha_agregado');
mysqli_stmt_bind_param($stmt, 'i', $usuario);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$items = [];
$unidades = 0;
$total = 0.0;
while ($rs && $row = mysqli_fetch_assoc($rs)) {
    $row['cantidad'] = (int) $row['cantidad'];
    $row['stock'] = (int) $row['stock'];
    $row['precio'] = (float) $row['precio'];
    $items[] = $row;
    $unidades += $row['cantidad'];
    $total += $row['cantidad'] * $row['precio'];
}
mysqli_stmt_close($stmt);

function imagen_producto(?string $imagen): string
{
    return $imagen && filter_var($imagen, FILTER_VALIDATE_URL) ? $imagen : '';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <?= csrf_meta() ?>
  <title>Carrito | Digital Care</title>
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
      <div class="cart-head">
        <div>
          <h2>Mi carrito</h2>
          <p>Hola, <strong><?= e($nombre) ?></strong>. Tu carrito se guarda automáticamente.</p>
        </div>
        <a class="primary" href="module.php?module=repuestos">Seguir comprando</a>
      </div>

      <?php if (!$items): ?>
        <div class="empty-cart">
          <div>🛒</div>
          <h3>Tu carrito está vacío</h3>
          <p>Agregá productos del inventario para iniciar una compra.</p>
        </div>
      <?php else: ?>
        <div class="cart-layout">
          <section>
            <div id="mensaje" role="alert"></div>
            <?php foreach ($items as $item): ?>
              <?php $subtotal = $item['cantidad'] * $item['precio']; $imagen = imagen_producto($item['imagen']); ?>
              <article class="cart-item">
                <div class="item-image">
                  <?php if ($imagen): ?><img src="<?= e($imagen) ?>" alt="<?= e($item['nombre']) ?>"><?php else: ?><span>📦</span><?php endif; ?>
                </div>
                <div class="item-info">
                  <h3><?= e($item['nombre']) ?></h3>
                  <p class="item-category"><?= e($item['categoria'] ?: 'Repuesto') ?></p>
                  <p><?= e($item['descripcion']) ?></p>
                  <small class="<?= $item['stock'] <= 5 ? 'stock-low' : 'stock-ok' ?>">Stock disponible: <?= $item['stock'] ?></small>
                </div>
                <div class="item-actions">
                  <strong>$<?= number_format($item['precio'], 2, ',', '.') ?></strong>
                  <div class="qty-row">
                    <button class="qty" data-id="<?= (int) $item['id_carrito'] ?>" data-c="-1">-</button>
                    <b><?= $item['cantidad'] ?></b>
                    <button class="qty" data-id="<?= (int) $item['id_carrito'] ?>" data-c="1">+</button>
                  </div>
                  <small>Subtotal $<?= number_format($subtotal, 2, ',', '.') ?></small>
                  <button class="remove" data-id="<?= (int) $item['id_carrito'] ?>">Eliminar</button>
                </div>
              </article>
            <?php endforeach; ?>
          </section>

          <aside class="cart-summary">
            <h3>Resumen del pedido</h3>
            <p><span>Productos</span><b><?= $unidades ?> unidades</b></p>
            <p class="total"><b>Total</b><b>$<?= number_format($total, 2, ',', '.') ?></b></p>
            <button id="vaciar" class="outline-danger">Vaciar carrito</button>
            <button id="comprar" class="success">Continuar compra</button>
          </aside>
        </div>

        <section id="checkout" class="checkout" hidden>
          <h3>Datos para finalizar la compra</h3>
          <p>Todos los campos son obligatorios. El pago continuará en un checkout de prueba.</p>
          <div class="test-warning">ENTORNO DE PRUEBA - No se realizará ningún cobro real.</div>
          <form id="checkoutForm">
            <input type="hidden" name="checkout_intent" value="<?= e($_SESSION['checkout_intent']) ?>">
            <div class="checkout-grid">
              <label>Nombre completo<input required name="nombre_completo" autocomplete="name"></label>
              <label>DNI<input required name="dni" inputmode="numeric" pattern="[0-9. -]{6,20}"></label>
              <label>Teléfono<input required name="telefono" autocomplete="tel" pattern="[0-9 +()\-]{6,30}"></label>
              <label>Correo electrónico<input required type="email" name="email" autocomplete="email"></label>
              <label>Dirección<input required name="direccion" autocomplete="street-address"></label>
              <label>Ciudad<input required name="ciudad" autocomplete="address-level2"></label>
              <label>Provincia<input required name="provincia" autocomplete="address-level1"></label>
              <label>Código postal<input required name="codigo_postal" autocomplete="postal-code"></label>
            </div>
            <h3>Elegí el medio de pago</h3>
            <div class="payment-grid">
              <label>
                <input type="radio" name="metodo_pago" value="mercado_pago" required checked>
                <strong>Mercado Pago</strong>
                <small>Sandbox/mock para tesis. Permite aprobar, rechazar o dejar pendiente.</small>
                <span id="mpEstado">Entorno de prueba activo</span>
              </label>
            </div>
            <button id="pagar" class="success" type="submit">Continuar con el pago</button>
          </form>
        </section>
      <?php endif; ?>
    </article>
  </section>
</main>
<style>
  .cart-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap}.cart-head h2{margin:0;color:#1e4e95}.primary,.success{border:0;color:#fff;background:linear-gradient(90deg,#17b67f,#12a36a);border-radius:999px;padding:12px 20px;text-decoration:none;font-weight:800;cursor:pointer}.primary{background:linear-gradient(90deg,#2397dd,#2a65de)}.empty-cart{text-align:center;padding:65px 20px;background:#f0f8ff;border-radius:8px}.empty-cart div{font-size:4rem}.empty-cart h3{color:#1e4e95}.empty-cart p{color:#576f78}.cart-layout{display:grid;grid-template-columns:minmax(0,2fr) minmax(300px,1fr);gap:24px;margin-top:20px}.cart-item{display:flex;gap:16px;background:#fff;border:1px solid #d7e3e8;border-radius:8px;padding:16px;margin-bottom:14px;align-items:center}.item-image{width:76px;height:76px;border-radius:8px;background:#e6f7ff;display:grid;place-items:center;overflow:hidden;flex:0 0 76px;font-size:2rem}.item-image img{width:100%;height:100%;object-fit:cover}.item-info{flex:1;min-width:0}.item-info h3{margin:0 0 5px;color:#1f3762;font-size:1rem}.item-info p{margin:0 0 6px;color:#576f78;font-size:.82rem}.item-category{color:#2397dd!important;font-weight:800}.stock-low{color:#d75a00;font-weight:800}.stock-ok{color:#17b67f;font-weight:800}.item-actions{text-align:right;min-width:145px}.item-actions strong,.item-actions small{display:block;color:#17b67f;font-weight:800}.qty-row{display:flex;gap:7px;align-items:center;justify-content:flex-end;margin:9px 0}.qty{border:0;border-radius:7px;background:#2397dd;color:white;width:26px;height:26px;font-weight:800;cursor:pointer}.remove{border:0;background:none;color:#d94b4b;cursor:pointer;margin-top:7px;font-weight:800}.cart-summary,.checkout{background:#fff;border:1px solid #d7e3e8;border-radius:8px;padding:22px}.cart-summary{height:max-content;background:#f0f8ff;border:2px solid #2397dd}.cart-summary h3,.checkout h3{margin-top:0;color:#1e4e95}.cart-summary p{display:flex;justify-content:space-between;color:#576f78}.cart-summary .total{border-top:1px solid #b8d4df;padding-top:13px;color:#1e4e95;font-size:1.15rem}.cart-summary .total b:last-child{color:#17b67f}.outline-danger{width:100%;border:1px solid #d94b4b;color:#d94b4b;background:#fff;border-radius:999px;padding:10px;font-weight:800;cursor:pointer}.cart-summary .success{width:100%;margin-top:10px}.checkout{margin-top:24px}.test-warning{background:#fff4d8;border:1px solid #e8bd55;color:#8a5b00;border-radius:8px;padding:10px 12px;font-weight:800;margin:12px 0}.checkout-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:13px;margin-top:15px}.payment-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px}.payment-grid label{border:1px solid #b8d4df;border-radius:8px;padding:15px;cursor:pointer;background:#fff}.payment-grid small,.payment-grid span{display:block;margin-top:5px;color:#576f78}label{font-size:.85rem;font-weight:800;color:#1f3762}input,select{box-sizing:border-box;display:block;width:100%;margin-top:5px;border:1px solid #b8d4df;border-radius:8px;padding:10px;font:inherit;color:#1f3762}@media(max-width:850px){.cart-layout{grid-template-columns:1fr}.cart-item{align-items:flex-start}.item-actions{text-align:left}.qty-row{justify-content:flex-start}}@media(max-width:560px){.cart-item{display:grid}.item-actions{min-width:0}}
</style>
<script>
const csrf = document.querySelector('meta[name=csrf-token]')?.content || '';
const api = data => {
  data.csrf_token = csrf;
  return fetch('api_carrito.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams(data)}).then(r => r.json());
};
document.querySelectorAll('.qty').forEach(button => button.onclick = () => api({action:'cambiar_cantidad', key:button.dataset.id, cambio:button.dataset.c}).then(data => data.success ? location.reload() : alert(data.error)));
document.querySelectorAll('.remove').forEach(button => button.onclick = () => { if (confirm('¿Eliminar este producto?')) api({action:'eliminar', key:button.dataset.id}).then(data => data.success ? location.reload() : alert(data.error)); });
document.getElementById('vaciar')?.addEventListener('click', () => { if (confirm('¿Vaciar el carrito?')) api({action:'limpiar'}).then(data => data.success ? location.reload() : alert(data.error)); });
document.getElementById('comprar')?.addEventListener('click', () => { checkout.hidden = false; checkout.scrollIntoView({behavior:'smooth'}); });
fetch('api_carrito.php?action=estado_pagos').then(r => r.json()).then(data => {
  mpEstado.textContent = data.mercado_pago?.conectado ? 'Sandbox/mock activo' : 'No disponible';
  mpEstado.style.color = data.mercado_pago?.conectado ? '#17b67f' : '#d75a00';
});
document.getElementById('checkoutForm')?.addEventListener('submit', event => {
  event.preventDefault();
  const button = document.getElementById('pagar');
  button.disabled = true;
  button.textContent = 'Procesando...';
  const data = new FormData(event.target);
  data.append('action', 'finalizar_compra');
  data.append('csrf_token', csrf);
  fetch('api_carrito.php', {method:'POST', body:new URLSearchParams(data)})
    .then(r => r.json())
    .then(result => {
      if (!result.success) throw new Error(result.error);
      location.href = result.url_pago;
    })
    .catch(error => {
      alert(error.message || 'No se pudo continuar con el pago.');
      button.disabled = false;
      button.textContent = 'Continuar con el pago';
    });
});
</script>
</body>
</html>
