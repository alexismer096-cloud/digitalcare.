<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
session_start();
require_once 'conexion.php';
require_once 'layout.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit;
}

$usuarioActual = (int) $_SESSION['id_usuario'];
$error = '';
$estadosTrabajo = ['Pendiente', 'En proceso', 'En reparación', 'Completado'];

function post_text(string $key, string $label, int $max = 500, bool $required = true): string
{
    $value = trim($_POST[$key] ?? '');
    if (($required && $value === '') || mb_strlen($value, 'UTF-8') > $max) {
        throw new InvalidArgumentException("Completá correctamente $label.");
    }
    return $value;
}

function estado_trabajo_clase(string $estado): string
{
    return match ($estado) {
        'Completado' => 'ok',
        'En proceso', 'En reparación' => 'progress',
        default => 'pending',
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $accion = $_POST['accion'] ?? 'crear';

        if ($accion === 'actualizar_estado') {
            $trabajoId = filter_input(INPUT_POST, 'id_trabajo', FILTER_VALIDATE_INT);
            $estado = post_text('estado', 'el estado', 50);

            if (!$trabajoId || !in_array($estado, $estadosTrabajo, true)) {
                throw new InvalidArgumentException('Seleccioná un trabajo y un estado válido.');
            }

            mysqli_begin_transaction($conexion);

            $stmt = db_prepare($conexion, 'SELECT id_solicitud FROM trabajos WHERE id_trabajo = ? FOR UPDATE');
            mysqli_stmt_bind_param($stmt, 'i', $trabajoId);
            mysqli_stmt_execute($stmt);
            $trabajoActual = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            if (!$trabajoActual) {
                mysqli_rollback($conexion);
                throw new InvalidArgumentException('El trabajo seleccionado no existe.');
            }

            $stmt = db_prepare($conexion, 'UPDATE trabajos SET estado = ?, fecha_fin = CASE WHEN ? = "Completado" THEN COALESCE(fecha_fin, CURDATE()) ELSE NULL END WHERE id_trabajo = ?');
            mysqli_stmt_bind_param($stmt, 'ssi', $estado, $estado, $trabajoId);
            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($ok && !empty($trabajoActual['id_solicitud'])) {
                $solicitudId = (int) $trabajoActual['id_solicitud'];
                $stmt = db_prepare($conexion, 'UPDATE solicitudes SET estado = ? WHERE id_solicitud = ?');
                mysqli_stmt_bind_param($stmt, 'si', $estado, $solicitudId);
                $ok = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            if ($ok) {
                $actividad = 'Trabajo #' . $trabajoId . ' cambió de estado a ' . $estado;
                $tipo = 'trabajo';
                $stmt = db_prepare($conexion, 'INSERT INTO actividad_reciente (tipo, descripcion, referencia_id, id_usuario) VALUES (?, ?, ?, ?)');
                mysqli_stmt_bind_param($stmt, 'ssii', $tipo, $actividad, $trabajoId, $usuarioActual);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                mysqli_commit($conexion);
                header('Location: trabajos.php?estado_ok=1');
                exit;
            }

            mysqli_rollback($conexion);
            $error = 'No se pudo actualizar el estado del trabajo.';
        } else {
        $clienteId = filter_input(INPUT_POST, 'id_cliente', FILTER_VALIDATE_INT);
        $tecnicoId = filter_input(INPUT_POST, 'id_tecnico', FILTER_VALIDATE_INT) ?: null;
        $servicioId = filter_input(INPUT_POST, 'id_servicio', FILTER_VALIDATE_INT) ?: null;
        $equipo = post_text('tipo_equipo', 'el equipo', 120);
        $problema = post_text('problema', 'el problema', 2000);
        $descripcion = post_text('descripcion', 'la descripción del trabajo', 2000);
        $diagnostico = post_text('diagnostico', 'el diagnóstico', 2000, false);
        $prioridad = post_text('prioridad', 'la prioridad', 20);
        $estado = post_text('estado', 'el estado', 50);
        $observaciones = post_text('observaciones', 'las observaciones', 2000, false);
        $costo = filter_input(INPUT_POST, 'costo', FILTER_VALIDATE_FLOAT);

        if (!$clienteId || $costo === false || $costo < 0) {
            throw new InvalidArgumentException('Seleccioná un cliente y un costo válido.');
        }
        if (!in_array($prioridad, ['Baja', 'Media', 'Alta', 'Urgente'], true)) {
            throw new InvalidArgumentException('La prioridad no es válida.');
        }

        if (!in_array($estado, $estadosTrabajo, true)) {
            throw new InvalidArgumentException('El estado no es válido.');
        }

        mysqli_begin_transaction($conexion);

        $stmt = db_prepare($conexion, 'INSERT INTO solicitudes (tipo_equipo, problema, estado, id_usuario) VALUES (?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'sssi', $equipo, $problema, $estado, $clienteId);
        $ok = mysqli_stmt_execute($stmt);
        $solicitudId = (int) mysqli_insert_id($conexion);
        mysqli_stmt_close($stmt);

        $stmt = db_prepare($conexion, 'INSERT INTO trabajos (descripcion, diagnostico, prioridad, observaciones, estado, fecha_inicio, costo, id_usuario, id_tecnico, id_solicitud) VALUES (?, ?, ?, ?, ?, CURDATE(), ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'sssssdiii', $descripcion, $diagnostico, $prioridad, $observaciones, $estado, $costo, $clienteId, $tecnicoId, $solicitudId);
        $ok = $ok && mysqli_stmt_execute($stmt);
        $trabajoId = (int) mysqli_insert_id($conexion);
        mysqli_stmt_close($stmt);

        if ($ok && $servicioId) {
            $stmt = db_prepare($conexion, 'SELECT precio FROM servicios WHERE id_servicio = ? AND estado = "Activo"');
            mysqli_stmt_bind_param($stmt, 'i', $servicioId);
            mysqli_stmt_execute($stmt);
            $servicio = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            if ($servicio) {
                $cantidad = 1;
                $precio = (float) $servicio['precio'];
                $stmt = db_prepare($conexion, 'INSERT INTO trabajo_servicio (id_trabajo, id_servicio, cantidad, precio_unitario) VALUES (?, ?, ?, ?)');
                mysqli_stmt_bind_param($stmt, 'iiid', $trabajoId, $servicioId, $cantidad, $precio);
                $ok = $ok && mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }

        if ($ok) {
            $tipoMantenimiento = 'Mantenimiento de ' . $equipo;
            $descripcionMantenimiento = $diagnostico !== '' ? $diagnostico : $problema;
            $fechaProgramada = date('Y-m-d');
            $estadoMantenimiento = $estado === 'Completado' ? 'Completado' : 'Pendiente';
            $stmt = db_prepare($conexion, 'INSERT INTO mantenimiento (id_trabajo, tipo, descripcion, estado, fecha_programada, id_usuario) VALUES (?, ?, ?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'issssi', $trabajoId, $tipoMantenimiento, $descripcionMantenimiento, $estadoMantenimiento, $fechaProgramada, $tecnicoId);
            $ok = $ok && mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        if ($ok) {
            $actividad = "Trabajo #$trabajoId registrado para $equipo";
            $tipo = 'trabajo';
            $stmt = db_prepare($conexion, 'INSERT INTO actividad_reciente (tipo, descripcion, referencia_id, id_usuario) VALUES (?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'ssii', $tipo, $actividad, $trabajoId, $usuarioActual);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            mysqli_commit($conexion);
            header('Location: trabajos.php?ok=1');
            exit;
        }

        mysqli_rollback($conexion);
        $error = 'No se pudo registrar el trabajo.';
        }
    } catch (Throwable $ex) {
        if (mysqli_errno($conexion)) {
            mysqli_rollback($conexion);
        }
        $error = $ex->getMessage();
    }
}

$clientes = mysqli_query($conexion, 'SELECT id_usuario, nombre, apellido, email FROM usuarios ORDER BY nombre, apellido');
$tecnicos = mysqli_query($conexion, 'SELECT id_tecnico, nombre FROM tecnicos ORDER BY nombre');
$servicios = mysqli_query($conexion, "SELECT id_servicio, nombre, precio FROM servicios WHERE estado = 'Activo' ORDER BY nombre");
$trabajos = mysqli_query($conexion, 'SELECT t.*, s.tipo_equipo, s.problema, u.nombre cliente_nombre, u.apellido cliente_apellido, te.nombre tecnico, GROUP_CONCAT(se.nombre SEPARATOR ", ") servicios
    FROM trabajos t
    LEFT JOIN solicitudes s ON s.id_solicitud = t.id_solicitud
    LEFT JOIN usuarios u ON u.id_usuario = t.id_usuario
    LEFT JOIN tecnicos te ON te.id_tecnico = t.id_tecnico
    LEFT JOIN trabajo_servicio ts ON ts.id_trabajo = t.id_trabajo
    LEFT JOIN servicios se ON se.id_servicio = ts.id_servicio
    GROUP BY t.id_trabajo
    ORDER BY t.fecha_inicio DESC, t.id_trabajo DESC');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Trabajos | Digital Care</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<main class="dashboard-page">
  <?php digital_care_header($conexion); ?>
  <section class="dashboard-content">
    <article class="module-view">
      <div class="module-head">
        <div>
          <h2>Trabajos técnicos</h2>
          <p>Ingreso, diagnóstico y seguimiento real de reparaciones.</p>
        </div>
        <a class="primary" href="clientes.php">Clientes</a>
      </div>

      <?php if (isset($_GET['ok'])): ?><p class="ok">Trabajo registrado y mantenimiento creado automáticamente.</p><?php endif; ?>
      <?php if (isset($_GET['estado_ok'])): ?><p class="ok">Estado del trabajo actualizado correctamente.</p><?php endif; ?>
      <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>

      <form method="post" class="work-form">
        <?= csrf_input() ?>
        <input type="hidden" name="accion" value="crear">
        <label>Cliente
          <select name="id_cliente" required>
            <option value="">Seleccionar cliente</option>
            <?php while ($cliente = $clientes ? mysqli_fetch_assoc($clientes) : null): ?>
              <option value="<?= (int) $cliente['id_usuario'] ?>"><?= e(trim($cliente['nombre'] . ' ' . ($cliente['apellido'] ?? '')) . ' - ' . $cliente['email']) ?></option>
            <?php endwhile; ?>
          </select>
        </label>
        <label>Equipo<input required name="tipo_equipo" placeholder="Notebook, PC, impresora..."></label>
        <label>Prioridad
          <select name="prioridad" required><option>Media</option><option>Baja</option><option>Alta</option><option>Urgente</option></select>
        </label>
        <label>Estado
          <select name="estado" required>
            <?php foreach ($estadosTrabajo as $estadoOpcion): ?>
              <option><?= e($estadoOpcion) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Técnico
          <select name="id_tecnico"><option value="">Sin asignar</option><?php while ($tecnico = $tecnicos ? mysqli_fetch_assoc($tecnicos) : null): ?><option value="<?= (int) $tecnico['id_tecnico'] ?>"><?= e($tecnico['nombre']) ?></option><?php endwhile; ?></select>
        </label>
        <label>Servicio
          <select name="id_servicio"><option value="">Sin servicio asociado</option><?php while ($servicio = $servicios ? mysqli_fetch_assoc($servicios) : null): ?><option value="<?= (int) $servicio['id_servicio'] ?>"><?= e($servicio['nombre']) ?> - $<?= number_format((float) $servicio['precio'], 2, ',', '.') ?></option><?php endwhile; ?></select>
        </label>
        <label>Costo<input required name="costo" type="number" min="0" step="0.01" value="0"></label>
        <label>Problema<textarea required name="problema"></textarea></label>
        <label>Descripción del trabajo<textarea required name="descripcion"></textarea></label>
        <label>Diagnóstico<textarea name="diagnostico"></textarea></label>
        <label>Observaciones<textarea name="observaciones"></textarea></label>
        <button class="primary">Registrar trabajo</button>
      </form>

      <div class="maint-list">
        <?php while ($trabajo = $trabajos ? mysqli_fetch_assoc($trabajos) : null): ?>
          <article class="maint-card">
            <div class="row-between">
              <h3>#<?= (int) $trabajo['id_trabajo'] ?> · <?= e($trabajo['tipo_equipo'] ?? 'Equipo') ?></h3>
              <strong class="job-status <?= e(estado_trabajo_clase((string) $trabajo['estado'])) ?>"><?= e($trabajo['estado']) ?></strong>
            </div>
            <p><?= e($trabajo['descripcion']) ?></p>
            <small>Cliente: <?= e(trim(($trabajo['cliente_nombre'] ?? '') . ' ' . ($trabajo['cliente_apellido'] ?? ''))) ?> · Técnico: <?= e($trabajo['tecnico'] ?? 'Sin asignar') ?> · Prioridad: <?= e($trabajo['prioridad']) ?></small>
            <small>Servicios: <?= e($trabajo['servicios'] ?? 'Sin servicio') ?></small>
            <form method="post" class="status-form">
              <?= csrf_input() ?>
              <input type="hidden" name="accion" value="actualizar_estado">
              <input type="hidden" name="id_trabajo" value="<?= (int) $trabajo['id_trabajo'] ?>">
              <label>Modificar estado
                <select name="estado" required>
                  <?php foreach ($estadosTrabajo as $estadoOpcion): ?>
                    <option value="<?= e($estadoOpcion) ?>" <?= $trabajo['estado'] === $estadoOpcion ? 'selected' : '' ?>><?= e($estadoOpcion) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <button class="secondary" type="submit">Modificar estado</button>
            </form>
            <strong class="amount">$<?= number_format((float) $trabajo['costo'], 2, ',', '.') ?></strong>
          </article>
        <?php endwhile; ?>
      </div>
    </article>
  </section>
</main>
<style>
  .module-head,.row-between{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start}.module-head h2{margin:0;color:#1e4e95}.work-form{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;background:#f0f8ff;padding:16px;border-radius:14px;margin:16px 0}.work-form label{font-weight:700;color:#1f3762}.work-form textarea{min-height:76px;resize:vertical}.work-form input,.work-form select,.work-form textarea{width:100%;box-sizing:border-box;margin-top:5px;padding:10px;border:1px solid #b8d4df;border-radius:8px;font:inherit}.primary{display:inline-block;border:0;border-radius:999px;background:#2397dd;color:#fff;padding:10px 16px;font-weight:700;text-decoration:none;cursor:pointer}.maint-card{padding:16px;margin-bottom:12px}.amount{float:right;color:#17b67f}.ok{color:#17b67f;font-weight:700}.error{color:#d94b4b;font-weight:700}
  .status-form{display:grid;grid-template-columns:minmax(180px,1fr) auto;gap:10px;align-items:end;border-top:1px solid #d7e3e8;padding-top:12px}.status-form label{font-weight:700;color:#1f3762}.status-form select{width:100%;box-sizing:border-box;margin-top:5px;padding:10px;border:1px solid #b8d4df;border-radius:8px;font:inherit}.secondary{border:0;border-radius:999px;background:#17b67f;color:#fff;padding:10px 14px;font-weight:700;cursor:pointer}.job-status{display:inline-block;white-space:nowrap}@media(max-width:620px){.status-form{grid-template-columns:1fr}.secondary{width:100%}}
</style>
</body>
</html>
