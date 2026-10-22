# Informe previo de limpieza - Digital Care

Fecha: 2026-06-24

## Alcance revisado

Se revisaron archivos PHP, HTML, JS, CSS, SQL y Markdown en la raiz del proyecto y en la copia anidada `tesis_pechuga/tesis_pechuga`. Tambien se verificaron hashes SHA-256 para detectar duplicados exactos, busquedas de referencias internas y existencia de imagenes locales.

## Hallazgos y decision

| Archivo o grupo encontrado | Motivo | Archivos que dependen de el | Riesgo de eliminacion | Decision |
|---|---|---|---|---|
| `tesis_pechuga/tesis_pechuga/` | Copia anidada del proyecto completo. Contiene duplicados exactos y algunas versiones mas completas que deben promoverse antes de borrar. | No hay referencias activas desde la raiz hacia esta ruta. | Bajo despues de promover versiones completas. | Promover archivos utiles y eliminar carpeta anidada. |
| Archivos raiz con sufijo ` (1)` o `(1)` | Copias antiguas o duplicadas: `index`, `module`, `script`, `styles`, login, logout, registro, recuperar, SQL y documentacion. | No se encontraron referencias activas hacia estos nombres. | Bajo. | Eliminar copias y conservar nombres canonicos sin sufijo. |
| `(1).vscode/` | Copia duplicada de configuracion VS Code. | Ninguna dependencia de aplicacion. | Bajo. | Eliminar copia duplicada. |
| `conexion.php` | La version activa era incompleta: no definia `e()`, `json_response()` ni `redirect_with_message()`, aunque varios PHP activos las usan. | `index.php`, `module.php`, `validar_login.php`, `procesar_registro.php`, `procesar_recuperar.php`, APIs y carrito. | Alto si se elimina; bajo si se reemplaza por version completa. | Reemplazar por version completa, no eliminar. |
| `api_carrito.php`, `api_stock.php`, `api_repuesto_detalle.php`, `carrito.php`, `cargar_datos.php`, `cargar_repuestos.php`, `crear_tabla_carrito.php` | Las versiones anidadas son mas completas o seguras: consultas preparadas, validacion, respuestas JSON centralizadas, escapado HTML y rutas corregidas. | `module.php`, `carrito.php`, operaciones de stock y carga de datos. | Medio si se conserva la version antigua; bajo al promover la completa. | Reemplazar versiones raiz por versiones completas. |
| `api_login.php` | Endpoint JSON no usado por el login actual, pero puede servir a clientes externos. La version anidada usa consulta preparada y helper JSON. | Sin referencias activas actuales; posible uso externo. | Bajo-medio. | Mantener endpoint canonico y reemplazar por version limpia. |
| `module.js` | Contenia muchas funciones de render antiguo que ya no se ejecutan porque `module.php` renderiza en servidor. | Cargado por `module.php`. | Bajo si se conserva comportamiento auxiliar minimo. | Reemplazar por JS minimo para acciones actuales. |
| `script.js` | Contenia datos de modulo usados solo como lista de validacion y textos no renderizados. | Cargado por `index.php`. | Medio: maneja dashboard y navegacion. | Limpiar datos redundantes sin romper navegacion. |
| `module.html` | Pantalla estatica antigua del modulo. La ruta activa y funcional es `module.php`, que valida sesion y renderiza contenido real. | Sin referencias activas. | Bajo. | Eliminar. |
| Imagenes locales | No se encontraron `.png`, `.jpg`, `.jpeg`, `.gif`, `.webp`, `.svg` ni `.ico`. | No aplica. | Ninguno. | Sin cambios. |
| Librerias externas | Solo Google Fonts en HTML/PHP. No hay `package.json`, `vendor`, imports JS ni gestor de dependencias. | CSS visual. | Bajo. | Sin eliminaciones. |
| CSS duplicado | `styles.css`, `styles(1).css`, `styles (1).css` y copias anidadas son duplicados exactos. | `index.php`, `module.php`, `carrito.php`. | Bajo si se conserva `styles.css`. | Conservar solo `styles.css`. |

## Rutas canonicas a conservar

- `index.php` y `index.html`
- `validar_login.php`
- `registro.php` y `procesar_registro.php`
- `recuperar.php` y `procesar_recuperar.php`
- `module.php?module=trabajos`
- `module.php?module=clientes`
- `module.php?module=repuestos`
- `module.php?module=mantenimiento`
- `module.php?module=servicios`
- `module.php?module=reportes`
- `module.php?module=perfil`
- `carrito.php`
- APIs activas: `api_carrito.php`, `api_stock.php`, `api_repuesto_detalle.php`, `api_login.php`
- Conexion y base de datos: `conexion.php`, `digital_care.sql`, `tablas_compras.sql`

## Riesgo general

Riesgo bajo-medio. La eliminacion de duplicados es de bajo riesgo porque se conservan los nombres canonicos. El mayor riesgo detectado estaba en conservar versiones incompletas de archivos activos, por eso se promueven primero las versiones completas.
