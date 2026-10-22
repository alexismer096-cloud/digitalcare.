<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit;
}

echo "<h2>Poblando base de datos con datos de prueba reales...</h2>";

// 1. Insertar Repuestos si no hay
$checkRep = mysqli_query($conexion, "SELECT COUNT(*) as total FROM repuestos");
$rowRep = mysqli_fetch_assoc($checkRep);
if ($rowRep['total'] == 0) {
    echo "Insertando repuestos...<br>";
    $sql = "INSERT INTO repuestos (nombre, descripcion, stock, precio) VALUES 
    ('Disco SSD 480GB', 'Disco de estado sólido Kingston', 15, 75.50),
    ('Memoria RAM 8GB DDR4', 'Módulo Crucial 3200MHz', 2, 35.00),
    ('Pantalla LED 15.6', 'Pantalla para laptop 30 pines', 4, 120.00),
    ('Batería HP 15-DA', 'Batería original HP', 3, 45.00),
    ('Cargador Universal', 'Cargador multimarca 90W', 20, 25.00)";
    mysqli_query($conexion, $sql);
}

// 2. Insertar Solicitudes (Trabajos) si no hay
$checkSol = mysqli_query($conexion, "SELECT COUNT(*) as total FROM solicitudes");
$rowSol = mysqli_fetch_assoc($checkSol);
if ($rowSol['total'] == 0) {
    echo "Insertando trabajos...<br>";
    // Obtener un id_usuario válido para la clave foránea
    $userRes = mysqli_query($conexion, "SELECT id_usuario FROM usuarios LIMIT 1");
    $userRow = mysqli_fetch_assoc($userRes);
    $uid = $userRow ? $userRow['id_usuario'] : 1;

    $sql = "INSERT INTO solicitudes (tipo_equipo, problema, estado, id_usuario) VALUES 
    ('Laptop Dell Inspiron', 'No enciende, posible falla de placa', 'Pendiente', $uid),
    ('MacBook Air', 'Pantalla rota y teclado pegajoso', 'En proceso', $uid),
    ('PC de Escritorio', 'Limpieza general y cambio de pasta', 'Completado', $uid),
    ('iPhone 12', 'Cambio de batería', 'Pendiente', $uid),
    ('Impresora Epson', 'No jala papel', 'Completado', $uid)";
    mysqli_query($conexion, $sql);
}

// 3. Insertar Mantenimientos si no hay
$checkMant = mysqli_query($conexion, "SELECT COUNT(*) as total FROM mantenimiento");
$rowMant = mysqli_fetch_assoc($checkMant);
if ($rowMant['total'] == 0) {
    echo "Insertando mantenimientos...<br>";
    $userRes = mysqli_query($conexion, "SELECT id_usuario FROM usuarios LIMIT 1");
    $userRow = mysqli_fetch_assoc($userRes);
    $uid = $userRow ? $userRow['id_usuario'] : 1;

    $sql = "INSERT INTO mantenimiento (tipo, descripcion, estado, fecha_programada, id_usuario) VALUES 
    ('Preventivo', 'Limpieza de servidores locales', 'Pendiente', '2026-05-10', $uid),
    ('Correctivo', 'Cambio de fuente de poder taller', 'Completado', '2026-04-20', $uid),
    ('Software', 'Actualización de antivirus en recepción', 'Pendiente', '2026-05-05', $uid)";
    mysqli_query($conexion, $sql);
}

echo "<br><b>¡Listo! Los datos han sido cargados.</b>";
echo "<br><a href='index.php?view=dashboard'>Volver al Dashboard</a>";
?>
