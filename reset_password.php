<?php
require_once 'conexion.php';
$token = trim($_GET['token'] ?? '');
$valid = false;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $hash = hash('sha256', $token);
    $stmt = db_prepare($conexion, "SELECT id_usuario FROM usuarios WHERE reset_token_hash = ? AND reset_token_used_at IS NULL AND reset_token_expires_at > NOW() LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $hash);
    mysqli_stmt_execute($stmt);
    $valid = (bool) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Digital Care - Nueva contrasena</title>
  <link rel="stylesheet" href="./styles.css" />
  <style>
    .auth-card{width:min(430px,95vw);text-align:center}.auth-form{display:grid;gap:12px}.auth-note{color:#4c5d66;font-size:.92rem;line-height:1.45}.password-rules{list-style:none;padding:0;margin:0;display:grid;gap:5px;text-align:left;color:#7a4b4b;font-size:.84rem}.password-rules li::before{content:"x ";font-weight:700}.password-rules li.ok{color:#1b8f4f}.password-rules li.ok::before{content:"✓ "}
  </style>
</head>
<body>
  <main class="login-page">
    <section class="auth-card">
      <h1 class="brand"><span class="brand-blue">Digital</span><span class="brand-green">Care</span></h1>
      <?php if (!$valid): ?>
        <p class="auth-note">El enlace para restablecer la contrasena no es valido o ya expiro.</p>
        <nav class="links"><a href="recuperar.php">Solicitar un nuevo enlace</a></nav>
      <?php else: ?>
        <h2 class="brand" style="font-size:1.45rem;margin-bottom:10px">Establecer nueva contrasena</h2>
        <form class="auth-form" action="procesar_reset_password.php" method="POST">
          <?= csrf_input() ?>
          <input type="hidden" name="token" value="<?= e($token) ?>">
          <input id="password" name="password" type="password" placeholder="Nueva contrasena" autocomplete="new-password" required>
          <input id="confirmar" name="confirmar" type="password" placeholder="Confirmar nueva contrasena" autocomplete="new-password" required>
          <ul class="password-rules" aria-live="polite">
            <li data-rule="length">8 caracteres</li>
            <li data-rule="upper">Mayuscula</li>
            <li data-rule="lower">Minuscula</li>
            <li data-rule="number">Numero</li>
            <li data-rule="special">Caracter especial</li>
            <li data-rule="match">Coinciden</li>
          </ul>
          <button id="saveBtn" type="submit" class="submit-btn" disabled>Guardar contrasena</button>
          <p id="feedback" class="feedback" role="status" aria-live="polite"></p>
        </form>
      <?php endif; ?>
    </section>
  </main>
  <script>
    const pass = document.getElementById('password');
    if (pass) {
      const confirm = document.getElementById('confirmar');
      const btn = document.getElementById('saveBtn');
      const tests = {
        length: v => v.length >= 8,
        upper: v => /[A-Z]/.test(v),
        lower: v => /[a-z]/.test(v),
        number: v => /[0-9]/.test(v),
        special: v => /[!@#$%&*?]/.test(v),
        match: v => v !== '' && v === confirm.value
      };
      function updateRules() {
        const value = pass.value;
        let ok = true;
        document.querySelectorAll('.password-rules li').forEach(li => {
          const ruleOk = tests[li.dataset.rule](value);
          li.classList.toggle('ok', ruleOk);
          ok = ok && ruleOk;
        });
        btn.disabled = !ok;
      }
      pass.addEventListener('input', updateRules);
      confirm.addEventListener('input', updateRules);
    }
  </script>
</body>
</html>
