<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');

session_start();
require_once 'conexion.php';
require_once 'database_schema.php';
require_once 'layout.php';
require_once 'pagos_simulados.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location:index.php');
    exit;
}

ensure_digitalcare_shop_schema($conexion);

$usuarioId = (int) $_SESSION['id_usuario'];
$operacion = trim((string) ($_GET['op'] ?? $_POST['op'] ?? ''));
$error = '';
$resultadoFinal = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $validacion = validar_tarjeta_prueba($_POST);
    if (!$validacion['ok']) {
        $error = $validacion['error'];
    } else {
        $resultadoFinal = aplicar_resultado_pago_simulado($conexion, $usuarioId, $operacion, $validacion['resultado'], $validacion);
        if (!$resultadoFinal['ok']) {
            $error = $resultadoFinal['error'];
            $resultadoFinal = null;
        }
    }
}

$stmt = db_prepare($conexion, 'SELECT p.*,pg.estado pago_estado,pg.referencia_proveedor,pg.tarjeta_ultimos4,pg.tipo_tarjeta
    FROM pedidos p
    JOIN pagos pg ON pg.pedido_id = p.id
    WHERE p.cliente_id = ? AND pg.numero_operacion = ?
    LIMIT 1');
mysqli_stmt_bind_param($stmt, 'is', $usuarioId, $operacion);
mysqli_stmt_execute($stmt);
$pedido = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$pedido) {
    http_response_code(404);
    $error = 'No se encontró la operación de prueba.';
}

$detalles = [];
if ($pedido) {
    $pedidoId = (int) $pedido['id'];
    $stmt = db_prepare($conexion, 'SELECT d.cantidad,d.precio_unitario,d.subtotal,r.nombre FROM detalle_pedido d JOIN repuestos r ON r.id_repuesto=d.producto_id WHERE d.pedido_id=? ORDER BY d.id');
    mysqli_stmt_bind_param($stmt, 'i', $pedidoId);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    while ($rs && $row = mysqli_fetch_assoc($rs)) $detalles[] = $row;
    mysqli_stmt_close($stmt);
}

function clase_pago(string $estado): string
{
    return match ($estado) {
        'Aprobado', 'Pagado' => 'approved',
        'Rechazado' => 'rejected',
        default => 'pending',
    };
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <?= csrf_meta() ?>
  <title>Mercado Pago de prueba | Digital Care</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<main class="dashboard-page">
  <?php digital_care_header($conexion); ?>
  <section class="dashboard-content">
    <article class="module-view checkout-shell">
      <div class="checkout-title">
        <div>
          <a class="brand small brand-link checkout-brand" href="index.php?view=dashboard">
            <span class="brand-blue">Digital</span><span class="brand-green">Care</span>
          </a>
          <h2>Finalizar compra</h2>
          <p>Mercado Pago - checkout sandbox/mock para pruebas y demostración.</p>
        </div>
        <span class="test-badge">ENTORNO DE PRUEBA - No se realizará ningún cobro real.</span>
      </div>

      <?php if ($resultadoFinal): ?>
        <?php $estado = (string) $resultadoFinal['estado_pago']; ?>
        <section class="payment-status <?= clase_pago($estado) ?>">
          <h3>Pago <?= e(mb_strtolower($estado, 'UTF-8')) ?></h3>
          <p>
            <?php if ($estado === 'Aprobado'): ?>
              Tu pedido fue marcado como pagado y el stock se descontó correctamente.
            <?php elseif ($estado === 'Rechazado'): ?>
              El intento quedó registrado como rechazado. No se descontó stock.
            <?php else: ?>
              El pago quedó pendiente. No se descontará stock hasta que sea aprobado.
            <?php endif; ?>
          </p>
          <p><b>Operación:</b> <?= e($resultadoFinal['numero_operacion']) ?> · <b>Transacción:</b> <?= e($resultadoFinal['referencia']) ?></p>
          <div class="checkout-actions">
            <a class="primary" href="mis_pedidos.php">Ver mis pedidos</a>
            <a class="secondary" href="reportes.php">Ver reportes</a>
          </div>
        </section>
      <?php elseif ($pedido): ?>
        <?php if ($error): ?><div class="friendly-error"><?= e($error) ?></div><?php endif; ?>
        <div class="checkout-layout">
          <section class="checkout-panel">
            <h3>Datos del comprador</h3>
            <div class="buyer-grid">
              <p><span>Nombre</span><b><?= e($pedido['nombre_completo']) ?></b></p>
              <p><span>DNI</span><b><?= e($pedido['dni']) ?></b></p>
              <p><span>Email</span><b><?= e($pedido['email']) ?></b></p>
              <p><span>Teléfono</span><b><?= e($pedido['telefono']) ?></b></p>
              <p><span>Dirección</span><b><?= e($pedido['direccion']) ?>, <?= e($pedido['ciudad']) ?></b></p>
              <p><span>Operación</span><b><?= e($pedido['numero_operacion']) ?></b></p>
            </div>

            <h3>Método de pago</h3>
            <div class="method-box">
              <strong>Mercado Pago</strong>
              <small>Simulación local. Usá solamente tarjetas y documentos ficticios.</small>
            </div>

            <form method="post" class="test-card-form" autocomplete="off" novalidate>
              <?= csrf_input() ?>
              <input type="hidden" name="op" value="<?= e($operacion) ?>">
              <label>Nombre del titular
                <input required name="titular" maxlength="150" placeholder="APRO TEST">
              </label>
              <label>Número de tarjeta de prueba
                <input required name="numero_tarjeta" inputmode="numeric" maxlength="23" placeholder="4509 9535 6623 3704">
              </label>
              <div class="form-row">
                <label>Vencimiento
                  <input required name="vencimiento" maxlength="5" placeholder="12/30">
                </label>
                <label>CVV
                  <input required name="cvv" inputmode="numeric" maxlength="4" placeholder="123">
                </label>
              </div>
              <label>Documento ficticio
                <input required name="documento" maxlength="20" placeholder="12345678">
              </label>

              <fieldset class="result-options">
                <legend>Resultado de prueba</legend>
                <label><input type="radio" name="resultado" value="aprobado" required> Pago aprobado</label>
                <label><input type="radio" name="resultado" value="rechazado" required> Pago rechazado</label>
                <label><input type="radio" name="resultado" value="pendiente" required> Pago pendiente</label>
              </fieldset>

              <div class="security-note">Conexión segura de demostración. No se guardan número completo de tarjeta ni CVV.</div>
              <button class="success" type="submit">Continuar con el pago</button>
              <a class="back-link" href="carrito.php">Volver al carrito</a>
            </form>
          </section>

          <aside class="checkout-summary">
            <h3>Resumen del pedido</h3>
            <?php foreach ($detalles as $detalle): ?>
              <div class="summary-line">
                <div>
                  <b><?= e($detalle['nombre']) ?></b>
                  <small><?= (int) $detalle['cantidad'] ?> x $<?= number_format((float) $detalle['precio_unitario'], 2, ',', '.') ?></small>
                </div>
                <strong>$<?= number_format((float) $detalle['subtotal'], 2, ',', '.') ?></strong>
              </div>
            <?php endforeach; ?>
            <div class="summary-total">
              <span>Total</span>
              <b>$<?= number_format((float) $pedido['total'], 2, ',', '.') ?></b>
            </div>
          </aside>
        </div>
      <?php else: ?>
        <div class="friendly-error"><?= e($error) ?></div>
        <a class="primary" href="carrito.php">Volver al carrito</a>
      <?php endif; ?>
    </article>
  </section>
</main>
<style>
  .checkout-shell{max-width:1180px}.checkout-title{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;flex-wrap:wrap}.checkout-brand{display:inline-flex;margin-bottom:8px}.checkout-title h2{margin:0;color:#1e4e95}.checkout-title p{margin:7px 0 0;color:#576f78}.test-badge{background:#fff4d8;border:1px solid #e8bd55;color:#8a5b00;border-radius:8px;padding:10px 12px;font-weight:800;max-width:390px}.checkout-layout{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(320px,.75fr);gap:22px;margin-top:22px}.checkout-panel,.checkout-summary,.payment-status{background:#fff;border:1px solid #d7e3e8;border-radius:8px;padding:22px}.checkout-panel h3,.checkout-summary h3{margin:0 0 14px;color:#1e4e95}.buyer-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px;margin-bottom:24px}.buyer-grid p{margin:0;background:#f8fbfd;border:1px solid #e4eef2;border-radius:8px;padding:11px}.buyer-grid span,.summary-line small{display:block;color:#576f78;font-size:.78rem}.buyer-grid b{color:#1f3762}.method-box{border:1px solid #b8d4df;border-radius:8px;padding:14px;margin-bottom:18px;background:#f0f8ff}.method-box strong{display:block;color:#1e4e95}.method-box small{color:#576f78}.test-card-form{display:grid;gap:13px}.test-card-form label{font-weight:800;color:#1f3762;font-size:.86rem}.test-card-form input{box-sizing:border-box;width:100%;margin-top:6px;border:1px solid #b8d4df;border-radius:8px;padding:11px;font:inherit}.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}.result-options{border:1px solid #d7e3e8;border-radius:8px;padding:13px;display:grid;gap:8px}.result-options legend{font-weight:800;color:#1e4e95}.result-options label{display:flex;gap:8px;align-items:center}.result-options input{width:auto;margin:0}.security-note{background:#e9f8f0;color:#0f7e52;border:1px solid #bce9d3;border-radius:8px;padding:10px;font-weight:700}.success,.primary,.secondary{border:0;border-radius:999px;color:#fff;padding:12px 18px;font-weight:800;text-decoration:none;text-align:center;cursor:pointer}.success{background:linear-gradient(90deg,#17b67f,#12a36a)}.primary{background:linear-gradient(90deg,#2397dd,#2a65de)}.secondary{background:#17b67f}.back-link{color:#1e4e95;font-weight:800;text-align:center;text-decoration:none}.checkout-summary{height:max-content;background:#f8fbfd}.summary-line{display:flex;justify-content:space-between;gap:12px;border-bottom:1px solid #d7e3e8;padding:12px 0}.summary-line b{color:#1f3762}.summary-line strong{color:#17b67f;white-space:nowrap}.summary-total{display:flex;justify-content:space-between;gap:12px;padding-top:16px;color:#1e4e95;font-size:1.15rem}.summary-total b{color:#17b67f}.friendly-error{background:#fff1f1;color:#a53030;border:1px solid #f1b5b5;border-radius:8px;padding:12px;margin:18px 0;font-weight:700}.payment-status{text-align:center;margin-top:24px}.payment-status h3{font-size:1.8rem;margin:0 0 10px}.payment-status.approved h3{color:#17b67f}.payment-status.rejected h3{color:#d94b4b}.payment-status.pending h3{color:#d75a00}.payment-status p{color:#576f78}.checkout-actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:18px}@media(max-width:900px){.checkout-layout{grid-template-columns:1fr}}@media(max-width:560px){.form-row{grid-template-columns:1fr}.test-badge{max-width:none}}
</style>
</body>
</html>
