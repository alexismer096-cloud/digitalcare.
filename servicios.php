<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
session_start();
require_once 'conexion.php';
require_once 'layout.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location:index.php');
    exit;
}

$q = trim($_GET['q'] ?? '');
$cat = trim($_GET['categoria'] ?? '');
$sql = 'SELECT * FROM servicios WHERE 1';
$types = '';
$args = [];

if ($q !== '') {
    $sql .= ' AND (nombre LIKE ? OR descripcion LIKE ?)';
    $types .= 'ss';
    $like = "%$q%";
    $args[] = $like;
    $args[] = $like;
}
if ($cat !== '') {
    $sql .= ' AND categoria = ?';
    $types .= 's';
    $args[] = $cat;
}
$sql .= ' ORDER BY estado DESC, nombre';

$stmt = db_prepare($conexion, $sql);
if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$args);
}
mysqli_stmt_execute($stmt);
$servicios = mysqli_stmt_get_result($stmt);
$categorias = mysqli_query($conexion, 'SELECT DISTINCT categoria FROM servicios ORDER BY categoria');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Servicios | Digital Care</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<main class="dashboard-page">
  <?php digital_care_header($conexion); ?>
  <section class="dashboard-content">
    <article class="module-view">
      <div class="head">
        <div><h2>Servicios</h2><p>Catálogo técnico administrado desde MySQL.</p></div>
        <button onclick="abrir()" class="primary">Agregar servicio</button>
      </div>
      <form method="get" class="filters">
        <input name="q" value="<?= e($q) ?>" placeholder="Buscar servicio">
        <select name="categoria">
          <option value="">Todas las categorías</option>
          <?php while ($categoria = $categorias ? mysqli_fetch_assoc($categorias) : null): ?>
            <option value="<?= e($categoria['categoria']) ?>" <?= $cat === $categoria['categoria'] ? 'selected' : '' ?>><?= e($categoria['categoria']) ?></option>
          <?php endwhile; ?>
        </select>
        <button class="primary">Filtrar</button>
      </form>
      <div class="service-grid">
        <?php while ($servicio = $servicios ? mysqli_fetch_assoc($servicios) : null): ?>
          <?php $json = e(json_encode($servicio, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE)); ?>
          <article class="repuesto-card">
            <div class="row"><strong><?= e($servicio['categoria']) ?></strong><b class="<?= $servicio['estado'] === 'Activo' ? 'active' : 'paused' ?>"><?= e($servicio['estado']) ?></b></div>
            <h3><?= e($servicio['nombre']) ?></h3>
            <p><?= e($servicio['descripcion']) ?></p>
            <p><b class="price">$<?= number_format((float) $servicio['precio'], 2, ',', '.') ?></b> · <?= e($servicio['tiempo_estimado']) ?></p>
            <button onclick='editar(<?= $json ?>)' class="primary">Editar</button>
            <button onclick="estado(<?= (int) $servicio['id_servicio'] ?>,'<?= $servicio['estado'] === 'Activo' ? 'Inactivo' : 'Activo' ?>')" class="warning"><?= $servicio['estado'] === 'Activo' ? 'Desactivar' : 'Activar' ?></button>
            <button onclick="borrar(<?= (int) $servicio['id_servicio'] ?>)" class="danger">Eliminar</button>
          </article>
        <?php endwhile; ?>
      </div>
    </article>
  </section>
</main>

<div id="modal" hidden>
  <form id="form">
    <h3 id="titulo">Servicio</h3>
    <?= csrf_input() ?>
    <input type="hidden" name="id">
    <label>Nombre<input required name="nombre"></label>
    <label>Categoría<input required name="categoria"></label>
    <label>Precio<input required type="number" min="0" step="0.01" name="precio"></label>
    <label>Tiempo estimado<input required name="tiempo_estimado"></label>
    <label>Estado<select name="estado"><option>Activo</option><option>Inactivo</option></select></label>
    <label>Imagen (URL)<input name="imagen"></label>
    <label>Descripción<textarea required name="descripcion"></textarea></label>
    <button class="primary">Guardar</button>
    <button type="button" onclick="cerrar()">Cancelar</button>
  </form>
</div>

<style>
  .head,.filters,.row{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center}.head h2{margin:0;color:#1e4e95}.filters{justify-content:flex-start;margin:16px 0}.service-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:17px}.price,.active{color:#17b67f}.paused{color:#d75a00}input,select,textarea{width:100%;box-sizing:border-box;padding:9px;margin:5px 0 11px;border:1px solid #b8d4df;border-radius:8px}.primary,.warning,.danger{border:0;border-radius:999px;color:#fff;padding:9px 13px;cursor:pointer;font-weight:700;margin:2px;text-decoration:none}.primary{background:#2397dd}.warning{background:#f59d18}.danger{background:#f52d46}#modal{position:fixed;inset:0;background:#0008;z-index:10;place-items:center}#modal:not([hidden]){display:grid}#form{width:min(520px,90vw);background:#fff;border-radius:18px;padding:23px}
</style>
<script>
const m=document.getElementById('modal'),f=document.getElementById('form');
const csrf=f.elements.csrf_token.value;
function abrir(){f.reset();f.elements.id.value='';titulo.textContent='Nuevo servicio';m.hidden=false}
function cerrar(){m.hidden=true}
function editar(s){abrir();titulo.textContent='Editar servicio';for(const k in s)if(f.elements[k])f.elements[k].value=s[k]??''}
function api(d){return fetch('api_servicios.php',{method:'POST',body:new URLSearchParams(d)}).then(r=>r.json())}
f.onsubmit=e=>{e.preventDefault();const d=new FormData(f);d.append('action','guardar');api(d).then(x=>x.success?location.reload():alert(x.error))}
function estado(id,e){api({action:'estado',id,estado:e,csrf_token:csrf}).then(x=>x.success?location.reload():alert(x.error))}
function borrar(id){if(confirm('¿Eliminar este servicio?'))api({action:'eliminar',id,csrf_token:csrf}).then(x=>x.success?location.reload():alert(x.error))}
</script>
</body>
</html>
