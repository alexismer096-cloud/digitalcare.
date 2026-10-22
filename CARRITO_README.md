## 🛒 SISTEMA DE CARRITO DE COMPRAS - Digital Care

### ✅ QUÉ SE IMPLEMENTÓ

Se ha agregado un **carrito de compras único por cliente** con las siguientes características:

#### 1. **Botón Carrito en la Interfaz**
   - 🛒 Ubicado en la barra superior (topbar) junto a 🏠 y ↪
   - Muestra un contador rojo con el número de items
   - Click lleva directamente a `carrito.php`

#### 2. **Agregar Items al Carrito**
   - **Opción 1**: Desde la tarjeta de repuestos → botón "🛒 Agregar al carrito"
   - **Opción 2**: Ver detalles del repuesto → modal → "🛒 Agregar al carrito"
   - La sesión guarda los items en `$_SESSION['carrito']`

#### 3. **Página de Carrito** (`carrito.php`)
   - Lista completa de items con:
     - Nombre del producto
     - Descripción
     - Precio unitario
     - Controles de cantidad (+/-)
     - Botón eliminar (🗑️)
   - **Resumen de Pedido**:
     - Total de items
     - Total a pagar
     - Botón "Vaciar Carrito"
     - Botón "Finalizar Compra"

#### 4. **Funcionalidades del Carrito**
   - **Agregar**: Aumenta cantidad si existe, o agrega nuevo item
   - **Cambiar Cantidad**: +/- por cada item
   - **Eliminar**: Borra individual del carrito
   - **Limpiar**: Vacía todo el carrito
   - **Finalizar**: Guarda la compra en BD y limpia la sesión

#### 5. **Base de Datos**
   - Tabla `compras`: id_compra, id_usuario, total, estado, fecha_compra
   - Tabla `detalles_compras`: id_detalle, id_compra, id_repuesto, cantidad, precio_unitario, subtotal

---

### 📝 CÓMO USAR

#### **Para Clientes:**

1. **Navegar a Repuestos**: Click en menú → Módulo Repuestos
2. **Agregar Items**: 
   - Opción A: Click en "🛒 Agregar al carrito" de la tarjeta
   - Opción B: "Ver Detalles" → Modal → "🛒 Agregar al carrito"
3. **Confirmación**: Aparecerá alerta verde ✅
4. **Ver Carrito**: Click en 🛒 de la topbar
5. **Gestionar**: Cambiar cantidades o eliminar items
6. **Finalizar**: Click en "✅ Finalizar Compra"
7. **Confirmación**: Se guarda en BD y regresa al dashboard

#### **Para Administrador:**

Puedes ver las compras consultando la BD:
```sql
SELECT * FROM compras;
SELECT * FROM detalles_compras;
```

---

### 🔧 INSTALACIÓN

#### **Paso 1: Crear las Tablas**

Opción A - Usando PHP:
```
Accede a: http://localhost/tesis%20pechuga/crear_tabla_compras.php
```

Opción B - Usando SQL directo:
1. Abre PhpMyAdmin
2. Selecciona BD `digital_care`
3. Copia el contenido de `tablas_compras.sql`
4. Ejecuta en la pestaña SQL

#### **Paso 2: Verificar Archivos**

- ✅ `carrito.php` - Página del carrito
- ✅ `api_carrito.php` - API del carrito
- ✅ `crear_tabla_compras.php` - Crear tablas
- ✅ `tablas_compras.sql` - Script SQL
- ✅ `module.php` - Modificado (botón carrito + agregar desde modal)
- ✅ `module.js` - Compatible (funciones del carrito)

---

### 🎯 FLUJOS DISPONIBLES

#### **A. Agregar desde Tarjeta de Repuesto**
```
[Ver Repuestos] → [Tarjeta con "🛒 Agregar al carrito"] → ✅ Alerta → Badge actualizado
```

#### **B. Agregar desde Modal de Detalles**
```
[Ver Repuestos] → [Ver Detalles] → [Modal abierto] → [🛒 Agregar al carrito] → ✅ Alerta → Modal cierra
```

#### **C. Gestionar Carrito**
```
[🛒 Topbar] → [Ver todos los items] → [Cambiar cantidad/Eliminar] → [Finalizar Compra] → BD guardada
```

---

### 💾 ESTRUCTURA DE DATOS

**Compra guardada en BD:**
```json
{
  "id_compra": 1,
  "id_usuario": 5,
  "total": 250.50,
  "estado": "Completado",
  "fecha_compra": "2026-05-06 14:30:00",
  "detalles": [
    {
      "id_repuesto": 3,
      "cantidad": 2,
      "precio_unitario": 50.25,
      "subtotal": 100.50
    },
    {
      "id_repuesto": 7,
      "cantidad": 3,
      "precio_unitario": 50.00,
      "subtotal": 150.00
    }
  ]
}
```

---

### 🐛 TROUBLESHOOTING

| Problema | Solución |
|----------|----------|
| No aparece el botón 🛒 | Recarga la página (F5) |
| El carrito no guarda items | Verifica que sesiones estén activas |
| Error al finalizar compra | Corre `crear_tabla_compras.php` |
| El contador no se actualiza | Espera 1 segundo o recarga la página |
| Items no se guardan en BD | Verifica permisos y conexión DB |

---

### 🚀 PRÓXIMAS MEJORAS (OPCIONALES)

- [ ] Historial de compras del cliente
- [ ] Descuentos y cupones
- [ ] Cálculo de impuestos
- [ ] Métodos de pago integrados
- [ ] Notificación por email
- [ ] Exportar PDF de factura

---

**Sistema implementado el:** 6 de mayo de 2026
**Versión:** 1.0
**Estado:** ✅ Funcionando
