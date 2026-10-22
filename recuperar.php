<?php require_once 'conexion.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Digital Care - Recuperar contrasena</title>
  <link rel="stylesheet" href="./styles.css" />
  <style>
    .auth-card{width:min(420px,95vw);text-align:center}.auth-note{margin:0 0 18px;color:#4c5d66;font-size:.92rem;line-height:1.45}.auth-form{display:grid;gap:12px}.back-link{color:#3c6672;text-decoration:none;font-weight:600;font-size:.88rem}.back-link:hover{text-decoration:underline}
  </style>
</head>
<body>
  <main class="login-page">
    <section class="auth-card" aria-labelledby="recover-title">
      <h1 class="brand"><span class="brand-blue">Digital</span><span class="brand-green">Care</span></h1>
      <h2 id="recover-title" class="brand" style="font-size:1.45rem;margin-bottom:10px">Recuperar contrasena</h2>
      <p class="auth-note">Te enviaremos un correo electronico para verificar tu identidad y restablecer tu contrasena.</p>
      <p class="auth-note">Revisa tu bandeja de entrada y, si no lo encuentras, revisa la carpeta de spam.</p>
      <form class="auth-form" action="procesar_recuperar.php" method="POST">
        <?= csrf_input() ?>
        <input name="email" type="email" placeholder="Correo electronico" autocomplete="email" required />
        <button type="submit" class="submit-btn">Enviar instrucciones</button>
        <p id="feedback" class="feedback" role="status" aria-live="polite"></p>
      </form>
      <nav class="links"><a class="back-link" href="./index.php">Volver al inicio de sesion</a></nav>
    </section>
  </main>
  <script>
    const params = new URLSearchParams(window.location.search);
    const msg = params.get('msg');
    const type = params.get('type');
    const feedback = document.getElementById('feedback');
    if (msg && feedback) {
      feedback.textContent = msg;
      feedback.className = 'feedback ' + (type || '');
    }
  </script>
</body>
</html>
