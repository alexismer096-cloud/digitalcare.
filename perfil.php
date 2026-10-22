<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
require_once 'auth.php';
secure_session_start();
require_once 'conexion.php';
require_once 'layout.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location:index.php');
    exit;
}

$id = (int) $_SESSION['id_usuario'];
$stmt = db_prepare($conexion, 'SELECT * FROM usuarios WHERE id_usuario = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$u = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: [];
mysqli_stmt_close($stmt);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Mi perfil | Digital Care</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<main class="dashboard-page">
  <?php digital_care_header($conexion); ?>
  <section class="dashboard-content">
    <article class="module-view">
      <div class="profile-head">
        <div class="profile-photo">
          <?php if (!empty($u['foto_perfil'])): ?>
            <img src="<?= e($u['foto_perfil']) ?>" alt="Foto de perfil">
          <?php else: ?>
            <?= e(mb_strtoupper(mb_substr($u['nombre'] ?? 'U', 0, 1, 'UTF-8'), 'UTF-8')) ?>
          <?php endif; ?>
        </div>
        <div>
          <h2>Mi perfil</h2>
          <p><?= e($u['email'] ?? '') ?></p>
        </div>
      </div>
      <div id="msg" class="profile-msg"></div>
      <form id="perfil" class="profile-form">
        <?= csrf_input() ?>
        <label>Nombre<input required name="nombre" value="<?= e($u['nombre'] ?? '') ?>"></label>
        <label>Apellido<input name="apellido" value="<?= e($u['apellido'] ?? '') ?>"></label>
        <label>Usuario<input name="nombre_usuario" value="<?= e($u['nombre_usuario'] ?? '') ?>"></label>
        <label>Correo<input disabled value="<?= e($u['email'] ?? '') ?>"></label>
        <label>Teléfono<input name="telefono" value="<?= e($u['telefono'] ?? '') ?>"></label>
        <label>DNI<input name="dni" value="<?= e($u['dni'] ?? '') ?>"></label>
        <label>Fecha de nacimiento<input type="date" name="fecha_nacimiento" value="<?= e($u['fecha_nacimiento'] ?? '') ?>"></label>
        <label>Dirección<input name="direccion" value="<?= e($u['direccion'] ?? '') ?>"></label>
        <label>Ciudad<input name="ciudad" value="<?= e($u['ciudad'] ?? '') ?>"></label>
        <label>Provincia<input name="provincia" value="<?= e($u['provincia'] ?? '') ?>"></label>
        <label>País<input name="pais" value="<?= e($u['pais'] ?? 'Argentina') ?>"></label>
        <label>Foto de perfil (URL)<input name="foto_perfil" value="<?= e($u['foto_perfil'] ?? '') ?>"></label>
        <p class="profile-meta"><b>Rol:</b> <?= e($u['rol'] ?? 'Cliente') ?> · <b>Fecha de registro:</b> <?= !empty($u['fecha_registro']) ? date('d/m/Y', strtotime($u['fecha_registro'])) : 'Sin fecha' ?></p>
        <button>Guardar cambios</button>
      </form>
      <hr>
      <form id="clave" class="password-form">
        <?= csrf_input() ?>
        <h3>Seguridad</h3>
        <p class="security-copy">Cambiar contraseña</p>
        <label>Contraseña actual<input required type="password" name="actual" autocomplete="current-password"></label>
        <label>Nueva contraseña<input id="nueva" required minlength="8" type="password" name="nueva" autocomplete="new-password"></label>
        <label>Confirmar nueva contraseña<input id="confirmar" required minlength="8" type="password" name="confirmar" autocomplete="new-password"></label>
        <ul class="password-rules" aria-live="polite">
          <li data-rule="length">8 caracteres</li>
          <li data-rule="upper">Mayúscula</li>
          <li data-rule="lower">Minúscula</li>
          <li data-rule="number">Número</li>
          <li data-rule="special">Caracter especial</li>
          <li data-rule="match">Coinciden</li>
        </ul>
        <button id="claveBtn" disabled>Actualizar contraseña</button>
      </form>
      <a class="logout-link" href="logout.php">Cerrar sesión</a>
    </article>
  </section>
</main>
<style>
  .profile-head{display:flex;gap:16px;align-items:center;margin-bottom:16px}.profile-photo{width:76px;height:76px;border-radius:18px;background:linear-gradient(135deg,#2397dd,#17b67f);color:#fff;display:grid;place-items:center;font-size:2rem;font-weight:800;overflow:hidden}.profile-photo img{width:100%;height:100%;object-fit:cover}.profile-head h2{margin:0;color:#1e4e95}.profile-form,.password-form{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px}.password-form h3,.password-form .security-copy,.password-rules,.profile-meta{grid-column:1/-1}.security-copy{margin:0;color:#576f78;font-weight:700}.profile-meta{color:#576f78}label{font-weight:700;color:#1f3762}input{display:block;width:100%;box-sizing:border-box;padding:10px;border:1px solid #b8d4df;border-radius:8px;margin:5px 0 0}button,.logout-link{width:max-content;background:#2397dd;color:#fff;border:0;border-radius:999px;padding:11px 17px;font-weight:700;text-decoration:none;cursor:pointer}button[disabled]{opacity:.55;cursor:not-allowed}.logout-link{display:inline-block;margin-top:14px;background:#f52d46}.profile-msg{min-height:24px;font-weight:700}.password-rules{list-style:none;padding:0;margin:0;display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:6px;color:#8a4a4a;font-size:.84rem}.password-rules li::before{content:"x ";font-weight:800}.password-rules li.ok{color:#1b8f4f}.password-rules li.ok::before{content:"✓ "}
</style>
<script>
function enviar(form, action) {
  const data = new FormData(form);
  data.append('action', action);
  fetch('api_perfil.php', {method:'POST', body:new URLSearchParams(data)})
    .then(r => r.json())
    .then(x => {
      msg.textContent = x.success ? x.mensaje : x.error;
      msg.style.color = x.success ? '#17b67f' : '#d94b4b';
    });
}
const nueva = document.getElementById('nueva');
const confirmar = document.getElementById('confirmar');
const claveBtn = document.getElementById('claveBtn');
const rules = {
  length: v => v.length >= 8,
  upper: v => /[A-Z]/.test(v),
  lower: v => /[a-z]/.test(v),
  number: v => /[0-9]/.test(v),
  special: v => /[!@#$%&*?]/.test(v),
  match: v => v !== '' && v === confirmar.value
};
function updatePasswordRules() {
  const value = nueva.value;
  let ok = true;
  document.querySelectorAll('.password-rules li').forEach(li => {
    const ruleOk = rules[li.dataset.rule](value);
    li.classList.toggle('ok', ruleOk);
    ok = ok && ruleOk;
  });
  claveBtn.disabled = !ok;
}
nueva.addEventListener('input', updatePasswordRules);
confirmar.addEventListener('input', updatePasswordRules);
perfil.onsubmit = e => { e.preventDefault(); enviar(perfil, 'guardar'); };
clave.onsubmit = e => { e.preventDefault(); enviar(clave, 'password'); };
</script>
</body>
</html>
