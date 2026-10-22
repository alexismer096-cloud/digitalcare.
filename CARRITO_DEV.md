## 🛠️ GUÍA DE DESARROLLO - CARRITO DE COMPRAS

### 📂 ARQUITECTURA DEL SISTEMA

```
Carpeta raíz (tesis pechuga)
│
├── 🔴 FRONTEND (Cliente)
│   ├── module.php ...................... Módulo principal dinámico
│   ├── module.js ....................... Lógica del cliente
│   ├── carrito.php ..................... Página del carrito
│   ├── styles.css ...................... Estilos CSS
│   └── index.php ....................... Dashboard
│
├── 🟢 BACKEND (Servidor)
│   ├── api_carrito.php ................. API REST del carrito
│   ├── api_repuesto_detalle.php ....... Detalle de repuestos
│   ├── api_stock.php ................... Gestión de stock
│   ├── conexion.php .................... Conexión BD
│   └── logout.php ...................... Cerrar sesión
│
├── 🟠 BD (Datos)
│   ├── crear_tabla_compras.php ........ Crear tablas (PHP)
│   ├── tablas_compras.sql ............. Crear tablas (SQL)
│   └── digital_care.sql ............... Backup BD
│
└── 📖 DOCUMENTACIÓN
    ├── CARRITO_README.md .............. Guía de usuario
    ├── CARRITO_VISUAL.md .............. Mockups y flujos
    └── CARRITO_DEV.md ................. Este archivo

```

---

### 🔄 FLUJO DE DATOS

```
┌─────────────┐
│   Cliente   │ (Browser)
└──────┬──────┘
       │ Hace click
       ▼
┌──────────────────────────────────┐
│  module.php (Módulo Repuestos)   │ Renderiza tarjetas
└──────┬──────────────────────────┘
       │ agregarAlCarrito(id)
       ▼
┌──────────────────────────────────┐
│   api_carrito.php?action=agregar │ Procesa request
│   &id=3                          │
└──────┬──────────────────────────┘
       │ Responde JSON
       ▼
┌──────────────────────────────────┐
│  module.js (actualizarCarrito)   │ Actualiza UI
└──────┬──────────────────────────┘
       │ Muestra alerta
       ▼
┌──────────────────────────────────┐
│  $_SESSION['carrito'] actualizado│ Items guardados en sesión
└──────────────────────────────────┘
```

---

### 📡 API REST ENDPOINTS

#### **1. Agregar al Carrito**
```
GET /api_carrito.php?action=agregar&id=3

Response:
{
  "success": true,
  "mensaje": "Producto agregado al carrito"
}
```

#### **2. Cambiar Cantidad**
```
POST /api_carrito.php
Body: action=cambiar_cantidad&key=0&cambio=1

Response:
{
  "success": true
}
```

#### **3. Eliminar Item**
```
POST /api_carrito.php
Body: action=eliminar&key=0

Response:
{
  "success": true
}
```

#### **4. Limpiar Carrito**
```
POST /api_carrito.php
Body: action=limpiar

Response:
{
  "success": true
}
```

#### **5. Contar Items**
```
GET /api_carrito.php?action=contar

Response:
{
  "success": true,
  "cantidad": 3
}
```

#### **6. Finalizar Compra**
```
POST /api_carrito.php
Body: action=finalizar_compra

Response:
{
  "success": true,
  "id_compra": 1,
  "total": 165.00
}
```

---

### 🗄️ ESQUEMA BD

#### **Tabla: compras**
```sql
CREATE TABLE compras (
    id_compra INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    total DECIMAL(10, 2),
    estado VARCHAR(50) DEFAULT 'Pendiente',
    fecha_compra TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);
```

**Campos:**
- `id_compra`: Identificador único
- `id_usuario`: Quién realiza la compra
- `total`: Monto total
- `estado`: 'Completado', 'Pendiente', 'Cancelado'
- `fecha_compra`: Timestamp automático

---

#### **Tabla: detalles_compras**
```sql
CREATE TABLE detalles_compras (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_compra INT NOT NULL,
    id_repuesto INT NOT NULL,
    cantidad INT,
    precio_unitario DECIMAL(10, 2),
    subtotal DECIMAL(10, 2),
    FOREIGN KEY (id_compra) REFERENCES compras(id_compra),
    FOREIGN KEY (id_repuesto) REFERENCES repuestos(id_repuesto)
);
```

**Campos:**
- `id_detalle`: Identificador único
- `id_compra`: Referencia a la compra
- `id_repuesto`: Referencia al producto
- `cantidad`: Unidades compradas
- `precio_unitario`: Precio al momento de compra
- `subtotal`: cantidad × precio_unitario

---

### 🔐 SEGURIDAD

#### **1. Validación de Sesión**
```php
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit;
}
```

#### **2. Sanitización de Inputs**
```php
$id_repuesto = intval($_GET['id']); // Convierte a INT
```

#### **3. Queries Preparadas (PRÓXIMA MEJORA)**
```php
// Actual (vulnerable):
$sql = "SELECT * FROM repuestos WHERE id_repuesto = $id";

// Mejor (parameterizado):
$stmt = $conexion->prepare("SELECT * FROM repuestos WHERE id_repuesto = ?");
$stmt->bind_param("i", $id);
```

---

### 🎨 FUNCIONES JS PRINCIPALES

#### **agregarAlCarrito(id, nombre)**
```javascript
function agregarAlCarrito(id, nombre) {
    fetch('api_carrito.php?action=agregar&id=' + id)
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert('✅ ' + nombre + ' agregado al carrito');
                actualizarContadorCarrito();
                cerrarModal();
            }
        });
}
```

**Parámetros:**
- `id`: ID del repuesto
- `nombre`: Nombre del producto (para la alerta)

---

#### **actualizarContadorCarrito()**
```javascript
function actualizarContadorCarrito() {
    fetch('api_carrito.php?action=contar')
        .then(res => res.json())
        .then(data => {
            if(data.success && data.cantidad > 0) {
                document.getElementById('cartCount').textContent = data.cantidad;
                document.getElementById('cartCount').style.display = 'flex';
            }
        });
}
```

**Efecto:** Actualiza el badge rojo con el contador

---

### 📝 CÓMO EXTENDER

#### **Agregar Descuentos**

1. Agregar columna a BD:
```sql
ALTER TABLE compras ADD COLUMN descuento DECIMAL(10, 2) DEFAULT 0;
```

2. Actualizar api_carrito.php:
```php
$descuento = calcularDescuento($total);
$total_final = $total - $descuento;
```

3. Guardar en BD:
```sql
INSERT INTO compras (..., descuento) VALUES (..., $descuento);
```

---

#### **Agregar Método de Pago**

1. Agregar columna:
```sql
ALTER TABLE compras ADD COLUMN metodo_pago VARCHAR(50);
```

2. Agregar en carrito.php:
```html
<select id="metodo" name="metodo_pago">
    <option>Efectivo</option>
    <option>Tarjeta</option>
    <option>Transferencia</option>
</select>
```

3. Enviar en finalizar:
```javascript
body: 'action=finalizar_compra&metodo=' + document.getElementById('metodo').value
```

---

#### **Agregar Historial de Compras**

1. Crear página: `historial_compras.php`
```php
$res = mysqli_query($conexion, "SELECT * FROM compras WHERE id_usuario = $id_usuario ORDER BY fecha_compra DESC");
```

2. Agregar en menú lateral o módulos

3. Mostrar tabla con compras pasadas

---

### 🧪 TESTING

#### **Test 1: Agregar Item**
```
1. Ir a Repuestos
2. Click "Agregar al carrito"
3. Ver alerta ✅
4. Badge debe mostrar 1
```

#### **Test 2: Múltiples Items**
```
1. Agregar 3 productos diferentes
2. Verificar badge muestra 3
3. Click en 🛒
4. Verificar tabla con 3 items
```

#### **Test 3: Cambiar Cantidad**
```
1. En carrito, click [+] en un item
2. Cantidad debe aumentar
3. Subtotal debe recalcularse
4. Total debe actualizarse
```

#### **Test 4: Finalizar Compra**
```
1. Click "Finalizar Compra"
2. Confirmar alerta
3. Verificar en BD: INSERT en compras
4. Verificar en BD: INSERT en detalles_compras
5. Carrito debe vaciarse
```

---

### 🐛 ERRORES COMUNES

| Error | Causa | Solución |
|-------|-------|----------|
| `$_SESSION['carrito']` vacío | Sesión no iniciada | `session_start()` en carrito.php |
| Items no se guardan | Archivo api_carrito.php no existe | Verificar ruta |
| Badge no se actualiza | JS no cargado | Recarga F5 |
| Error BD al finalizar | Tablas no existen | Ejecutar crear_tabla_compras.php |
| Compra se repite | API llamada 2x | Verificar doble clic en botón |

---

### 📊 QUERIES ÚTILES

**Ver todas las compras:**
```sql
SELECT c.*, u.nombre 
FROM compras c 
JOIN usuarios u ON c.id_usuario = u.id_usuario 
ORDER BY c.fecha_compra DESC;
```

**Ver detalles de una compra:**
```sql
SELECT dc.*, r.nombre 
FROM detalles_compras dc 
JOIN repuestos r ON dc.id_repuesto = r.id_repuesto 
WHERE dc.id_compra = 1;
```

**Total vendido por usuario:**
```sql
SELECT u.nombre, COUNT(*) as compras, SUM(c.total) as total_gastado
FROM compras c
JOIN usuarios u ON c.id_usuario = u.id_usuario
GROUP BY c.id_usuario;
```

**Producto más vendido:**
```sql
SELECT r.nombre, SUM(dc.cantidad) as total_vendido
FROM detalles_compras dc
JOIN repuestos r ON dc.id_repuesto = r.id_repuesto
GROUP BY dc.id_repuesto
ORDER BY total_vendido DESC
LIMIT 1;
```

---

### 🚀 PRÓXIMAS VERSIONES

**v2.0:**
- [ ] Autenticación mejorada (JWT)
- [ ] Carrito persistente (BD en lugar de sesión)
- [ ] Historial de compras del cliente
- [ ] Sistema de cupones
- [ ] Carrito compartido entre dispositivos

**v3.0:**
- [ ] Integración de pagos (Stripe, PayPal)
- [ ] Email de confirmación
- [ ] Seguimiento de envíos
- [ ] Reseñas de productos
- [ ] Recomendaciones personalizadas

---

**Última actualización:** 6 de mayo de 2026
**Versión:** 1.0
**Desarrollador:** Sistema automático
