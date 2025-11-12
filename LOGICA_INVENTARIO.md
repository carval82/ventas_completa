# 📋 LÓGICA DE COTIZACIONES Y REMISIONES FRENTE AL INVENTARIO

## 🎯 RESUMEN EJECUTIVO

Este documento explica cómo funcionan las **Cotizaciones** y **Remisiones** en relación al **inventario** del sistema, detallando cuándo y cómo se afecta el stock de productos.

---

## 📋 COTIZACIONES Y INVENTARIO

### ❌ **LAS COTIZACIONES NO AFECTAN EL INVENTARIO**

```
COTIZACIÓN → NO IMPACTA STOCK
```

#### 🔍 **Razones:**
- **Son propuestas comerciales** - No compromisos firmes
- **Pueden ser rechazadas** - El cliente puede no aceptar
- **Pueden vencer** - Tienen fecha de vencimiento
- **Son estimaciones** - Precios y disponibilidad pueden cambiar

#### 📊 **Estados de Cotización:**
```
┌─────────────┬──────────────────┬─────────────────┐
│   ESTADO    │   DESCRIPCIÓN    │  IMPACTO STOCK  │
├─────────────┼──────────────────┼─────────────────┤
│ Pendiente   │ Esperando resp.  │      NINGUNO    │
│ Aprobada    │ Cliente acepta   │      NINGUNO    │
│ Rechazada   │ Cliente rechaza  │      NINGUNO    │
│ Vencida     │ Tiempo agotado   │      NINGUNO    │
│ Convertida  │ Se hizo venta    │      NINGUNO*   │
└─────────────┴──────────────────┴─────────────────┘
```
*El impacto al stock ocurre en la VENTA, no en la cotización.

#### 🔄 **Flujo de Cotización:**
```
1. CREAR COTIZACIÓN
   ├── Seleccionar productos
   ├── Definir cantidades
   ├── Calcular precios
   └── ❌ NO afectar stock

2. APROBAR/RECHAZAR
   ├── Cliente decide
   └── ❌ NO afectar stock

3. CONVERTIR A VENTA (opcional)
   ├── Crear nueva venta
   ├── ✅ AQUÍ SÍ se afecta stock
   └── Marcar cotización como convertida
```

---

## 🚚 REMISIONES Y INVENTARIO

### ✅ **LAS REMISIONES SÍ AFECTAN EL INVENTARIO**

```
REMISIÓN → IMPACTA STOCK INMEDIATAMENTE
```

#### 🔍 **Razones:**
- **Compromiso de entrega** - Productos reservados para cliente
- **Control de salidas** - Seguimiento de mercancía
- **Gestión logística** - Productos en tránsito
- **Responsabilidad legal** - Documento oficial de entrega

#### 📊 **Tipos de Remisión e Impacto:**
```
┌─────────────┬──────────────────┬─────────────────┬─────────────────┐
│    TIPO     │   DESCRIPCIÓN    │  IMPACTO STOCK  │   MOMENTO       │
├─────────────┼──────────────────┼─────────────────┼─────────────────┤
│ Venta       │ Entrega vendida  │   ⬇️ RESTA      │ Al crear        │
│ Traslado    │ Cambio ubicación │   ⬇️ RESTA      │ Al crear        │
│ Muestra     │ Producto gratis  │   ⬇️ RESTA      │ Al crear        │
│ Devolución  │ Cliente devuelve │   ⬆️ SUMA       │ Al crear        │
└─────────────┴──────────────────┴─────────────────┴─────────────────┘
```

#### 📊 **Estados de Remisión e Inventario:**
```
┌─────────────┬──────────────────┬─────────────────┬─────────────────┐
│   ESTADO    │   DESCRIPCIÓN    │  IMPACTO STOCK  │     ACCIÓN      │
├─────────────┼──────────────────┼─────────────────┼─────────────────┤
│ Pendiente   │ Creada, no sale  │   ⬇️ YA RESTADO │ Stock reservado │
│ En Tránsito │ Productos salen  │   ⬇️ YA RESTADO │ Mantiene        │
│ Entregada   │ Cliente recibe   │   ⬇️ YA RESTADO │ Mantiene        │
│ Devuelta    │ Regresa producto │   ⬆️ SUMA       │ Restaura stock  │
│ Cancelada   │ Se cancela       │   ⬆️ SUMA       │ Restaura stock  │
└─────────────┴──────────────────┴─────────────────┴─────────────────┘
```

---

## 🔄 FLUJOS DETALLADOS

### 📋 **Flujo: Cotización → Venta → Remisión**

```
1. COTIZACIÓN (Stock: Sin cambios)
   ├── Cliente solicita cotización
   ├── Se verifica disponibilidad actual
   ├── Se calcula precio
   └── ❌ Stock NO se afecta

2. VENTA (Stock: -Cantidad)
   ├── Cliente acepta cotización
   ├── Se crea venta desde cotización
   ├── ✅ Stock SE REDUCE
   └── Se marca cotización como convertida

3. REMISIÓN (Stock: Ya afectado en venta)
   ├── Se crea remisión desde venta
   ├── ❌ Stock NO se afecta (ya se redujo)
   └── Se controla entrega física
```

### 🚚 **Flujo: Remisión Directa**

```
1. CREAR REMISIÓN DIRECTA (Stock: -Cantidad)
   ├── Seleccionar productos
   ├── Definir cantidades
   ├── ✅ Stock SE REDUCE inmediatamente
   └── Productos quedan reservados

2. CAMBIAR ESTADO
   ├── Pendiente → En Tránsito: Sin cambio
   ├── En Tránsito → Entregada: Sin cambio
   ├── Cualquier → Devuelta: ✅ Stock SE SUMA
   └── Cualquier → Cancelada: ✅ Stock SE SUMA
```

---

## 🎛️ CONTROLES DE INVENTARIO

### ✅ **Validaciones Implementadas:**

#### 📋 **En Cotizaciones:**
```php
// Al crear cotización - Solo verificar disponibilidad
if ($producto->stock < $cantidad_solicitada) {
    // ⚠️ Advertencia: Stock insuficiente
    // ✅ Permitir crear cotización igual
}
```

#### 🚚 **En Remisiones:**
```php
// Al crear remisión - Validar stock obligatorio
if ($producto->stock < $cantidad_solicitada) {
    // ❌ Error: No se puede crear remisión
    // ❌ Bloquear operación
}

// Al crear - Reducir stock
$producto->decrement('stock', $cantidad);

// Al cancelar/devolver - Restaurar stock
$producto->increment('stock', $cantidad);
```

### 📊 **Control de Entregas Parciales:**

```php
// DetalleRemision tiene control granular:
- cantidad: Total a entregar
- cantidad_entregada: Ya entregada
- cantidad_devuelta: Devuelta por cliente
- cantidadPendiente(): Falta por entregar
```

---

## 🔧 MÉTODOS CLAVE IMPLEMENTADOS

### 📋 **Modelo Cotizacion:**
```php
// ❌ NO tiene métodos de stock
// Solo cálculos de totales y validaciones de negocio
```

### 🚚 **Modelo Remision:**
```php
public function actualizarStock($operacion = 'restar')
{
    foreach ($this->detalles as $detalle) {
        if ($operacion === 'restar') {
            $producto->decrement('stock', $detalle->cantidad);
        } elseif ($operacion === 'sumar') {
            $producto->increment('stock', $detalle->cantidad);
        }
    }
}
```

### 📦 **Modelo DetalleRemision:**
```php
public function registrarEntrega(float $cantidad): bool
{
    // Controla entregas parciales sin afectar stock
    // (El stock ya se redujo al crear la remisión)
}

public function registrarDevolucion(float $cantidad): bool
{
    // Devuelve al stock solo lo que se devuelve
    $this->producto->increment('stock', $cantidad);
}
```

---

## 📈 CASOS DE USO PRÁCTICOS

### 🎯 **Caso 1: Cotización Normal**
```
Stock inicial: 100 unidades
1. Cliente pide cotización de 50 → Stock: 100 (sin cambio)
2. Se envía cotización → Stock: 100 (sin cambio)
3. Cliente acepta → Se crea venta → Stock: 50 ✅
4. Se crea remisión → Stock: 50 (sin cambio adicional)
```

### 🎯 **Caso 2: Remisión Directa**
```
Stock inicial: 100 unidades
1. Se crea remisión directa de 30 → Stock: 70 ✅
2. Estado: Pendiente → Stock: 70
3. Estado: En Tránsito → Stock: 70
4. Estado: Entregada → Stock: 70
```

### 🎯 **Caso 3: Devolución**
```
Stock después de remisión: 70 unidades
1. Cliente devuelve 10 unidades
2. Se registra devolución → Stock: 80 ✅
3. Se actualiza estado a "Devuelta"
```

### 🎯 **Caso 4: Cancelación**
```
Stock después de remisión: 70 unidades
1. Se cancela remisión completa
2. Se restaura stock → Stock: 100 ✅
3. Estado cambia a "Cancelada"
```

---

## ⚠️ CONSIDERACIONES IMPORTANTES

### 🚨 **Alertas de Stock:**
- **Cotizaciones:** Mostrar advertencia si stock < cantidad
- **Remisiones:** Bloquear si stock < cantidad
- **Entregas:** Permitir entregas parciales
- **Devoluciones:** Validar que no exceda lo entregado

### 🔄 **Integridad de Datos:**
- Usar **transacciones DB** para operaciones críticas
- **Logs de auditoría** para cambios de stock
- **Validaciones en tiempo real** antes de confirmar
- **Rollback automático** en caso de errores

### 📊 **Reportes Sugeridos:**
- Stock reservado por remisiones pendientes
- Productos en tránsito por cliente
- Histórico de movimientos por producto
- Análisis de devoluciones por período

---

## 🎯 RESUMEN FINAL

| **MÓDULO**    | **IMPACTO STOCK** | **MOMENTO**        | **REVERSIBLE** |
|---------------|-------------------|--------------------|----------------|
| **Cotización** | ❌ NO            | Nunca              | N/A            |
| **Venta**      | ✅ SÍ (-Stock)   | Al crear/confirmar | ❌ NO          |
| **Remisión**   | ✅ SÍ (-Stock)   | Al crear           | ✅ SÍ          |

### 🎪 **Filosofía del Sistema:**
- **Cotizaciones = Intención** → No comprometen inventario
- **Ventas = Compromiso** → Reducen inventario definitivamente  
- **Remisiones = Ejecución** → Controlan la entrega física

**¡El sistema mantiene la integridad del inventario mientras permite flexibilidad comercial!** 🚀
