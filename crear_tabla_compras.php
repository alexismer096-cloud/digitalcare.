<?php
require_once 'database_schema.php';

if (ensure_purchase_tables($conexion)) {
    echo "✅ Tablas 'compras' y 'detalles_compras' creadas o ya existentes.\n";
} else {
    echo "❌ Error al crear tablas de compras: " . e(mysqli_error($conexion)) . "\n";
}

echo "<br><a href='index.php?view=dashboard'>Volver al Dashboard</a>";
?>
