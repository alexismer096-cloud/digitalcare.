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

echo "<h2>Recuperando Inventario de Repuestos...</h2>";

// 1. Limpiar tabla actual para evitar duplicados si se desea empezar de cero
if (!mysqli_query($conexion, "TRUNCATE TABLE repuestos")) {
    echo "<p style='color:red;'>No se pudo vaciar la tabla repuestos: " . e(mysqli_error($conexion)) . "</p>";
    exit;
}

echo "Insertando lista extensa de repuestos reales...<br>";

$repuestos = [
    ['Disco HDD 1TB', 'Disco de escritorio Western Digital Blue', 12, 55.00],
    ['Disco SSD 480GB', 'Kingston A400 SATA3', 8, 70.00],
    ['RAM DDR3 8GB', 'Memoria Kingston para PC antigua', 14, 25.00],
    ['RAM DDR4 8GB', 'Crucial 3200MHz Laptop', 18, 32.00],
    ['Pantalla LCD 15.6', '40 pines CCFL', 6, 45.00],
    ['Pantalla LED 15.6', '30 pines Slim Full HD', 4, 60.00],
    ['Teclado HP 15-DA', 'Español con marco', 11, 20.00],
    ['Batería Dell Inspiron', 'Model M5Y1K original', 9, 30.00],
    ['Ventilador CPU Intel', 'Socket 1151/1200', 13, 12.00],
    ['Fuente 500W EVGA', 'Certificación 80 Plus White', 7, 35.00],
    ['Cargador Universal', 'Puntas intercambiables 90W', 16, 15.00],
    ['Tarjeta WiFi USB', 'TP-Link 150Mbps', 10, 10.00],
    ['Pasta térmica MX-4', 'Jeringa 4g Artic', 28, 6.00],
    ['Bisagras Lenovo Ideapad', 'Par izquierda/derecha', 5, 18.00],
    ['Touchpad HP 15', 'Color plata original', 4, 24.00],
    ['Puerto Carga Samsung A52', 'Módulo de carga original', 7, 14.00],
    ['Cámara iPhone 11', 'Módulo frontal FaceID', 6, 36.00],
    ['Batería iPhone 12', 'Original 2815mAh', 3, 42.00],
    ['Switch Mecánico Blue', 'Pack x10 Outemu', 34, 5.00],
    ['Cable Flex Pantalla', 'LVDS 30 pines universal', 9, 8.00],
    ['Conector SATA', 'Cable datos y power', 22, 4.00],
    ['Placa de Audio USB', 'Adaptador 7.1', 12, 11.00],
    ['Módulo Bluetooth 5.0', 'Mini PCIe para Laptop', 8, 13.00],
    ['Pantalla iPhone 11', 'Display Incell Premium', 2, 58.00],
    ['Set Tornillos Laptop', 'Kit x100 unidades varios tamaños', 40, 5.00],
    ['Disco SSD NVMe 500GB', 'Samsung 980 Pro', 10, 85.00],
    ['RAM DDR4 16GB', 'Corsair Vengeance RGB', 5, 65.00],
    ['Mouse Pad Gamer', 'XL 90x40cm Negro', 25, 15.00],
    ['Webcam Full HD 1080p', 'Con micrófono integrado', 12, 28.00],
    ['Cable HDMI 2.0', '3 metros reforzado', 30, 8.00]
];

$stmt = mysqli_prepare($conexion, "INSERT INTO repuestos (nombre, descripcion, stock, precio) VALUES (?, ?, ?, ?)");

if (!$stmt) {
    echo "<p style='color:red;'>No se pudo preparar la restauración de stock.</p>";
    exit;
}

foreach ($repuestos as $r) {
    $nombre = $r[0];
    $desc = $r[1];
    $stock = (int) $r[2];
    $precio = (float) $r[3];
    mysqli_stmt_bind_param($stmt, 'ssid', $nombre, $desc, $stock, $precio);
    mysqli_stmt_execute($stmt);
}

mysqli_stmt_close($stmt);

echo "<br><b>¡Éxito! Se han restaurado " . count($repuestos) . " repuestos en la base de datos.</b>";
echo "<br><a href='module.php?module=repuestos'>Ver Repuestos en el Módulo</a>";
?>
