<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
require_once 'conexion.php';

echo "<h1>Creando Tabla de Carrito...</h1>";

$sql = "CREATE TABLE IF NOT EXISTS carrito (
    id_carrito INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_repuesto INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_repuesto) REFERENCES repuestos(id_repuesto) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($conexion, $sql)) {
    echo "<p style='color: green; font-weight: bold;'>✅ Tabla de carrito creada o ya existe correctamente!</p>";
} else {
    echo "<p style='color: red;'>❌ Error al crear la tabla: " . mysqli_error($conexion) . "</p>";
}

echo "<br><a href='index.php?view=dashboard' style='padding: 12px 24px; background: #2397dd; color: white; text-decoration: none; border-radius: 10px;'>🟢 Volver al Dashboard</a>";
?>
