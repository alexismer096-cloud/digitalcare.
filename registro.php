<?php require_once 'conexion.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Digital Care - Registro</title>
  <style>
    body {
      margin: 0;
      min-height: 100vh;
      display: grid;
      place-items: center;
      font-family: Arial, sans-serif;
      background: #c8edf0;
    }
    .card {
      width: min(380px, 92vw);
      background: #fff;
      border-radius: 14px;
      padding: 24px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    }
    h1 {
      margin: 0 0 14px;
      font-size: 24px;
      color: #185ca8;
    }
    p {
      margin: 0 0 18px;
      color: #51606f;
      font-size: 14px;
    }
    input, button, a {
      width: 100%;
      box-sizing: border-box;
      border-radius: 999px;
      padding: 10px 14px;
      font-size: 14px;
      margin-bottom: 10px;
    }
    input {
      border: 1px solid #b7d5e7;
    }
    button {
      border: 0;
      color: #fff;
      font-weight: 700;
      background: linear-gradient(90deg, #2366cc, #26a0e8);
      cursor: pointer;
    }
    .feedback {
      min-height: 20px;
      margin: 4px 0 8px;
      font-size: 13px;
    }
    .error { color: #c02c2c; }
    .success { color: #1b8f4f; }
    .back {
      display: inline-block;
      text-align: center;
      text-decoration: none;
      border: 1px solid #b7d5e7;
      color: #2c4b6a;
      background: #f7fbff;
    }
  </style>
</head>
<body>
  <main class="card">
    <h1>Crear Cuenta</h1>
    <p>Registra tus datos para entrar a Digital Care.</p>
    <form id="registerForm" action="procesar_registro.php" method="POST">
      <?= csrf_input() ?>
      <input name="nombre" type="text" placeholder="Nombre completo" required />
      <input name="email" type="email" placeholder="Correo electrónico" required />
      <input id="password" name="password" type="password" placeholder="Contraseña segura" minlength="8" required />
      <input id="confirmar" name="confirmar" type="password" placeholder="Confirmar contraseña" minlength="8" required />
      <ul class="password-rules" aria-live="polite">
        <li data-rule="length">8 caracteres</li>
        <li data-rule="upper">Mayúscula</li>
        <li data-rule="lower">Minúscula</li>
        <li data-rule="number">Número</li>
        <li data-rule="special">Caracter especial</li>
        <li data-rule="match">Coinciden</li>
      </ul>
      <button type="submit">Registrarme</button>
      <p id="feedback" class="feedback" role="status" aria-live="polite"></p>
      <a class="back" href="./index.php">Volver al inicio de sesión</a>
    </form>
  </main>
  <script>
    const pass = document.getElementById('password');
    const confirm = document.getElementById('confirmar');
    const rules = {
      length: v => v.length >= 8,
      upper: v => /[A-Z]/.test(v),
      lower: v => /[a-z]/.test(v),
      number: v => /[0-9]/.test(v),
      special: v => /[!@#$%&*?]/.test(v),
      match: v => v !== '' && v === confirm.value
    };
    function updateRules() {
      const value = pass.value;
      document.querySelectorAll('.password-rules li').forEach(li => li.classList.toggle('ok', rules[li.dataset.rule](value)));
    }
    pass.addEventListener('input', updateRules);
    confirm.addEventListener('input', updateRules);

    function showFeedbackFromQuery() {
      const params = new URLSearchParams(window.location.search);
      const msg = params.get("msg");
      const type = params.get("type");
      const feedback = document.getElementById("feedback");

      if (msg && feedback) {
        feedback.textContent = msg;
        feedback.className = "feedback " + (type || "");
      }
    }
    showFeedbackFromQuery();
  </script>
  <style>.password-rules{list-style:none;padding:0;margin:0 0 10px;display:grid;gap:5px;text-align:left;color:#7a4b4b;font-size:13px}.password-rules li::before{content:"x ";font-weight:700}.password-rules li.ok{color:#1b8f4f}.password-rules li.ok::before{content:"✓ "}</style>
</body>
</html>
