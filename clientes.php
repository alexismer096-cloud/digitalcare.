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
$page = max(1, (int) ($_GET['pagina'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$like = "%$q%";

$stmt = db_prepare($conexion, 'SELECT COUNT(*) n FROM usuarios WHERE nombre LIKE ? OR apellido LIKE ? OR email LIKE ? OR telefono LIKE ?');
mysqli_stmt_bind_param($stmt, 'ssss', $like, $like, $like, $like);
mysqli_stmt_execute($stmt);
$total = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['n'] ?? 0);
mysqli_stmt_close($stmt);

$stmt = db_prepare($conexion, 'SELECT u.id_usuario,u.nombre,u.apellido,u.nombre_usuario,u.email,u.telefono,u.direccion,u.rol,u.fecha_registro, COUNT(t.id_trabajo) trabajos
    FROM usuarios u
    LEFT JOIN trabajos t ON t.id_usuario = u.id_usuario
    WHERE u.nombre LIKE ? OR u.apellido LIKE ? OR u.email LIKE ? OR u.telefono LIKE ?
    GROUP BY u.id_usuario
    ORDER BY u.fecha_registro DESC
    LIMIT ? OFFSET ?');
mysqli_stmt_bind_param($stmt, 'ssssii', $like, $like, $like, $like, $limit, $offset);
mysqli_stmt_execute($stmt);
$clientes = mysqli_stmt_get_result($stmt);
$pages = max(1, (int) ceil($total / $limit));
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Clientes | Digital Care</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<main class="dashboard-page">
  <?php digital_care_header($conexion); ?>
  <section class="dashboard-content">
    <article class="module-view">
      <div class="head">
        <div>
          <h2>Clientes</h2>
          <p>Base de datos completa de clientes y trabajos asociados.</p>
        </div>
        <button class="primary" onclick="abrir()">Agregar cliente</button>
      </div>

      <form class="filters">
        <input name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre, correo o teléfono">
        <button class="primary">Buscar</button>
      </form>

      <div class="table-wrap">
        <table>
          <thead><tr><th>Nombre</th><th>Correo</th><th>Teléfono</th><th>Dirección</th><th>Alta</th><th>Trabajos</th><th>Estado</th><th>Acciones</th></tr></thead>
          <tbody>
          <?php while ($cliente = $clientes ? mysqli_fetch_assoc($clientes) : null): ?>
            <?php $json = e(json_encode($cliente, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE)); ?>
            <tr>
              <td class="client-name"><strong><?= e(trim($cliente['nombre'] . ' ' . ($cliente['apellido'] ?? ''))) ?></strong></td>
              <td><?= e($cliente['email']) ?></td>
              <td><?= e($cliente['telefono'] ?: 'Sin teléfono') ?></td>
              <td><?= e($cliente['direccion'] ?: 'Sin dirección') ?></td>
              <td><?= date('d/m/Y', strtotime($cliente['fecha_registro'])) ?></td>
              <td><?= (int) $cliente['trabajos'] ?></td>
              <td><span class="badge"><?= e($cliente['rol'] ?: 'Cliente') ?></span></td>
              <td class="actions">
                <button class="primary" onclick='editar(<?= $json ?>)'>Editar</button>
                <a class="secondary" href="trabajos.php?id_cliente=<?= (int) $cliente['id_usuario'] ?>">Crear trabajo</a>
                <button class="secondary" onclick="historial(<?= (int) $cliente['id_usuario'] ?>)">Historial</button>
                <button class="danger" onclick="borrar(<?= (int) $cliente['id_usuario'] ?>)">Eliminar</button>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <nav class="pages">
        <?php if ($page > 1): ?><a href="?q=<?= urlencode($q) ?>&pagina=<?= $page - 1 ?>">Anterior</a><?php endif; ?>
        <strong>Página <?= $page ?> de <?= $pages ?></strong>
        <?php if ($page < $pages): ?><a href="?q=<?= urlencode($q) ?>&pagina=<?= $page + 1 ?>">Siguiente</a><?php endif; ?>
      </nav>
    </article>
  </section>
</main>

<div id="modal" hidden>
  <form id="form">
    <h3 id="titulo">Cliente</h3>
    <?= csrf_input() ?>
    <input type="hidden" name="id">
    <label>Nombre<input required name="nombre"></label>
    <label>Apellido<input name="apellido"></label>
    <label>Usuario<input name="nombre_usuario"></label>
    <label>Correo<input required type="email" name="email"></label>
    <label>Teléfono<input name="telefono"></label>
    <label>Dirección<input name="direccion"></label>
    <label id="clave">Contraseña inicial<input type="password" name="password" minlength="8"></label>
    <button class="primary" type="submit">Guardar</button>
    <button type="button" onclick="cerrar()">Cancelar</button>
  </form>
</div>

<div id="historialModal" hidden>
  <section>
    <button class="close" onclick="cerrarHistorial()">Cerrar</button>
    <h3>Historial de trabajos</h3>
    <div id="historialBody"></div>
  </section>
</div>

<style>
  .head,.filters,.pages{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center}.head h2{margin:0;color:#1e4e95}.filters{justify-content:flex-start;margin:16px 0}.filters input{max-width:420px}.table-wrap{overflow:auto;border:1px solid #d7e3e8;border-radius:14px;background:#fff;box-shadow:0 8px 18px #3b6f7c12}table{width:100%;border-collapse:separate;border-spacing:0;background:#fff;min-width:1120px;table-layout:fixed}th,td{padding:13px 14px;border-bottom:1px solid #e7eef2;text-align:left;vertical-align:middle;color:#294757;font-size:.88rem;line-height:1.3;overflow:hidden;text-overflow:ellipsis}th{color:#1e4e95;background:#f0f8ff;font-size:.76rem;text-transform:uppercase;letter-spacing:.02em;white-space:nowrap}tbody tr{height:66px}tbody tr:hover{background:#f8fcff}th:nth-child(1){width:210px}th:nth-child(2){width:235px}th:nth-child(3){width:140px}th:nth-child(4){width:190px}th:nth-child(5){width:105px}th:nth-child(6){width:95px}th:nth-child(7){width:115px}th:nth-child(8){width:330px}.client-name,.client-name strong{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#1f3762}.client-name strong{display:block}td:nth-child(2),td:nth-child(3),td:nth-child(5),td:nth-child(6),td:nth-child(7){white-space:nowrap}.actions{display:flex;gap:6px;flex-wrap:nowrap;align-items:center;overflow:visible}.primary,.secondary,.danger{border:0;border-radius:999px;color:#fff;padding:8px 12px;font-weight:700;cursor:pointer;text-decoration:none;font-size:.78rem;white-space:nowrap;line-height:1}.primary{background:#2397dd}.secondary{background:#17b67f}.danger{background:#f52d46}.badge{display:inline-block;background:#e6f7ff;color:#1e4e95;border-radius:999px;padding:5px 9px;font-weight:700;white-space:nowrap}input{padding:9px;border:1px solid #b8d4df;border-radius:8px;width:100%;box-sizing:border-box;margin:4px 0 10px}#modal,#historialModal{position:fixed;inset:0;background:#0008;z-index:10;place-items:center}#modal:not([hidden]),#historialModal:not([hidden]){display:grid}#form,#historialModal section{background:#fff;padding:22px;border-radius:16px;width:min(560px,92vw);max-height:84vh;overflow:auto}.close{float:right;border:0;background:#f52d46;color:#fff;border-radius:999px;padding:7px 11px}@media (max-width:720px){table{min-width:1040px}th:nth-child(8){width:295px}.primary,.secondary,.danger{padding:8px 10px;font-size:.74rem}.table-wrap{border-radius:12px}}
</style>
<script>
const m=document.getElementById('modal'),f=document.getElementById('form'),hm=document.getElementById('historialModal'),hb=document.getElementById('historialBody');
const csrf=f.elements.csrf_token.value;
function abrir(){f.reset();f.elements.id.value='';titulo.textContent='Nuevo cliente';clave.hidden=false;f.elements.password.required=true;m.hidden=false}
function cerrar(){m.hidden=true}
function editar(x){abrir();titulo.textContent='Editar cliente';clave.hidden=true;f.elements.password.required=false;for(const k in x)if(f.elements[k])f.elements[k].value=x[k]??''}
f.onsubmit=e=>{e.preventDefault();let d=new FormData(f);d.append('action','guardar');fetch('api_clientes.php',{method:'POST',body:new URLSearchParams(d)}).then(r=>r.json()).then(x=>x.success?location.reload():alert(x.error))}
function borrar(id){if(confirm('¿Eliminar este cliente? Esta acción no se puede deshacer.'))fetch('api_clientes.php',{method:'POST',body:new URLSearchParams({action:'eliminar',id,csrf_token:csrf})}).then(r=>r.json()).then(x=>x.success?location.reload():alert(x.error))}
function historial(id){hb.innerHTML='Cargando...';hm.hidden=false;fetch('api_clientes.php?action=historial&id='+id).then(r=>r.json()).then(x=>{hb.innerHTML=x.success?x.html:x.error})}
function cerrarHistorial(){hm.hidden=true}
</script>
</body>
</html>
