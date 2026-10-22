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

$usuario = (int) $_SESSION['id_usuario'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'crear';

    if ($action === 'completar') {
        $id = filter_input(INPUT_POST, 'id_mantenimiento', FILTER_VALIDATE_INT);
        if ($id) {
            mysqli_begin_transaction($conexion);
            $estado = 'Completado';
            $stmt = db_prepare($conexion, 'UPDATE mantenimiento SET estado = ?, completado_en = NOW() WHERE id_mantenimiento = ?');
            mysqli_stmt_bind_param($stmt, 'si', $estado, $id);
            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $stmt = db_prepare($conexion, 'UPDATE trabajos t JOIN mantenimiento m ON m.id_trabajo = t.id_trabajo SET t.estado = "Completado", t.fecha_fin = CURDATE() WHERE m.id_mantenimiento = ? AND t.estado <> "Completado"');
            mysqli_stmt_bind_param($stmt, 'i', $id);
            $ok = $ok && mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $tipo = 'mantenimiento';
            $descripcion = "Mantenimiento #$id completado";
            $stmt = db_prepare($conexion, 'INSERT INTO historial_mantenimiento (id_mantenimiento, estado_anterior, estado_nuevo, observacion, id_usuario) VALUES (?, "Pendiente", "Completado", ?, ?)');
            mysqli_stmt_bind_param($stmt, 'isi', $id, $descripcion, $usuario);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $stmt = db_prepare($conexion, 'INSERT INTO actividad_reciente (tipo, descripcion, referencia_id, id_usuario) VALUES (?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'ssii', $tipo, $descripcion, $id, $usuario);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $ok ? mysqli_commit($conexion) : mysqli_rollback($conexion);
        }
        header('Location:mantenimiento.php?estado=' . urlencode($estado));
        exit;
    }

    $tipo = trim($_POST['tipo'] ?? '');
    $desc = trim($_POST['descripcion'] ?? '');
    $fecha = $_POST['fecha_programada'] ?? '';
    $estado = $_POST['estado'] ?? 'Pendiente';
    $trabajoId = filter_input(INPUT_POST, 'id_trabajo', FILTER_VALIDATE_INT) ?: null;

    if ($tipo && $desc && $fecha && in_array($estado, ['Pendiente', 'En proceso', 'Completado'], true)) {
        $stmt = db_prepare($conexion, 'INSERT INTO mantenimiento (id_trabajo, tipo, descripcion, estado, fecha_programada, id_usuario) VALUES (?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'issssi', $trabajoId, $tipo, $desc, $estado, $fecha, $usuario);
        mysqli_stmt_execute($stmt);
        $mantenimientoId = (int) mysqli_insert_id($conexion);
        mysqli_stmt_close($stmt);

        if ($trabajoId) {
            $fechaFinSql = $estado === 'Completado' ? ', fecha_fin = CURDATE()' : ', fecha_fin = NULL';
            $stmt = db_prepare($conexion, "UPDATE trabajos SET estado = ?$fechaFinSql WHERE id_trabajo = ?");
            mysqli_stmt_bind_param($stmt, 'si', $estado, $trabajoId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        $observacion = 'Tarea creada desde el modulo de mantenimiento.';
        $stmt = db_prepare($conexion, 'INSERT INTO historial_mantenimiento (id_mantenimiento, estado_nuevo, observacion, id_usuario) VALUES (?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'issi', $mantenimientoId, $estado, $observacion, $usuario);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header('Location:mantenimiento.php');
        exit;
    }
    $error = 'Completá todos los campos correctamente.';
}

$busqueda = trim((string) ($_GET['q'] ?? ''));
$estadoFiltro = trim((string) ($_GET['estado'] ?? ''));
$estadosValidos = ['Pendiente', 'En proceso', 'Completado'];
if ($estadoFiltro === 'Finalizado' || $estadoFiltro === 'Finalizados') {
    $estadoFiltro = 'Completado';
}
if (!in_array($estadoFiltro, $estadosValidos, true)) {
    $estadoFiltro = '';
}
$busquedaEstado = mb_strtolower($busqueda, 'UTF-8');
if ($estadoFiltro === '' && in_array($busquedaEstado, ['finalizado', 'finalizados', 'completado', 'completados'], true)) {
    $estadoFiltro = 'Completado';
    $busqueda = '';
}
if ($estadoFiltro === '' && $busquedaEstado === 'en proceso') {
    $estadoFiltro = 'En proceso';
    $busqueda = '';
}
if ($estadoFiltro === '' && $busquedaEstado === 'pendiente') {
    $estadoFiltro = 'Pendiente';
    $busqueda = '';
}

$trabajos = mysqli_query($conexion, 'SELECT t.id_trabajo, s.tipo_equipo, u.nombre cliente FROM trabajos t LEFT JOIN solicitudes s ON s.id_solicitud=t.id_solicitud LEFT JOIN usuarios u ON u.id_usuario=t.id_usuario ORDER BY t.id_trabajo DESC');
$sql = 'SELECT m.*, u.nombre responsable, t.estado trabajo_estado, s.tipo_equipo
    FROM mantenimiento m
    LEFT JOIN usuarios u ON u.id_usuario = m.id_usuario
    LEFT JOIN trabajos t ON t.id_trabajo = m.id_trabajo
    LEFT JOIN solicitudes s ON s.id_solicitud = t.id_solicitud';
$where = [];
$types = '';
$params = [];

if ($estadoFiltro !== '') {
    $where[] = 'm.estado = ?';
    $types .= 's';
    $params[] = $estadoFiltro;
}

if ($busqueda !== '') {
    $where[] = '(m.tipo LIKE ? OR m.descripcion LIKE ? OR u.nombre LIKE ? OR s.tipo_equipo LIKE ? OR t.estado LIKE ? OR CAST(m.id_mantenimiento AS CHAR) LIKE ? OR CAST(m.id_trabajo AS CHAR) LIKE ?)';
    $like = '%' . $busqueda . '%';
    $types .= 'sssssss';
    array_push($params, $like, $like, $like, $like, $like, $like, $like);
}

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY m.fecha_programada DESC, m.id_mantenimiento DESC';

$stmt = db_prepare($conexion, $sql);
if ($params) {
    $bindParams = [$types];
    foreach ($params as $key => $value) {
        $bindParams[] = &$params[$key];
    }
    mysqli_stmt_bind_param($stmt, ...$bindParams);
}
mysqli_stmt_execute($stmt);
$mantenimientos = mysqli_stmt_get_result($stmt);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Mantenimiento | Digital Care</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<main class="dashboard-page">
  <?php digital_care_header($conexion); ?>
  <section class="dashboard-content">
    <article class="module-view">
      <h2>Mantenimiento</h2>
      <p>Las tareas creadas desde trabajos aparecen automáticamente en este módulo.</p>
      <form method="post" class="maint-form">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="crear">
        <label>Trabajo
          <select name="id_trabajo"><option value="">Sin trabajo asociado</option><?php while ($trabajo = $trabajos ? mysqli_fetch_assoc($trabajos) : null): ?><option value="<?= (int) $trabajo['id_trabajo'] ?>">#<?= (int) $trabajo['id_trabajo'] ?> - <?= e($trabajo['tipo_equipo'] ?? 'Equipo') ?> - <?= e($trabajo['cliente'] ?? 'Cliente') ?></option><?php endwhile; ?></select>
        </label>
        <label>Tipo<input required name="tipo"></label>
        <label>Fecha<input required name="fecha_programada" type="date"></label>
        <label>Estado<select name="estado"><option>Pendiente</option><option>En proceso</option><option>Completado</option></select></label>
        <label>Descripción<input required name="descripcion"></label>
        <button>Registrar</button>
      </form>
      <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
      <form method="get" class="maint-filter">
        <label>Buscar
          <input type="search" name="q" value="<?= e($busqueda) ?>" placeholder="Buscar por tipo, descripcion, cliente, equipo o numero">
        </label>
        <label>Estado
          <select name="estado">
            <option value="">Todos los estados</option>
            <option value="Pendiente"<?= $estadoFiltro === 'Pendiente' ? ' selected' : '' ?>>Pendiente</option>
            <option value="En proceso"<?= $estadoFiltro === 'En proceso' ? ' selected' : '' ?>>En proceso</option>
            <option value="Completado"<?= $estadoFiltro === 'Completado' ? ' selected' : '' ?>>Finalizados</option>
          </select>
        </label>
        <button type="submit">Buscar</button>
        <a class="filter-link<?= $estadoFiltro === '' && $busqueda === '' ? ' active' : '' ?>" href="mantenimiento.php">Todos</a>
        <a class="filter-link<?= $estadoFiltro === 'En proceso' ? ' active' : '' ?>" href="mantenimiento.php?estado=<?= urlencode('En proceso') ?>">En proceso</a>
        <a class="filter-link<?= $estadoFiltro === 'Completado' ? ' active' : '' ?>" href="mantenimiento.php?estado=Completado">Finalizados</a>
      </form>
      <div class="maint-list">
        <?php $hayMantenimientos = false; ?>
        <?php while ($mantenimiento = $mantenimientos ? mysqli_fetch_assoc($mantenimientos) : null): ?>
          <?php $hayMantenimientos = true; ?>
          <article class="maint-card">
            <div class="row">
              <h3><?= e($mantenimiento['tipo']) ?></h3>
              <strong><?= e($mantenimiento['estado']) ?></strong>
            </div>
            <p><?= e($mantenimiento['descripcion']) ?></p>
            <p><b>Trabajo:</b> <?= $mantenimiento['id_trabajo'] ? '#' . (int) $mantenimiento['id_trabajo'] . ' - ' . e($mantenimiento['tipo_equipo'] ?? 'Equipo') : 'Sin asociar' ?></p>
            <small><?= date('d/m/Y', strtotime($mantenimiento['fecha_programada'])) ?> · <?= e($mantenimiento['responsable'] ?? 'Sin asignar') ?></small>
            <?php if ($mantenimiento['estado'] !== 'Completado'): ?>
              <form method="post" style="margin-top:10px">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="completar">
                <input type="hidden" name="id_mantenimiento" value="<?= (int) $mantenimiento['id_mantenimiento'] ?>">
                <button>Marcar completado</button>
              </form>
            <?php endif; ?>
          </article>
        <?php endwhile; ?>
        <?php if (!$hayMantenimientos): ?>
          <div class="empty-maint">No hay tareas de mantenimiento para esta busqueda.</div>
        <?php endif; ?>
      </div>
    </article>
  </section>
</main>
<style>
  h2{color:#1e4e95}.maint-form,.maint-filter{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:10px;background:#f0f8ff;padding:15px;border-radius:14px}.maint-filter{align-items:end;margin-top:16px;background:#fff;border:1px solid #d7e3e8}.row{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap}.maint-card{padding:16px;margin-top:14px}.maint-card strong{background:#f0f8ff;border-radius:999px;color:#1e4e95;padding:6px 10px}label{font-weight:700;color:#1f3762}input,select{width:100%;box-sizing:border-box;padding:9px;border:1px solid #b8d4df;border-radius:8px;margin-top:5px}button,.filter-link{border:0;border-radius:999px;background:#2397dd;color:#fff;padding:9px 14px;font-weight:700;cursor:pointer;text-align:center;text-decoration:none}.filter-link{background:#eef6fb;color:#1e4e95}.filter-link.active{background:#17b67f;color:#fff}.empty-maint{margin-top:14px;background:#f0f8ff;border-radius:8px;padding:30px;text-align:center;color:#576f78;font-weight:700}.error{color:#d94b4b;font-weight:700}
</style>
</body>
</html>
