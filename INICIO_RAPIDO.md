## 🎯 GUÍA RÁPIDA - CÓMO USAR EL CARRITO

### 🚀 PRIMEROS PASOS (5 MINUTOS)

#### **Paso 1: Crear Tablas en la BD**

1. Abre tu navegador
2. Ve a: `http://localhost/tesis%20pechuga/crear_tabla_compras.php`
3. Deberías ver mensajes verdes ✅ indicando que se crearon las tablas

**Si algo sale mal:**
- Abre PhpMyAdmin
- Selecciona BD "digital_care"
- Copia el contenido de `tablas_compras.sql`
- Pégalo en la pestaña SQL y ejecuta

---

#### **Paso 2: Inicia Sesión**

1. Ve a: `http://localhost/tesis%20pechuga/`
2. Inicia sesión con un usuario válido (o regístrate)
3. Deberías ver el dashboard

---

#### **Paso 3: Prueba el Carrito**

1. Click en "Repuestos" (en el módulo de dashboard)
2. Verás una lista de repuestos con tarjetas
3. En cada tarjeta hay un botón verde: **[🛒 Agregar al carrito]**

---

### 📱 USANDO EL CARRITO

#### **AGREGAR PRODUCTOS**

**Opción 1: Desde la Tarjeta**
```
1. Busca un repuesto en la lista
2. Click en [🛒 Agregar al carrito]
3. Verás alerta: ✅ "Disco SSD agregado al carrito"
4. En la barra superior derecha, verás 🛒 con un número (ej: 🛒 1)
```

**Opción 2: Desde el Detalle**
```
1. Busca un repuesto
2. Click en [Ver Detalles]
3. Se abre una ventana modal (popup)
4. Click en [🛒 Agregar al carrito]
5. Modal se cierra automáticamente
6. El contador actualiza
```

---

#### **VER EL CARRITO**

```
1. Click en el icono 🛒 (con el número rojo) en la barra superior
2. Se abre la página del carrito
3. Verás una tabla con:
   - Nombre del producto
   - Descripción
   - Precio unitario
   - Cantidad (con botones +/−)
   - Subtotal
   - Botón 🗑️ para eliminar
```

---

#### **CAMBIAR CANTIDADES**

```
En la página del carrito, para cada producto:

[−] Botón MENOS: Reduce 1 unidad
[1] Número: Cantidad actual
[+] Botón MÁS: Aumenta 1 unidad

Ejemplo:
- Si hay 1 unidad, click [+] → ahora hay 2
- Si hay 2 unidades, click [−] → ahora hay 1
- El total se recalcula automáticamente
```

---

#### **ELIMINAR PRODUCTOS**

```
En el carrito, en cada fila de producto:

Click en [🗑️] → aparece confirmación
→ Si aceptas: Producto se elimina
→ Página se recarga automáticamente
```

---

#### **FINALIZAR COMPRA**

```
Paso 1: Verifica que tengas productos en el carrito
Paso 2: Desplázate al final de la página
Paso 3: Verás "RESUMEN DEL PEDIDO" con:
        - Total de items
        - Total a pagar
        - Botón: [✅ Finalizar Compra]

Paso 4: Click en [✅ Finalizar Compra]
Paso 5: Aparece confirmación: "¿Finalizar compra por: $XXX?"
Paso 6: Click "Aceptar"
Paso 7: Aparece: ✅ "¡Compra finalizada! Pedido #1"
Paso 8: Se regresa al dashboard automáticamente
Paso 9: El carrito se vacía
```

---

#### **VACIAR EL CARRITO**

```
En el "RESUMEN DEL PEDIDO":

1. Click en [🗑️ Vaciar Carrito]
2. Aparece: "¿Vaciar todo el carrito?"
3. Si aceptas: Todo se elimina
4. Página se recarga mostrando "Tu carrito está vacío"
```

---

### 🎨 INTERFAZ VISUAL

```
┌─────────────────────────────────────────┐
│                                         │
│  🌱 Digital Care    [🏠]  [🛒 3]  [↪]   │
│                          ↑
│                    Badge con items
│                                         │
└─────────────────────────────────────────┘

         REPUESTOS
┌──────────────────────────────────────────┐
│ 💾 Disco SSD 480GB                       │
│ Disco rápido de 480GB                    │
│ Precio: $65.00                           │
│ [Ver Detalles] [🛒 Agregar al carrito]   │
└──────────────────────────────────────────┘

         MI CARRITO (carrito.php)
┌──────────────────────────────────────────┐
│ 💾 Disco SSD 480GB × [−] 2 [+]  $130  🗑️│
│ 🧠 RAM DDR4 8GB × [−] 1 [+]  $35   🗑️ │
│                                          │
│ RESUMEN DEL PEDIDO                       │
│ ─────────────────────────────────────    │
│ Total: 3 items                           │
│ Total a pagar: $165.00                   │
│                                          │
│ [🗑️ Vaciar] [✅ Finalizar Compra]      │
└──────────────────────────────────────────┘
```

---

### ⚠️ PREGUNTAS FRECUENTES

**P: ¿Dónde se guarda mi carrito?**
R: En tu sesión (mientras tengas sesión abierta). Al cerrar sesión, se pierde, pero las compras finalizadas se guardan en la BD.

**P: ¿Puedo volver a agregar después de borrar un item?**
R: Sí, puedes agregar el mismo producto nuevamente.

**P: ¿Qué pasa si cierro el navegador?**
R: Si cierras completamente (logout), el carrito se vacía. Si solo cierras la pestaña, algunos navegadores pueden mantener la sesión.

**P: ¿Puedo cambiar de cantidad a 0?**
R: No, si la cantidad llega a 0, el producto se elimina automáticamente.

**P: ¿Se guarda la compra en algún lado?**
R: Sí, cuando haces "Finalizar Compra", se guarda en la base de datos. Puedes ver el historial (en futuras versiones).

**P: ¿Puedo agregar el mismo producto 2 veces?**
R: No necesitas. Si lo agregas 2 veces, el carrito aumenta la cantidad en lugar de duplicar la línea.

**P: ¿Hay límite de cantidad?**
R: No, puedes agregar tantas unidades como desees.

**P: ¿Hay descuentos o cupones?**
R: No en esta versión 1.0, pero está planeado para versiones futuras.

---

### 🐛 SI ALGO NO FUNCIONA

| Problema | Solución |
|----------|----------|
| No veo botón 🛒 en topbar | Recarga la página (F5) |
| Al hacer click no pasa nada | Abre F12 → Console para ver errores |
| El carrito no guarda items | Verifica que tengas sesión activa (login) |
| Error "tabla no existe" | Ejecuta crear_tabla_compras.php |
| El contador no se actualiza | Espera 2 segundos o recarga |
| No puedo finalizar la compra | Verifica que tengas items en el carrito |

---

### 🎓 PRÓXIMAS CARACTERÍSTICAS

En futuras versiones tendremos:
- [ ] Historial de compras
- [ ] Descuentos y cupones
- [ ] Métodos de pago (Tarjeta, Paypal, etc)
- [ ] Email de confirmación
- [ ] Recomendaciones personalizadas
- [ ] Carrito multi-dispositivo

---

**¡Listo para usar! 🚀**

Si tienes dudas, revisa:
- **CARRITO_README.md** - Información más detallada
- **CARRITO_VISUAL.md** - Mockups y flujos
- **Contacta al desarrollador** - Para asistencia técnica
