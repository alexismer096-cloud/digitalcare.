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

$rango = $_GET['rango'] ?? 'mes';
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');
if ($rango === 'hoy') {
    $desde = $hasta = date('Y-m-d');
}
if ($rango === '7dias') {
    $desde = date('Y-m-d', strtotime('-6 days'));
    $hasta = date('Y-m-d');
}
if ($rango === 'anio') {
    $desde = date('Y-01-01');
    $hasta = date('Y-m-d');
}

function scalar_report(mysqli $db, string $sql, string $types = '', mixed ...$args): mixed
{
    $stmt = db_prepare($db, $sql);
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$args);
    }
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row['n'] ?? 0;
}

$ingresos = (float) scalar_report($conexion, "SELECT COALESCE(SUM(total),0) n FROM pedidos WHERE estado_pago IN ('Aprobado','Pagado') AND DATE(fecha) BETWEEN ? AND ?", 'ss', $desde, $hasta);
$egresos = (float) scalar_report($conexion, 'SELECT COALESCE(SUM(d.cantidad * r.costo),0) n FROM detalle_pedido d JOIN repuestos r ON r.id_repuesto=d.producto_id JOIN pedidos p ON p.id=d.pedido_id WHERE p.estado_pago IN ("Aprobado","Pagado") AND DATE(p.fecha) BETWEEN ? AND ?', 'ss', $desde, $hasta);
$ganancias = max(0, $ingresos - $egresos);

$metricas = [
    'Clientes nuevos' => scalar_report($conexion, 'SELECT COUNT(*) n FROM usuarios WHERE DATE(fecha_registro) BETWEEN ? AND ?', 'ss', $desde, $hasta),
    'Trabajos realizados' => scalar_report($conexion, 'SELECT COUNT(*) n FROM trabajos WHERE DATE(fecha_inicio) BETWEEN ? AND ?', 'ss', $desde, $hasta),
    'Trabajos pendientes' => scalar_report($conexion, "SELECT COUNT(*) n FROM trabajos WHERE estado IN ('Pendiente','En proceso','En reparación')"),
    'Finalizados' => scalar_report($conexion, "SELECT COUNT(*) n FROM trabajos WHERE estado = 'Completado' AND (fecha_fin IS NULL OR DATE(fecha_fin) BETWEEN ? AND ?)", 'ss', $desde, $hasta),
    'Mantenimientos' => scalar_report($conexion, 'SELECT COUNT(*) n FROM mantenimiento WHERE fecha_programada BETWEEN ? AND ?', 'ss', $desde, $hasta),
    'Servicios realizados' => scalar_report($conexion, 'SELECT COALESCE(SUM(ts.cantidad),0) n FROM trabajo_servicio ts JOIN trabajos t ON t.id_trabajo=ts.id_trabajo WHERE DATE(t.fecha_inicio) BETWEEN ? AND ?', 'ss', $desde, $hasta),
    'Repuestos vendidos' => scalar_report($conexion, 'SELECT COALESCE(SUM(cantidad),0) n FROM detalle_pedido d JOIN pedidos p ON p.id=d.pedido_id WHERE p.estado_pago IN ("Aprobado","Pagado") AND DATE(p.fecha) BETWEEN ? AND ?', 'ss', $desde, $hasta),
    'Ventas aprobadas' => scalar_report($conexion, 'SELECT COUNT(*) n FROM pedidos WHERE estado_pago IN ("Aprobado","Pagado") AND DATE(fecha) BETWEEN ? AND ?', 'ss', $desde, $hasta),
    'Cantidad de pedidos' => scalar_report($conexion, 'SELECT COUNT(*) n FROM pedidos WHERE DATE(fecha) BETWEEN ? AND ?', 'ss', $desde, $hasta),
    'Pagos aprobados' => scalar_report($conexion, 'SELECT COUNT(*) n FROM pagos WHERE estado IN ("Aprobado","Pagado") AND DATE(fecha) BETWEEN ? AND ?', 'ss', $desde, $hasta),
    'Pagos pendientes' => scalar_report($conexion, 'SELECT COUNT(*) n FROM pagos WHERE estado = "Pendiente" AND DATE(fecha) BETWEEN ? AND ?', 'ss', $desde, $hasta),
    'Pagos rechazados' => scalar_report($conexion, 'SELECT COUNT(*) n FROM pagos WHERE estado = "Rechazado" AND DATE(fecha) BETWEEN ? AND ?', 'ss', $desde, $hasta),
    'Stock crítico' => scalar_report($conexion, 'SELECT COUNT(*) n FROM repuestos WHERE stock <= stock_minimo'),
    'Ingresos' => '$' . number_format($ingresos, 2, ',', '.'),
    'Egresos' => '$' . number_format($egresos, 2, ',', '.'),
];

if (($_GET['export'] ?? '') === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="digital-care-reportes-' . date('Ymd') . '.xls"');
    echo "\xEF\xBB\xBF";
    echo "Métrica\tValor\n";
    foreach ($metricas as $nombre => $valor) {
        echo $nombre . "\t" . $valor . "\n";
    }
    echo "Ganancias netas\t" . number_format($ganancias, 2, ',', '.') . "\n";
    exit;
}

$stmt = db_prepare($conexion, 'SELECT r.nombre, SUM(d.cantidad) n FROM detalle_pedido d JOIN repuestos r ON r.id_repuesto=d.producto_id JOIN pedidos p ON p.id=d.pedido_id WHERE p.estado_pago IN ("Aprobado","Pagado") AND DATE(p.fecha) BETWEEN ? AND ? GROUP BY d.producto_id ORDER BY n DESC LIMIT 5');
mysqli_stmt_bind_param($stmt, 'ss', $desde, $hasta);
mysqli_stmt_execute($stmt);
$repuestos = mysqli_stmt_get_result($stmt);

$stmtServicios = db_prepare($conexion, 'SELECT s.nombre, SUM(ts.cantidad) n FROM trabajo_servicio ts JOIN servicios s ON s.id_servicio=ts.id_servicio JOIN trabajos t ON t.id_trabajo=ts.id_trabajo WHERE DATE(t.fecha_inicio) BETWEEN ? AND ? GROUP BY ts.id_servicio ORDER BY n DESC LIMIT 5');
mysqli_stmt_bind_param($stmtServicios, 'ss', $desde, $hasta);
mysqli_stmt_execute($stmtServicios);
$servicios = mysqli_stmt_get_result($stmtServicios);

$stockBajo = mysqli_query($conexion, 'SELECT nombre, stock, stock_minimo FROM repuestos WHERE stock <= stock_minimo ORDER BY stock ASC, nombre LIMIT 8');
$trabajosPendientes = mysqli_query($conexion, "SELECT t.id_trabajo,t.estado,s.tipo_equipo,u.nombre,u.apellido FROM trabajos t LEFT JOIN solicitudes s ON s.id_solicitud=t.id_solicitud LEFT JOIN usuarios u ON u.id_usuario=t.id_usuario WHERE t.estado IN ('Pendiente','En proceso','En reparación') ORDER BY t.fecha_inicio DESC, t.id_trabajo DESC LIMIT 8");
$trabajosTerminados = mysqli_query($conexion, "SELECT t.id_trabajo,t.fecha_fin,s.tipo_equipo,u.nombre,u.apellido FROM trabajos t LEFT JOIN solicitudes s ON s.id_solicitud=t.id_solicitud LEFT JOIN usuarios u ON u.id_usuario=t.id_usuario WHERE t.estado = 'Completado' ORDER BY COALESCE(t.fecha_fin,t.fecha_inicio) DESC, t.id_trabajo DESC LIMIT 8");
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reportes | Digital Care</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<main class="dashboard-page">
  <?php digital_care_header($conexion); ?>
  <section class="dashboard-content">
    <article class="module-view">
      <h2>Reportes y estadísticas</h2>
      <form method="get" class="filters">
        <select name="rango">
          <option value="hoy" <?= $rango === 'hoy' ? 'selected' : '' ?>>Hoy</option>
          <option value="7dias" <?= $rango === '7dias' ? 'selected' : '' ?>>Últimos 7 días</option>
          <option value="mes" <?= $rango === 'mes' ? 'selected' : '' ?>>Este mes</option>
          <option value="anio" <?= $rango === 'anio' ? 'selected' : '' ?>>Este año</option>
          <option value="personalizado" <?= $rango === 'personalizado' ? 'selected' : '' ?>>Rango personalizado</option>
        </select>
        <input type="date" name="desde" value="<?= e($desde) ?>">
        <input type="date" name="hasta" value="<?= e($hasta) ?>">
        <button>Actualizar</button>
        <a class="export" href="?rango=<?= urlencode($rango) ?>&desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>&export=excel">Exportar Excel</a>
        <button type="button" onclick="window.print()">Exportar PDF</button>
      </form>
      <div class="stats-grid">
        <?php foreach ($metricas as $nombre => $valor): ?>
          <article class="stat-card" style="cursor:default"><div><p class="stat-label"><?= e($nombre) ?></p><p class="stat-value"><?= e($valor) ?></p></div></article>
        <?php endforeach; ?>
        <article class="stat-card" style="cursor:default"><div><p class="stat-label">Ganancias netas</p><p class="stat-value">$<?= number_format($ganancias, 2, ',', '.') ?></p></div></article>
      </div>
      <div class="charts">
        <article class="maint-card"><h3>Repuestos más vendidos</h3><ul><?php while ($row = $repuestos ? mysqli_fetch_assoc($repuestos) : null): ?><li><?= e($row['nombre']) ?> <b><?= (int) $row['n'] ?></b></li><?php endwhile; ?></ul></article>
        <article class="maint-card"><h3>Servicios más solicitados</h3><ul><?php while ($row = $servicios ? mysqli_fetch_assoc($servicios) : null): ?><li><?= e($row['nombre']) ?> <b><?= (int) $row['n'] ?></b></li><?php endwhile; ?></ul></article>
        <article class="maint-card"><h3>Stock bajo</h3><ul><?php while ($row = $stockBajo ? mysqli_fetch_assoc($stockBajo) : null): ?><li><?= e($row['nombre']) ?> <b><?= (int) $row['stock'] ?> / <?= (int) $row['stock_minimo'] ?></b></li><?php endwhile; ?></ul></article>
        <article class="maint-card"><h3>Trabajos pendientes</h3><ul><?php while ($row = $trabajosPendientes ? mysqli_fetch_assoc($trabajosPendientes) : null): ?><li>#<?= (int) $row['id_trabajo'] ?> <?= e($row['tipo_equipo'] ?? 'Equipo') ?> <b><?= e($row['estado']) ?></b></li><?php endwhile; ?></ul></article>
        <article class="maint-card"><h3>Trabajos terminados</h3><ul><?php while ($row = $trabajosTerminados ? mysqli_fetch_assoc($trabajosTerminados) : null): ?><li>#<?= (int) $row['id_trabajo'] ?> <?= e($row['tipo_equipo'] ?? 'Equipo') ?> <b><?= e($row['fecha_fin'] ?? 'Finalizado') ?></b></li><?php endwhile; ?></ul></article>
      </div>
    </article>
  </section>
</main>
<style>
  h2{color:#1e4e95}.filters{display:flex;gap:8px;flex-wrap:wrap;margin:15px 0}.charts{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px;margin-top:20px}.maint-card{padding:20px}.maint-card li{display:flex;justify-content:space-between;border-bottom:1px solid #e7eef2;padding:8px 0}select,input{padding:9px;border:1px solid #b8d4df;border-radius:8px}button,.export{border:0;border-radius:999px;background:#2397dd;color:#fff;padding:9px 16px;font-weight:700;text-decoration:none;cursor:pointer}
  @media print{.topbar,.filters{display:none}.dashboard-page{background:#fff}.module-view{box-shadow:none;border:0}}
</style>
</body>
</html>
