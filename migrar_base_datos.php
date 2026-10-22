<?php

require_once __DIR__ . '/database_schema.php';

ensure_core_schema($conexion);
ensure_users_table($conexion);
ensure_product_columns($conexion);
hydrate_product_metadata($conexion);
ensure_purchase_tables($conexion);
ensure_business_tables($conexion);

$pendingRelationships = ensure_relationships($conexion);
if ($pendingRelationships) {
    echo 'Estructura actualizada. Relaciones pendientes por registros huerfanos: ' . e(implode(', ', $pendingRelationships)) . '.';
    exit;
}

echo 'Estructura y relaciones de base de datos actualizadas correctamente.';
