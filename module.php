<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
session_start();
require_once 'conexion.php';
require_once 'layout.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit;
}

$modulo = $_GET['module'] ?? 'repuestos';
$rutas = [
    'trabajos' => 'trabajos.php',
    'mantenimiento' => 'mantenimiento.php',
    'clientes' => 'clientes.php',
    'servicios' => 'servicios.php',
    'reportes' => 'reportes.php',
    'perfil' => 'perfil.php',
];
if (isset($rutas[$modulo])) {
    header('Location: ' . $rutas[$modulo]);
    exit;
}

$total = mysqli_fetch_assoc(mysqli_query($conexion, 'SELECT COUNT(*) total, COALESCE(SUM(stock),0) unidades FROM repuestos')) ?: [];
$stockBajo = mysqli_fetch_assoc(mysqli_query($conexion, 'SELECT COUNT(*) bajo FROM repuestos WHERE stock <= stock_minimo')) ?: [];
$categorias = mysqli_fetch_assoc(mysqli_query($conexion, 'SELECT COUNT(DISTINCT categoria) categorias FROM repuestos')) ?: [];
$repuestos = mysqli_query($conexion, 'SELECT id_repuesto,nombre,descripcion,categoria,imagen,stock,stock_minimo,proveedor,codigo,marca,modelo,precio,costo FROM repuestos ORDER BY nombre ASC');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <?= csrf_meta() ?>
  <title>Repuestos | Digital Care</title>
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
      <div class="module-head">
        <div>
          <h2>Repuestos</h2>
          <p>Inventario, precios y stock conectados a MySQL.</p>
        </div>
        <a class="primary" href="carrito.php">Ver carrito</a>
      </div>

      <div class="parts-search-wrap">
        <span class="parts-search-icon">⌕</span>
        <input class="parts-search-input" type="search" id="searchInput" placeholder="Buscar por nombre, descripción o categoría">
      </div>

      <div class="stats-grid parts-stats">
        <article class="stat-card"><div><p class="stat-label">Productos</p><p class="stat-value"><?= (int) ($total['total'] ?? 0) ?></p></div></article>
        <article class="stat-card"><div><p class="stat-label">Categorías</p><p class="stat-value"><?= (int) ($categorias['categorias'] ?? 0) ?></p></div></article>
        <article class="stat-card"><div><p class="stat-label">Unidades</p><p class="stat-value"><?= (int) ($total['unidades'] ?? 0) ?></p></div></article>
        <article class="stat-card"><div><p class="stat-label">Stock bajo</p><p class="stat-value warning-text"><?= (int) ($stockBajo['bajo'] ?? 0) ?></p></div></article>
      </div>

      <div class="repuestos-grid" id="repuestosGrid">
        <?php if ($repuestos && mysqli_num_rows($repuestos) > 0): ?>
          <?php while ($row = mysqli_fetch_assoc($repuestos)): ?>
            <?php
              $id = (int) $row['id_repuesto'];
              $stock = (int) $row['stock'];
              $stockMinimo = (int) ($row['stock_minimo'] ?? 5);
              $stockClass = $stock <= $stockMinimo ? 'critical' : ($stock <= $stockMinimo + 3 ? 'warning' : 'ok');
              $imagen = trim((string) ($row['imagen'] ?? ''));
              $search = e(mb_strtolower(($row['nombre'] ?? '') . ' ' . ($row['descripcion'] ?? '') . ' ' . ($row['categoria'] ?? ''), 'UTF-8'));
              $nombreJson = e(json_encode($row['nombre'] ?? '', JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE));
            ?>
            <article class="repuesto-card" data-name="<?= $search ?>">
              <div class="repuesto-header">
                <div class="repuesto-media">
                  <?php if ($imagen && filter_var($imagen, FILTER_VALIDATE_URL)): ?>
                    <img src="<?= e($imagen) ?>" alt="<?= e($row['nombre']) ?>">
                  <?php else: ?>
                    <span>📦</span>
                  <?php endif; ?>
                </div>
                <span class="stock-badge <?= $stockClass ?>"><?= $stock ?> u.</span>
              </div>
              <h3><?= e($row['nombre']) ?></h3>
              <p class="part-category"><?= e($row['categoria'] ?: 'Repuesto') ?></p>
              <p class="part-description"><?= e($row['descripcion'] ?: 'Sin descripción') ?></p>
              <div class="part-meta">
                <span>Código: <?= e($row['codigo'] ?: 'Sin código') ?></span>
                <span>Proveedor: <?= e($row['proveedor'] ?: 'Sin proveedor') ?></span>
              </div>
              <div class="repuesto-footer">
                <strong>$<?= number_format((float) $row['precio'], 2, ',', '.') ?></strong>
                <div class="part-actions">
                  <button class="secondary" type="button" onclick="mostrarDetalles(<?= $id ?>)">Detalles</button>
                  <button class="primary" type="button" onclick="agregarAlCarrito(<?= $id ?>, <?= $nombreJson ?>)" <?= $stock < 1 ? 'disabled' : '' ?>>Agregar</button>
                </div>
              </div>
            </article>
          <?php endwhile; ?>
        <?php else: ?>
          <p class="empty-state">No hay repuestos cargados en el inventario.</p>
        <?php endif; ?>
      </div>
    </article>
  </section>
</main>

<div id="detallesModal" class="modal" hidden>
  <section class="modal-content">
    <button class="modal-close" type="button" onclick="cerrarModal()">×</button>
    <div id="modalBody"></div>
  </section>
</div>

<style>
  .module-head{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;flex-wrap:wrap}.module-head h2{margin:0;color:#1e4e95}.primary,.secondary{border:0;border-radius:999px;color:#fff;padding:9px 14px;font-weight:800;text-decoration:none;cursor:pointer}.primary{background:linear-gradient(90deg,#2397dd,#2a65de)}.secondary{background:#17b67f}.primary:disabled{opacity:.55;cursor:not-allowed}.parts-stats{grid-template-columns:repeat(4,minmax(0,1fr))}.warning-text{color:#d75a00}.repuestos-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:18px}.repuesto-card{background:#fff;border:1px solid #d7e3e8;border-radius:16px;padding:16px;display:grid;gap:10px;box-shadow:0 8px 18px #3b6f7c16}.repuesto-header,.repuesto-footer{display:flex;justify-content:space-between;gap:12px;align-items:center}.repuesto-media{width:62px;height:62px;border-radius:14px;background:linear-gradient(135deg,#e6f7ff,#dff5e7);display:grid;place-items:center;overflow:hidden;font-size:2rem}.repuesto-media img{width:100%;height:100%;object-fit:cover}.repuesto-card h3{margin:0;color:#1f3762;font-size:1.05rem;line-height:1.25}.part-category{margin:0;color:#2397dd;font-weight:800;font-size:.78rem}.part-description{margin:0;color:#576f78;font-size:.88rem;line-height:1.45;min-height:40px}.part-meta{display:grid;gap:4px;color:#6a808a;font-size:.76rem}.repuesto-footer strong{color:#17b67f;font-size:1.08rem}.part-actions{display:flex;gap:7px;flex-wrap:wrap;justify-content:flex-end}.empty-state{grid-column:1/-1;text-align:center;background:#f0f8ff;border-radius:16px;padding:45px;color:#576f78}.modal{position:fixed;inset:0;background:#0008;z-index:20;place-items:center}.modal:not([hidden]){display:grid}.modal-content{background:#fff;border-radius:18px;width:min(520px,92vw);padding:24px;position:relative;box-shadow:0 24px 60px #0004}.modal-close{position:absolute;top:12px;right:12px;border:0;background:#eef5f8;border-radius:50%;width:34px;height:34px;cursor:pointer;font-size:1.2rem}@media(max-width:820px){.parts-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:560px){.parts-stats{grid-template-columns:1fr}.part-actions{width:100%}.part-actions button{flex:1}}
</style>
<script>
const csrf = document.querySelector('meta[name=csrf-token]')?.content || '';
function escapeHtml(value) {
  const div = document.createElement('div');
  div.textContent = value ?? '';
  return div.innerHTML;
}
document.getElementById('searchInput')?.addEventListener('input', event => {
  const term = event.target.value.toLowerCase();
  document.querySelectorAll('.repuesto-card').forEach(card => {
    card.style.display = (card.dataset.name || '').includes(term) ? '' : 'none';
  });
});
function mostrarDetalles(id) {
  fetch('api_repuesto_detalle.php?id=' + encodeURIComponent(id))
    .then(r => r.json())
    .then(data => {
      if (!data.success) throw new Error(data.error || 'No se pudo cargar el repuesto.');
      const r = data.repuesto;
      modalBody.innerHTML = `
        <h2 style="margin:0;color:#1e4e95">${escapeHtml(r.nombre)}</h2>
        <p style="color:#576f78">${escapeHtml(r.descripcion || 'Sin descripción')}</p>
        <p><b>Categoría:</b> ${escapeHtml(r.categoria || 'Repuesto')}</p>
        <p><b>Stock:</b> ${Number(r.stock)} unidades</p>
        <p><b>Precio:</b> <strong style="color:#17b67f">$${Number(r.precio).toFixed(2)}</strong></p>
        <button class="primary" type="button" onclick="agregarAlCarrito(${Number(r.id_repuesto)}, '${escapeHtml(r.nombre).replaceAll("'", "&#039;")}')">Agregar al carrito</button>
      `;
      detallesModal.hidden = false;
    })
    .catch(error => alert(error.message));
}
function cerrarModal() { detallesModal.hidden = true; }
function actualizarContadorCarrito() {
  fetch('api_carrito.php?action=contar')
    .then(r => r.json())
    .then(data => {
      const badge = document.getElementById('cartCount');
      if (data.success && badge) {
        badge.textContent = data.cantidad;
        badge.hidden = data.cantidad < 1;
      }
    });
}
function agregarAlCarrito(id, nombre) {
  fetch('api_carrito.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action:'agregar', id, csrf_token:csrf})
  })
    .then(r => r.json())
    .then(data => {
      if (!data.success) throw new Error(data.error || 'No se pudo agregar el producto.');
      actualizarContadorCarrito();
      cerrarModal();
      alert(nombre + ' agregado al carrito.');
    })
    .catch(error => alert(error.message));
}
detallesModal?.addEventListener('click', event => { if (event.target === detallesModal) cerrarModal(); });
</script>
</body>
</html>
