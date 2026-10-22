<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('display_startup_errors', 0);

session_start();
require_once 'conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit;
}

echo "<html><body style='font-family:Arial,sans-serif;background:#f0f8ff;padding:40px;'>";
echo "<div style='max-width:700px;margin:0 auto;background:white;padding:40px;border-radius:15px;box-shadow:0 0 20px rgba(0,0,0,0.1);'>";

echo "<h1 style='color:#1e4e95;'>🚀 CARGANDO 100+ REPUESTOS</h1>";

// 1. Verificar conexión
if (!$conexion) {
    echo "<p style='color:red;'>❌ ERROR: No hay conexión a la base de datos.</p>";
    exit;
}
echo "<p style='color:green;'>✅ Conexión a la BD exitosa.</p>";

// 2. Vaciar la tabla (usamos DELETE en lugar de TRUNCATE por la clave foránea)
echo "<p style='color:orange;'>🔄 Vaciando la tabla anterior...</p>";
if (!mysqli_query($conexion, "DELETE FROM repuestos")) {
    echo "<p style='color:red;'>❌ No se pudo vaciar repuestos: " . e(mysqli_error($conexion)) . "</p>";
    exit;
}
mysqli_query($conexion, "ALTER TABLE repuestos AUTO_INCREMENT = 1");

// 3. Lista MÁS DE 100 REPUESTOS
$repuestos = [
    // Componentes principales (10)
    ["Placa madre", "Placa base para PC de escritorio", 10, 85],
    ["Procesador (CPU)", "Unidad central de procesamiento", 8, 120],
    ["Memoria RAM DDR3", "Módulo DDR3 para equipos antiguos", 20, 28],
    ["Memoria RAM DDR4", "Módulo DDR4 3200MHz", 25, 35],
    ["Memoria RAM DDR5", "Módulo DDR5 5600MHz", 12, 65],
    ["Disco duro HDD", "Disco mecánico 1TB", 15, 50],
    ["Disco sólido SSD", "SSD SATA3 480GB", 18, 65],
    ["SSD NVMe", "SSD PCIe 4.0 500GB", 10, 85],
    ["Fuente de alimentación", "Fuente 500W 80 Plus", 12, 40],
    ["Gabinete", "Caja para PC ATX", 10, 55],
    
    // Tarjetas y expansión (10)
    ["Tarjeta gráfica (GPU)", "Tarjeta de video para gaming", 6, 250],
    ["Tarjeta de sonido", "Tarjeta de audio PCIe", 15, 35],
    ["Tarjeta de red", "Tarjeta Ethernet Gigabit", 20, 20],
    ["Tarjeta WiFi", "Tarjeta WiFi 6 PCIe", 14, 30],
    ["Tarjeta Bluetooth", "Módulo Bluetooth 5.0", 18, 15],
    ["Tarjeta PCI", "Tarjeta de expansión PCI", 8, 25],
    ["Tarjeta PCIe", "Tarjeta de expansión PCIe x1", 10, 35],
    ["Controladora SATA", "Tarjeta controladora SATA 3.0", 12, 28],
    ["Controladora RAID", "Controladora RAID 0/1/5", 6, 85],
    ["Capturadora de video", "Capturadora HDMI 1080p", 8, 120],
    
    // Refrigeración (10)
    ["Cooler de CPU", "Ventilador para procesador", 25, 25],
    ["Ventilador gabinete", "Ventilador 120mm", 35, 12],
    ["Disipador de calor", "Disipador pasivo para chipset", 18, 10],
    ["Pasta térmica", "Pasta térmica MX-4 4g", 50, 6],
    ["Refrigeración líquida", "Kit AIO de refrigeración", 8, 120],
    ["Fan RGB", "Ventilador 120mm RGB", 20, 18],
    ["Controlador de ventiladores", "Hub para control de fans", 10, 25],
    ["Sensor térmico", "Sensor de temperatura interno", 15, 8],
    ["Soporte ventilador", "Soporte para montaje", 25, 5],
    ["Filtro antipolvo", "Filtro magnético para gabinete", 30, 8],
    
    // Cables y conectores (10)
    ["Cable SATA", "Cable de datos SATA 3.0", 40, 4],
    ["Cable IDE", "Cable IDE para discos antiguos", 15, 6],
    ["Cable de alimentación", "Cable de poder para PC", 25, 8],
    ["Cable USB", "Cable USB-A a USB-C 2m", 50, 10],
    ["Cable HDMI", "Cable HDMI 2.0 3m", 40, 12],
    ["Cable VGA", "Cable VGA para monitor", 20, 8],
    ["Cable DisplayPort", "Cable DisplayPort 1.4", 15, 15],
    ["Cable Ethernet", "Cable Cat6 5m", 30, 10],
    ["Adaptador USB", "Adaptador USB-C a USB-A", 35, 8],
    ["Adaptador HDMI", "Adaptador VGA a HDMI", 20, 10],
    
    // Periféricos (10)
    ["Teclado", "Teclado mecánico RGB", 20, 45],
    ["Mouse", "Mouse óptico inalámbrico", 25, 25],
    ["Monitor", "Monitor LED 24\" Full HD", 12, 180],
    ["Webcam", "Webcam Full HD 1080p", 18, 30],
    ["Micrófono", "Micrófono de escritorio", 15, 40],
    ["Parlantes", "Parlantes estéreo 2.0", 20, 35],
    ["Auriculares", "Auriculares con micrófono", 22, 50],
    ["Impresora", "Impresora multifunción", 8, 150],
    ["Escáner", "Escáner de documentos", 10, 80],
    ["Tableta gráfica", "Tableta para dibujo digital", 6, 120],
    
    // Energía (10)
    ["UPS", "UPS de 1000VA", 10, 180],
    ["Batería CMOS", "Batería CR2032 para placa madre", 50, 3],
    ["Fuente notebook", "Fuente de alimentación para laptop", 15, 60],
    ["Cargador laptop", "Cargador universal para notebook", 20, 35],
    ["Regulador de voltaje", "Regulador 500VA", 12, 45],
    ["Power bank laptop", "Batería externa para notebook", 10, 80],
    ["Cable power", "Cable de poder para notebook", 30, 12],
    ["Transformador", "Transformador 110v a 220v", 8, 30],
    ["Adaptador corriente", "Adaptador de corriente universal", 25, 18],
    ["Switch de energía", "Switch de corriente para PC", 15, 25],
    
    // Internos pequeños (10)
    ["Chip BIOS", "Chip BIOS para placa madre", 10, 20],
    ["Cristal oscilador", "Cristal de reloj para placa", 18, 5],
    ["Capacitor", "Capacitor electrolítico 1000µF", 100, 2],
    ["Resistencia", "Resistencia de carbono 1/4W", 200, 1],
    ["Transistor", "Transistor NPN BC547", 80, 1.5],
    ["Diodo", "Diodo rectificador 1N4007", 120, 1],
    ["Fusible", "Fusible de vidrio 5A", 100, 2],
    ["Regulador voltaje", "Regulador LM7805", 50, 4],
    ["MOSFET", "MOSFET de potencia IRF540", 30, 8],
    ["IC integrado", "Circuito integrado 555 Timer", 40, 5],
    
    // 30 REPUESTOS ADICIONALES para llegar a 100+
    ["Memoria RAM DDR4 16GB", "Módulo DDR4 3200MHz 16GB", 15, 55],
    ["Disco duro HDD 2TB", "Disco mecánico 2TB", 10, 75],
    ["SSD SATA3 1TB", "SSD SATA3 1TB", 8, 110],
    ["SSD NVMe 1TB", "SSD PCIe 4.0 1TB", 6, 150],
    ["Fuente 650W", "Fuente 650W 80 Plus Bronze", 8, 55],
    ["Gabinete RGB", "Caja para PC ATX RGB", 6, 85],
    ["Tarjeta gráfica GTX", "Tarjeta de video mid-range", 4, 350],
    ["Tarjeta WiFi USB", "Adaptador WiFi USB", 25, 15],
    ["Tarjeta Capture USB", "Capturadora USB HDMI", 10, 80],
    ["Cooler CPU RGB", "Ventilador CPU RGB", 12, 35],
    ["Ventilador 140mm", "Ventilador 140mm silencioso", 15, 15],
    ["Kit refrigeración líquida 240mm", "Kit AIO 240mm", 5, 150],
    ["Cable SATA 90°", "Cable SATA ángulo recto", 20, 5],
    ["Cable USB-C a USB-C", "Cable USB-C 2m", 30, 12],
    ["Cable USB 3.0", "Cable USB 3.0 1.5m", 25, 8],
    ["Cable DisplayPort 2.0", "Cable DP 2.0 4K", 12, 20],
    ["Cable Ethernet Cat6a", "Cable Cat6a 3m", 18, 12],
    ["Adaptador USB-C a HDMI", "Adaptador USB-C a HDMI 4K", 15, 18],
    ["Adaptador USB-C a VGA", "Adaptador USB-C a VGA", 10, 15],
    ["Teclado membrana", "Teclado de membrana económico", 18, 20],
    ["Mouse gamer", "Mouse gamer con luces", 15, 35],
    ["Monitor 27\"", "Monitor 27\" QHD", 5, 280],
    ["Webcam 4K", "Webcam 4K Ultra HD", 8, 60],
    ["Micrófono USB", "Micrófono USB para streaming", 10, 55],
    ["Parlantes Bluetooth", "Parlantes estéreo Bluetooth", 12, 45],
    ["Auriculares Bluetooth", "Auriculares inalámbricos", 10, 65],
    ["Impresora láser", "Impresora láser monocromática", 6, 200],
    ["Escáner portátil", "Escáner portátil de documentos", 8, 100],
    ["Tableta gráfica pequeña", "Tableta gráfica compacta", 10, 50],
    ["UPS 1500VA", "UPS de 1500VA con display", 5, 250],
    ["Batería externa 20000mAh", "Power bank 20000mAh", 15, 35],
    ["Cargador rápido 65W", "Cargador USB-C PD 65W", 20, 30]
];

echo "<p style='font-size:1.2rem;'>📦 Cargando " . count($repuestos) . " repuestos...</p>";
echo "<div style='background:#f8f9fa;padding:20px;border-radius:10px;margin:20px 0;max-height:300px;overflow-y:auto;'>";

$exitosos = 0;
$stmt = mysqli_prepare($conexion, "INSERT INTO repuestos (nombre, descripcion, stock, precio) VALUES (?, ?, ?, ?)");

if (!$stmt) {
    echo "<p style='color:red;'>❌ No se pudo preparar la carga de repuestos.</p>";
    exit;
}

foreach ($repuestos as $r) {
    $nombre = $r[0];
    $desc = $r[1];
    // Asegurar que el stock siempre sea >= 20
    $stock_final = (int) max($r[2], 20);
    $precio = (float) $r[3];
    mysqli_stmt_bind_param($stmt, 'ssid', $nombre, $desc, $stock_final, $precio);
    
    if (mysqli_stmt_execute($stmt)) {
        $exitosos++;
        // Mostrar solo cada 10 repuestos para no saturar la pantalla
        if ($exitosos % 10 == 0) {
            echo "<p style='color:green;'>✅ " . e($nombre) . " ($stock_final u)</p>";
        }
    } else {
        echo "<p style='color:red;'>❌ Error en " . e($nombre) . "</p>";
    }
}

mysqli_stmt_close($stmt);

echo "</div>";
echo "<p style='font-size:1.5rem;color:green;font-weight:bold;'>🎉 ¡LISTO! Se cargaron $exitosos repuestos.</p>";

echo "<a href='module.php?module=repuestos' style='display:inline-block;background:linear-gradient(90deg,#2397dd,#2a65de);color:white;padding:20px 40px;border-radius:999px;text-decoration:none;font-weight:bold;font-size:1.3rem;'>🟢 VER LOS 100+ REPUESTOS AHORA</a>";

echo "</div></body></html>";
?>
