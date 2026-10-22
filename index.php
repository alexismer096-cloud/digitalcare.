<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
require_once 'auth.php';
secure_session_start();
require_once 'conexion.php';
require_once 'layout.php';

$googleClientId = env_value('GOOGLE_CLIENT_ID');
$googleOAuthConfigured = is_string($googleClientId)
    && str_ends_with($googleClientId, '.apps.googleusercontent.com');

// Redirigir si hay sesión pero no está en el dashboard
if (isset($_SESSION['id_usuario']) && (!isset($_GET['view']) || $_GET['view'] !== 'dashboard')) {
    header("Location: index.php?view=dashboard");
    exit;
}

// Redirigir si no hay sesión pero intenta entrar al dashboard
if (!isset($_SESSION['id_usuario']) && isset($_GET['view']) && $_GET['view'] === 'dashboard') {
    header("Location: index.php");
    exit;
}

// Consultas para estadísticas reales
$total_trabajos_pendientes = 0;
$total_completados = 0;
$total_alertas_stock = 0;
$total_clientes = 0;
$total_ganancias = 0.0;
$total_stock = 0;
$total_pedidos = 0;
$total_servicios = 0;

if ($conexion) {
    // 1. Trabajos próximos/pendientes
    $res = mysqli_query($conexion, "SELECT COUNT(*) as total FROM solicitudes WHERE estado = 'Pendiente'");
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        $total_trabajos_pendientes = $row['total'];
    }

    // 2. Completados (ejemplo: trabajos finalizados)
    $res = mysqli_query($conexion, "SELECT COUNT(*) as total FROM solicitudes WHERE estado = 'Completado'");
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        $total_completados = $row['total'];
    }

    // 3. Alertas (repuestos con stock bajo, ej < 5)
    $res = mysqli_query($conexion, "SELECT COUNT(*) as total FROM repuestos WHERE stock <= stock_minimo");
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        $total_alertas_stock = $row['total'];
    }

    $res = mysqli_query($conexion, "SELECT COUNT(*) as total FROM usuarios");
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        $total_clientes = (int) ($row['total'] ?? 0);
    }

    $res = mysqli_query($conexion, "SELECT COALESCE(SUM(total),0) as total FROM pedidos WHERE estado_pago = 'Pagado'");
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        $total_ganancias = (float) ($row['total'] ?? 0);
    }

    $res = mysqli_query($conexion, "SELECT COALESCE(SUM(stock),0) as total FROM repuestos");
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        $total_stock = (int) ($row['total'] ?? 0);
    }

    $res = mysqli_query($conexion, "SELECT COUNT(*) as total FROM pedidos");
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        $total_pedidos = (int) ($row['total'] ?? 0);
    }

    $res = mysqli_query($conexion, "SELECT COUNT(*) as total FROM servicios WHERE estado = 'Activo'");
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        $total_servicios = (int) ($row['total'] ?? 0);
    }

    // 4. Actividad reciente (últimas 3 solicitudes)
    $recientes = [];
    $res = mysqli_query($conexion, "SELECT descripcion AS tipo_equipo, tipo AS estado, fecha FROM actividad_reciente ORDER BY fecha DESC LIMIT 3");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $recientes[] = $row;
        }
    }
    if (!$recientes) {
        $res = mysqli_query($conexion, "SELECT tipo_equipo, estado, fecha FROM solicitudes ORDER BY fecha DESC LIMIT 3");
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $recientes[] = $row;
            }
        }
    }
}

/* Avance calculado desde el registro de módulos auditables de la base de datos. */
$estado_modulos = [];
$total_modulos = $modulos_completados = 0;
if ($conexion) {
    $estadoRes = mysqli_query($conexion, "SELECT nombre, estado FROM estado_modulos ORDER BY nombre");
    while ($estadoRes && $estado = mysqli_fetch_assoc($estadoRes)) {
        $estado_modulos[] = $estado;
        $total_modulos++;
        if ($estado['estado'] === 'Completado') $modulos_completados++;
    }
}
$porcentaje_avance = $total_modulos ? (int) round(($modulos_completados / $total_modulos) * 100) : 0;
$contador_carrito = digital_care_cart_count($conexion);
ob_start();
digital_care_header($conexion);
$headerHtml = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Digital Care - Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
  />
  <link rel="stylesheet" href="./styles.css" />
</head>
<body>
  <main class="login-page">
    <section class="login-card" aria-labelledby="login-title">
      <h1 id="login-title" class="brand">
        <span class="brand-blue">Digital</span>
        <span class="brand-green">Care</span>
      </h1>

      <form id="loginForm" class="login-form" action="validar_login.php" method="POST" novalidate>
        <label class="sr-only" for="email">Correo electrónico</label>
        <input
          id="email"
          name="email"
          type="email"
          placeholder="Correo electrónico."
          autocomplete="email"
          required
        />

        <label class="sr-only" for="password">Contraseña</label>
        <div class="password-wrap">
          <input
            id="password"
            name="password"
            type="password"
            placeholder="Contraseña"
            autocomplete="current-password"
            minlength="6"
            required
          />
          <button id="togglePassword" type="button" class="icon-btn" aria-label="Mostrar contraseña">
            👁
          </button>
        </div>

        <button id="submitBtn" type="submit" class="submit-btn">INICIAR SESIÓN</button>
        <a class="google-btn" href="google_login.php" aria-label="Iniciar con Google">
          <span class="google-mark">G</span>
          <span>Iniciar con Google</span>
        </a>
        <p id="feedback" class="feedback" role="status" aria-live="polite"></p>
      </form>

      <nav class="links" aria-label="enlaces de acceso">
        <a href="./recuperar.php" id="forgotPassword">Olvidé mi contraseña</a>
        <a href="./registro.php" id="createAccount">Crear una nueva cuenta</a>
      </nav>
    </section>
  </main>

  <template id="dashboardTemplate">
    <main class="dashboard-page">
      <?= $headerHtml ?>

      <section class="dashboard-content">
        <h2 class="welcome">Hola, <?php echo isset($_SESSION['nombre']) ? htmlspecialchars($_SESSION['nombre']) : 'Usuario'; ?> 👋</h2>
        <p class="subtitle">Bienvenido a Digital Care</p>

        <div class="stats-grid">
          <article class="stat-card" data-module="proximos">
            <span class="stat-icon orange">🕘</span>
            <div>
              <p class="stat-label">Próximos Trabajos</p>
              <p class="stat-value"><?php echo $total_trabajos_pendientes; ?></p>
              <small class="stat-note">Tienes trabajos pendientes que requieren atención</small>
            </div>
          </article>
          <article class="stat-card" data-module="completados">
            <span class="stat-icon green">✅</span>
            <div>
              <p class="stat-label">Completados</p>
              <p class="stat-value"><?php echo $total_completados; ?></p>
              <small class="stat-note">Trabajos finalizados este mes</small>
            </div>
          </article>
          <article class="stat-card" data-module="alertas">
            <span class="stat-icon blue">ⓘ</span>
            <div>
              <p class="stat-label">Alertas</p>
              <p class="stat-value"><?php echo $total_alertas_stock; ?></p>
              <small class="stat-note">Repuestos con stock bajo</small>
            </div>
          </article>
          <article class="stat-card" data-module="clientes">
            <span class="stat-icon blue">👥</span>
            <div>
              <p class="stat-label">Clientes registrados</p>
              <p class="stat-value"><?php echo $total_clientes; ?></p>
              <small class="stat-note">Cuentas guardadas en MySQL</small>
            </div>
          </article>
          <article class="stat-card" data-module="reportes">
            <span class="stat-icon green">$</span>
            <div>
              <p class="stat-label">Ganancias</p>
              <p class="stat-value">$<?php echo number_format($total_ganancias, 2, ',', '.'); ?></p>
              <small class="stat-note">Pedidos con pago confirmado</small>
            </div>
          </article>
          <article class="stat-card" data-module="repuestos">
            <span class="stat-icon orange">📦</span>
            <div>
              <p class="stat-label">Stock</p>
              <p class="stat-value"><?php echo $total_stock; ?></p>
              <small class="stat-note">Unidades disponibles</small>
            </div>
          </article>
          <article class="stat-card" data-module="pedidos">
            <span class="stat-icon blue">🧾</span>
            <div>
              <p class="stat-label">Pedidos</p>
              <p class="stat-value"><?php echo $total_pedidos; ?></p>
              <small class="stat-note">Compras registradas</small>
            </div>
          </article>
          <article class="stat-card" data-module="servicios">
            <span class="stat-icon green">🧰</span>
            <div>
              <p class="stat-label">Servicios activos</p>
              <p class="stat-value"><?php echo $total_servicios; ?></p>
              <small class="stat-note">Catálogo disponible</small>
            </div>
          </article>
        </div>

        <div class="menu-grid">
          <article class="menu-card" data-module="trabajos"><span class="menu-dot purple">🛠</span><h3>Ver Trabajos</h3><p>Gestiona reparaciones</p></article>
          <article class="menu-card" data-module="repuestos"><span class="menu-dot green">📦</span><h3>Repuestos</h3><p>Inventario disponible</p></article>
          <a class="menu-card" href="mis_pedidos.php" style="text-decoration:none;color:inherit"><span class="menu-dot blue">🛒</span><h3>Mis Pedidos</h3><p>Compras y estados de pago</p></a>
          <article class="menu-card" data-module="mantenimiento"><span class="menu-dot orange">⚙</span><h3>Mantenimiento</h3><p>Tareas programadas</p></article>
          <article class="menu-card" data-module="clientes"><span class="menu-dot blue">👥</span><h3>Clientes</h3><p>Base de datos</p></article>
          <article class="menu-card" data-module="servicios"><span class="menu-dot blue">🧰</span><h3>Servicios</h3><p>Catálogo completo</p></article>
          <article class="menu-card" data-module="reportes"><span class="menu-dot purple">📊</span><h3>Reportes</h3><p>Estadísticas del negocio</p></article>
          <article class="menu-card" data-module="perfil"><span class="menu-dot pink">🧑</span><h3>Mi Perfil</h3><p>Configuración de cuenta</p></article>
        </div>

        <section class="activity" style="margin-bottom: 24px;">
          <h3>Estado del proyecto</h3>
          <p style="margin:0 0 10px;color:#576f78;"><strong style="color:#17b67f;">Digital Care está completo al <?php echo $porcentaje_avance; ?>%</strong></p>
          <div style="height:12px;background:#d7e3e8;border-radius:99px;overflow:hidden;margin-bottom:14px;"><div style="height:100%;width:<?php echo $porcentaje_avance; ?>%;background:linear-gradient(90deg,#17b67f,#2397dd);border-radius:99px;"></div></div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <?php foreach ($estado_modulos as $estado): $color = $estado['estado'] === 'Completado' ? '#17b67f' : ($estado['estado'] === 'En progreso' ? '#f59d18' : '#8fa3ad'); ?>
              <span style="border:1px solid <?php echo $color; ?>;color:<?php echo $color; ?>;border-radius:999px;padding:5px 9px;font-size:.78rem;font-weight:700;"><?php echo $estado['estado'] === 'Completado' ? '✓' : ($estado['estado'] === 'En progreso' ? '◐' : '○'); ?> <?php echo e($estado['nombre']); ?></span>
            <?php endforeach; ?>
          </div>
        </section>

        <section class="activity">
          <h3>Actividad Reciente</h3>
          <ul>
            <?php if (empty($recientes)): ?>
                <li class="act blue">No hay actividad reciente registrada.</li>
            <?php else: ?>
                <?php foreach ($recientes as $act): 
                    $clase = ($act['estado'] == 'Completado') ? 'green' : (($act['estado'] == 'En proceso') ? 'yellow' : 'blue');
                ?>
                    <li class="act <?php echo $clase; ?>">
                        <?php echo htmlspecialchars($act['tipo_equipo']); ?> - <?php echo htmlspecialchars($act['estado']); ?>
                        <span><?php echo date('d/m H:i', strtotime($act['fecha'])); ?></span>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
          </ul>
        </section>

      </section>
    </main>
  </template>

  <template id="moduleTemplate">
    <main class="dashboard-page">
      <?= $headerHtml ?>

      <section class="dashboard-content">
        <article class="module-view">
          <h2 id="moduleViewTitle">Módulo</h2>
          <p id="moduleViewSubtitle">Vista interna</p>
          <ul id="moduleViewList"></ul>
        </article>
      </section>
    </main>
  </template>

  <script>
    window.isLoggedIn = <?php echo isset($_SESSION['id_usuario']) ? 'true' : 'false'; ?>;
    window.userData = <?php echo json_encode([
      'nombre' => $_SESSION['nombre'] ?? '',
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;

    (function showFeedbackFromQuery(){
      try {
        const params = new URLSearchParams(window.location.search);
        const msg = params.get("msg");
        const type = params.get("type");
        const feedback = document.getElementById("feedback");
        if (msg && msg.startsWith("Google OAuth no esta configurado.")) {
          window.history.replaceState({}, document.title, "index.php");
          return;
        }
        if(msg && feedback) {
          feedback.textContent = msg;
          feedback.className = 'feedback ' + (type || '');
        }
      } catch(e) { /* ignore */ }
    })();
  </script>
  <script src="./script.js"></script>
</body>
</html>
