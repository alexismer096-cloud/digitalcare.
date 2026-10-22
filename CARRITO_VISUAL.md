## 📱 GUÍA VISUAL DEL CARRITO DE COMPRAS

### 1️⃣ TOPBAR ACTUALIZADA
```
┌─────────────────────────────────────────────────────────┐
│                                                         │
│  🌱 Digital Care    [🏠]  [🛒 2]  [↪]                   │
│                          └─ Badge con contador         │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

### 2️⃣ MÓDULO DE REPUESTOS - TARJETA DE PRODUCTO
```
┌──────────────────────────────────────────────────────┐
│                                                      │
│  [💾]                              [Stock: 15]       │
│                                                      │
│  💾 Disco SSD 480GB                                 │
│  Disco de estado sólido de alta velocidad           │
│                                                      │
│  $65.00    [Ver Detalles]  [🛒 Agregar al carrito]  │
│                                                      │
└──────────────────────────────────────────────────────┘
```

---

### 3️⃣ MODAL DE DETALLES (con opción de carrito)
```
┌────────────────────────────────────────┐
│                          ✕             │
│                                        │
│              [📦]                      │
│         Disco SSD 480GB                │
│                                        │
├────────────────────────────────────────┤
│                                        │
│ Descripción: Disco de estado sólido... │
│ Precio: $65.00                         │
│ Stock: 15 unidades                    │
│ ID: #3                                 │
│                                        │
│  [🛒 Agregar carrito]  [Cerrar]       │
│                                        │
└────────────────────────────────────────┘

Alerta confirmación:
✅ "Disco SSD 480GB agregado al carrito"
```

---

### 4️⃣ PÁGINA DEL CARRITO (carrito.php)
```
┌──────────────────────────────────────────────────────────┐
│                                                          │
│  🏠        🛒 Mi Carrito de Compras        ↪            │
│                                                          │
│  Cliente: Juan Pérez                                   │
│                                                          │
├──────────────────────────────────────────────────────────┤
│                                                          │
│ ┌────────────────────────────────────────────────────┐ │
│ │ 💾 Disco SSD 480GB                                │ │
│ │ Disco de estado sólido                            │ │
│ │ Precio: $65.00  [−] 2 [+]  Subtotal: $130  [🗑️]  │ │
│ └────────────────────────────────────────────────────┘ │
│                                                          │
│ ┌────────────────────────────────────────────────────┐ │
│ │ 🧠 RAM DDR4 8GB                                   │ │
│ │ Memoria rápida para PC                            │ │
│ │ Precio: $35.00  [−] 1 [+]  Subtotal: $35   [🗑️]  │ │
│ └────────────────────────────────────────────────────┘ │
│                                                          │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  ┌──────────────────────────────────────────────────┐  │
│  │ RESUMEN DEL PEDIDO                               │  │
│  │                                                  │  │
│  │ Total de items: 3 unidades                       │  │
│  │ ─────────────────────────────────────────────    │  │
│  │ Total a pagar: $165.00                           │  │
│  │                                                  │  │
│  │  [🗑️ Vaciar Carrito]  [✅ Finalizar Compra]    │  │
│  └──────────────────────────────────────────────────┘  │
│                                                          │
│  ← Seguir comprando                                    │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

---

### 5️⃣ FLUJO DE FINALIZACIÓN

**Paso 1:** Click en [✅ Finalizar Compra]
```
Alerta: "✅ Finalizar compra por: $165.00?"
[Cancelar]  [Aceptar]
```

**Paso 2:** Si acepta
```
✅ "¡Compra finalizada exitosamente!
    Número de pedido: #1"
```

**Paso 3:** Redirecciona a dashboard
```
El carrito se vacía automáticamente
La compra se guarda en BD
```

---

### 6️⃣ INTERACCIONES DEL CARRITO

**Cambiar Cantidad:**
```
Botón [−] decrece en 1
Botón [+] aumenta en 1
Se recalcula automáticamente el subtotal
```

**Eliminar Item:**
```
Click [🗑️]
Confirmación: "¿Estás seguro?"
Si acepta → item se elimina → página se recarga
```

**Vaciar Todo:**
```
Click [🗑️ Vaciar Carrito]
Confirmación: "¿Estás seguro de vaciar todo?"
Si acepta → carrito se limpia
```

---

### 7️⃣ ESTADOS POSIBLES

| Estado | Descripción | Botones Activos |
|--------|-------------|-----------------|
| **Carrito Vacío** | No hay items | Ver Repuestos |
| **Items Agregados** | 1+ items | Cambiar cant, Eliminar, Finalizar |
| **Procesando** | Guardando en BD | Deshabilitados |
| **Completado** | Compra guardada | Redirecciona |

---

### 8️⃣ COLORES Y ESTILOS

- **Botón Carrito**: 🛒 (neutro)
- **Badge Contador**: 🔴 Rojo (#ff4d4d)
- **Agregar**: 🟢 Verde (#17b67f)
- **Eliminar**: 🔴 Rojo (#ff4d4d)
- **Finalizar**: 🟢 Verde (#17b67f)
- **Detalles**: 🔵 Azul (#2397dd)
- **Fondo Cards**: Blanco con borde gris claro

---

### 9️⃣ NOTIFICACIONES

```
✅ Éxito:
   - Producto agregado
   - Compra finalizada

❌ Error:
   - Carrito vacío
   - Error en BD
   - Sesión expirada

⚠️ Confirmaciones:
   - ¿Eliminar item?
   - ¿Vaciar carrito?
   - ¿Finalizar compra?
```

---

### 🔟 DATOS GUARDADOS EN BD

**Tabla: compras**
```
id_compra | id_usuario | total  | estado     | fecha_compra
1         | 5          | 165.00 | Completado | 2026-05-06 14:30:00
2         | 3          | 89.50  | Completado | 2026-05-06 15:15:30
```

**Tabla: detalles_compras**
```
id_detalle | id_compra | id_repuesto | cantidad | precio_unitario | subtotal
1          | 1         | 3           | 2        | 65.00           | 130.00
2          | 1         | 7           | 1        | 35.00           | 35.00
3          | 2         | 5           | 2        | 44.75           | 89.50
```

---

**¡Sistema completamente funcional y listo para usar! 🚀**
