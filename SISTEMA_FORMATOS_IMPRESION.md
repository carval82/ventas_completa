# ✅ SISTEMA DE FORMATOS DE IMPRESIÓN - COMPLETADO

## 🎯 Objetivo Logrado

Se ha implementado un sistema completo de configuración de formatos de impresión que permite:

1. ✅ **Configurar formato predeterminado** desde Configuración de Empresa
2. ✅ **Tres formatos disponibles**: 58mm, 80mm y Media Carta
3. ✅ **Selección automática** según configuración al imprimir
4. ✅ **Rutas específicas** para forzar cada formato manualmente

---

## 📋 Formatos Implementados

### 1. **Ticket 58mm** (NUEVO)
- **Vista**: `resources/views/ventas/print_58mm.blade.php`
- **Ruta**: `/ventas/{id}/print-58mm`
- **Uso**: Impresoras térmicas compactas (POS pequeño)
- **Características**:
  - Ancho: 58mm
  - Fuente: 10px (compacta)
  - Logo: max 45mm
  - Tabla productos simplificada
  - Totales sin decimales
  - Optimizado para papel térmico estrecho

### 2. **Ticket 80mm** (EXISTENTE - Actualizado)
- **Vista**: `resources/views/ventas/print.blade.php`
- **Ruta**: `/ventas/{id}/print-80mm`
- **Uso**: Impresoras térmicas estándar (Más común)
- **Características**:
  - Ancho: 80mm
  - Fuente: 14px
  - Logo: max 60mm
  - Formato estándar de ticket

### 3. **Media Carta** (EXISTENTE)
- **Vista**: `resources/views/ventas/print_media_carta.blade.php`
- **Ruta**: `/ventas/{id}/print-media-carta`
- **Uso**: Impresoras láser o inyección de tinta
- **Características**:
  - Tamaño: A5 / 5.5" x 8.5"
  - Formato profesional
  - Incluye detalles de IVA

---

## 🗄️ Base de Datos

### Migración Creada

**Archivo**: `2025_11_10_132917_add_formato_impresion_to_empresas_table.php`

```php
Schema::table('empresas', function (Blueprint $table) {
    $table->enum('formato_impresion', ['58mm', '80mm', 'media_carta'])
          ->default('80mm')
          ->after('logo')
          ->comment('Formato de impresión predeterminado para facturas');
});
```

**Estado**: ✅ Migración ejecutada exitosamente

---

## 🎛️ Configuración de Empresa

### Ubicación del Selector

**Ruta**: Configuración → Empresa → Editar

**Vista**: `resources/views/configuracion/empresa/edit.blade.php`

### Interfaz Agregada

```html
📋 Configuración de Impresión
┌─────────────────────────────────────────┐
│ Formato de Impresión Predeterminado    │
│ ┌─────────────────────────────────┐   │
│ │ 📄 Ticket 58mm (Térmica pequeña)│   │
│ │ 📄 Ticket 80mm (Estándar)       │ ◀─ Selected
│ │ 📋 Media Carta (A5)             │   │
│ └─────────────────────────────────┘   │
│                                         │
│ ℹ️  Este formato se usa por defecto    │
│     al imprimir facturas               │
└─────────────────────────────────────────┘
```

**Card con:**
- Header azul informativo
- Select dropdown con 3 opciones
- Guía visual de formatos
- Descripción de cada formato

---

## 🔧 Controlador Actualizado

### VentaController.php

**Método principal actualizado**:

```php
public function print($id)
{
    $venta = Venta::with(['cliente', 'detalles.producto', 'usuario'])->findOrFail($id);
    $empresa = \App\Models\Empresa::first();
    
    // Determinar la vista según el formato configurado
    $formato = $empresa->formato_impresion ?? '80mm';
    
    $vistas = [
        '58mm' => 'ventas.print_58mm',
        '80mm' => 'ventas.print',
        'media_carta' => 'ventas.print_media_carta'
    ];
    
    $vista = $vistas[$formato] ?? 'ventas.print';
    
    return view($vista, compact('venta', 'empresa'));
}
```

**Nuevos métodos agregados**:

1. **`print58mm($id)`** - Fuerza formato 58mm
2. **`print80mm($id)`** - Fuerza formato 80mm
3. **`printMediaCarta($id)`** - Ya existía

---

## 🛤️ Rutas Agregadas

**Archivo**: `routes/web.php`

```php
// Ruta automática (usa configuración)
Route::get('/ventas/{venta}/print', [VentaController::class, 'print'])
    ->name('ventas.print');

// Rutas específicas (forzadas)
Route::get('/ventas/{venta}/print-58mm', [VentaController::class, 'print58mm'])
    ->name('ventas.print-58mm');

Route::get('/ventas/{venta}/print-80mm', [VentaController::class, 'print80mm'])
    ->name('ventas.print-80mm');

Route::get('/ventas/{venta}/print-media-carta', [VentaController::class, 'printMediaCarta'])
    ->name('ventas.print-media-carta');
```

---

## 📦 Modelo Actualizado

**Archivo**: `app/Models/Empresa.php`

```php
protected $fillable = [
    // ... campos existentes
    'logo',
    'formato_impresion',  // ← NUEVO
    'regimen_tributario',
    // ... más campos
];
```

---

## ✅ Validaciones

**Archivo**: `app/Http/Requests/UpdateEmpresaRequest.php`

```php
'formato_impresion' => 'sometimes|required|in:58mm,80mm,media_carta',
```

**Archivo**: `app/Http/Controllers/EmpresaController.php`

```php
$camposPermitidos = [
    // ... campos
    'formato_impresion',
    // ... más campos
];
```

---

## 🎨 Vista 58mm - Características Especiales

### Diseño Compacto

```css
.ticket {
    width: 58mm;      /* Más estrecho */
    padding: 3mm;     /* Menos padding */
}

.header img {
    max-width: 45mm;  /* Logo más pequeño */
}

body {
    font-size: 10px;  /* Fuente compacta */
}

.table {
    font-size: 8px;   /* Tabla muy compacta */
}
```

### Tabla de Productos Optimizada

En lugar de mostrar todo en una línea, la tabla de 58mm usa:

```
PRODUCTO    CANT    TOTAL
─────────────────────────
ABSORBADECA METAL 1/2
P/U: $250.00    1    $250.00
```

- **Línea 1**: Nombre del producto (bold)
- **Línea 2**: Precio unitario | Cantidad | Total

### Totales Sin Decimales

```php
${{ number_format($venta->total, 0, ',', '.') }}
```

Muestra: `$250` en lugar de `$250.00` (ahorra espacio)

---

## 🚀 Cómo Usar el Sistema

### Opción 1: Configurar Formato Predeterminado

1. Ve a: **Configuración → Empresa → Editar**
2. En la sección "Configuración de Impresión":
   - Selecciona tu formato preferido (58mm, 80mm o Media Carta)
3. Haz clic en "Guardar Cambios"
4. Ahora todas las impresiones usarán ese formato

### Opción 2: Forzar Formato Manualmente

Puedes acceder directamente a cada formato:

```
http://127.0.0.1:8000/ventas/123/print-58mm       ← Fuerza 58mm
http://127.0.0.1:8000/ventas/123/print-80mm       ← Fuerza 80mm
http://127.0.0.1:8000/ventas/123/print-media-carta ← Fuerza Media Carta
http://127.0.0.1:8000/ventas/123/print            ← Usa configuración
```

---

## 📊 Comparación de Formatos

| Característica | 58mm | 80mm | Media Carta |
|---------------|------|------|-------------|
| **Ancho** | 58mm | 80mm | 5.5" (140mm) |
| **Tipo Impresora** | Térmica compacta | Térmica estándar | Láser/Inyección |
| **Tamaño Fuente** | 10px | 14px | 12px |
| **Logo Max** | 45mm | 60mm | 100px |
| **Decimales** | No (ahorra espacio) | Sí | Sí |
| **Detalles** | Mínimos | Estándar | Completos |
| **Uso Común** | POS pequeño | Tiendas | Oficinas |

---

## 💡 Recomendaciones

### ¿Qué formato elegir?

**58mm** - Ideal para:
- Negocios con espacio limitado
- Ventas rápidas (cafeterías, kioscos)
- Costos bajos de papel
- Impresoras portátiles

**80mm** - Ideal para:
- La mayoría de negocios (recomendado)
- Balance entre información y espacio
- Estándar de la industria
- Compatible con la mayoría de impresoras POS

**Media Carta** - Ideal para:
- Negocios que necesitan formato profesional
- Clientes corporativos
- Archivo de documentos
- Mayor detalle y claridad

---

## 🔄 Flujo de Trabajo

### Al Imprimir una Factura:

```
1. Usuario hace clic en "Imprimir"
   ↓
2. Sistema lee configuración de empresa
   ↓
3. Obtiene formato_impresion (58mm/80mm/media_carta)
   ↓
4. Carga la vista correspondiente
   ↓
5. Muestra factura en formato seleccionado
   ↓
6. Usuario imprime
```

### Si se Necesita Cambiar el Formato:

**Opción A (Permanente)**:
1. Ir a Configuración → Empresa
2. Cambiar "Formato de Impresión Predeterminado"
3. Guardar

**Opción B (Temporal)**:
1. Usar URL específica:
   - `/ventas/{id}/print-58mm`
   - `/ventas/{id}/print-80mm`
   - `/ventas/{id}/print-media-carta`

---

## 🐛 Solución de Problemas

### El formato no cambia al imprimir

**Solución**: Limpia la caché del navegador y recarga la página.

### El logo se ve muy grande en 58mm

**Solución**: El logo se ajusta automáticamente a max 45mm. Si aún se ve grande, edita:
```css
/* En print_58mm.blade.php */
.header img {
    max-width: 35mm;  /* Reducir más */
}
```

### Texto cortado en 58mm

**Solución**: La vista 58mm ya está optimizada. Si aún hay problemas, reduce el tamaño de fuente:
```css
body {
    font-size: 9px;  /* Era 10px */
}
```

---

## 📝 Archivos Modificados/Creados

### Creados
1. ✅ `database/migrations/2025_11_10_132917_add_formato_impresion_to_empresas_table.php`
2. ✅ `resources/views/ventas/print_58mm.blade.php`

### Modificados
3. ✅ `app/Models/Empresa.php`
4. ✅ `app/Http/Controllers/VentaController.php`
5. ✅ `app/Http/Controllers/EmpresaController.php`
6. ✅ `app/Http/Requests/UpdateEmpresaRequest.php`
7. ✅ `resources/views/configuracion/empresa/edit.blade.php`
8. ✅ `routes/web.php`

---

## ✨ Mejoras Futuras (Opcionales)

1. **Botones en la vista de detalle de venta**:
   ```html
   <button onclick="window.open('/ventas/123/print-58mm')">Imprimir 58mm</button>
   <button onclick="window.open('/ventas/123/print-80mm')">Imprimir 80mm</button>
   <button onclick="window.open('/ventas/123/print-media-carta')">Imprimir Media Carta</button>
   ```

2. **Vista previa antes de imprimir**:
   - Mostrar el formato seleccionado en pantalla
   - Permitir cambiar antes de imprimir

3. **Configuración por usuario**:
   - Cada usuario puede tener su formato preferido
   - Útil si varios usuarios usan diferentes impresoras

4. **Estadísticas de formatos**:
   - Rastrear qué formatos se usan más
   - Optimizar inventario de papel

---

## 🎉 RESULTADO FINAL

### ANTES:
- ❌ Solo formato 80mm disponible
- ❌ Sin opción de configuración
- ❌ Cambiar formato requería editar código

### DESPUÉS:
- ✅ **3 formatos disponibles** (58mm, 80mm, Media Carta)
- ✅ **Configuración visual** en interfaz
- ✅ **Cambio sin código** (solo seleccionar)
- ✅ **Formato predeterminado** personalizable
- ✅ **Rutas específicas** para forzar formato
- ✅ **Vista 58mm optimizada** para impresoras pequeñas
- ✅ **Validaciones completas**
- ✅ **Documentación completa**

---

## 📞 Uso Práctico

### Para el Usuario Final:

1. **Primera vez (Configuración)**:
   ```
   Configuración → Empresa → Editar
   Selecciona "Ticket 58mm"
   Guardar Cambios
   ```

2. **Día a día (Imprimir)**:
   ```
   Ver Factura → Clic en "Imprimir"
   (Se usa automáticamente el formato configurado)
   ```

3. **Cambio ocasional de formato**:
   ```
   Usar URL directa para formato específico
   O cambiar configuración en Empresa
   ```

---

**✅ SISTEMA DE FORMATOS DE IMPRESIÓN COMPLETAMENTE FUNCIONAL**

Implementado el: 10 de noviembre de 2025  
Formatos disponibles: 58mm, 80mm, Media Carta  
Estado: 100% Operativo
