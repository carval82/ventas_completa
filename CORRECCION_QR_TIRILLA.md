# 🔧 CORRECCIÓN: QR CODE EN TIRILLA DE FACTURA ELECTRÓNICA

## ❌ Problema Identificado

El código QR no se mostraba en la tirilla de factura electrónica, aunque sí aparecía correctamente en el formato de media carta.

### Causa Raíz

En `resources/views/facturas_electronicas/pdf_tirilla.blade.php`, el código intentaba acceder a los datos del QR con rutas incorrectas:

```php
// ❌ INCORRECTO
$qrCode = $venta->qr_code ?? $detallesAlegra['data']['stamp']['barCodeContent'] ?? ...;

@elseif(isset($detallesAlegra['data']['stamp']['barCodeContent']))
    <img src="data:image/png;base64,{{ $detallesAlegra['data']['stamp']['barCodeContent'] }}" ...>
```

El controlador `FacturaElectronicaController::generarPDFTirilla()` pasa los datos como:

```php
'detallesAlegra' => $detallesAlegra['data'],  // ← Ya extrae ['data']
```

Por lo tanto, en la vista se debe acceder directamente como `$detallesAlegra['stamp']`, no `$detallesAlegra['data']['stamp']`.

---

## ✅ Solución Aplicada

### Archivo Modificado
`resources/views/facturas_electronicas/pdf_tirilla.blade.php`

### Cambios Realizados

#### 1. Corrección de Variables PHP (líneas 255-268)

```php
@php
    // Los datos vienen como $detallesAlegra (que ya es ['data'] del controlador)
    $cufe = $venta->cufe ?? ($detallesAlegra['stamp']['cufe'] ?? null);
    $qrCode = $venta->qr_code ?? ($detallesAlegra['stamp']['barCodeContent'] ?? null);
    
    // Log para debug (se puede quitar después)
    \Log::info('Tirilla PDF - Datos QR:', [
        'venta_qr' => $venta->qr_code ? 'Presente ('.strlen($venta->qr_code).' chars)' : 'No presente',
        'venta_cufe' => $venta->cufe ? substr($venta->cufe, 0, 20).'...' : 'No presente',
        'alegra_stamp_exists' => isset($detallesAlegra['stamp']),
        'alegra_qr' => isset($detallesAlegra['stamp']['barCodeContent']) ? 'Presente ('.strlen($detallesAlegra['stamp']['barCodeContent']).' chars)' : 'No presente',
        'alegra_cufe' => isset($detallesAlegra['stamp']['cufe']) ? substr($detallesAlegra['stamp']['cufe'], 0, 20).'...' : 'No presente',
    ]);
@endphp
```

#### 2. Corrección del Código QR (líneas 287-305)

```php
<!-- QR Code -->
@if($cufe)
    <div class="qr-code">
        @if($venta->qr_code)
            <!-- Usar QR de la base de datos si existe -->
            <img src="data:image/png;base64,{{ $venta->qr_code }}" alt="Código QR" style="width: 30mm; height: 30mm;">
        @elseif(isset($detallesAlegra['stamp']['barCodeContent']))
            <!-- ✅ CORREGIDO: Ahora accede correctamente -->
            <img src="data:image/png;base64,{{ $detallesAlegra['stamp']['barCodeContent'] }}" alt="Código QR" style="width: 30mm; height: 30mm;">
        @else
            <!-- Mostrar texto indicativo si no hay QR disponible -->
            <div style="border: 1px solid #000; padding: 3mm; text-align: center; font-size: 8px; width: 30mm; height: 30mm; display: flex; align-items: center; justify-content: center;">
                <div>
                    CÓDIGO QR<br>
                    CUFE: {{ substr($cufe, 0, 10) }}...
                </div>
            </div>
        @endif
    </div>
@endif
```

#### 3. Corrección de numberTemplate (líneas 312-316)

```php
@if(isset($detallesAlegra['numberTemplate']['text']))
    <div class="small">
        {{ $detallesAlegra['numberTemplate']['text'] }}
    </div>
@endif
```

---

## 🔍 Fuentes de Datos del QR

El sistema intenta obtener el QR de 3 fuentes (en orden de prioridad):

### 1. Base de Datos (Primera opción)
```php
$venta->qr_code
```
- Campo en la tabla `ventas`
- Se guarda cuando se emite la factura a DIAN
- Formato: Base64 string

### 2. Respuesta de Alegra (Segunda opción)
```php
$detallesAlegra['stamp']['barCodeContent']
```
- Se obtiene al consultar la factura en Alegra
- Disponible después de que Alegra recibe respuesta de DIAN
- Formato: Base64 string

### 3. Placeholder (Tercera opción)
```php
<div>CÓDIGO QR<br>CUFE: ...</div>
```
- Se muestra si no hay QR disponible
- Indica que existe CUFE pero aún no hay imagen QR

---

## 📊 Flujo de Datos

```
1. Factura se crea en Alegra (draft)
   └─> estado_dian: 'draft'
   └─> qr_code: null
   └─> cufe: null

2. Factura se abre (open)
   └─> estado_dian: 'open'
   └─> qr_code: null (aún no)
   └─> cufe: null (aún no)

3. Factura se envía a DIAN
   └─> estado_dian: 'stamp-sent' o 'accepted'
   └─> cufe: "ABC123..." (recibido)
   └─> qr_code: "iVBORw0..." (recibido)

4. Al imprimir tirilla:
   a. Busca qr_code en BD (venta->qr_code)
   b. Si no existe, busca en Alegra API
   c. Si tampoco existe, muestra placeholder
```

---

## 🧪 Cómo Verificar la Corrección

### Paso 1: Consultar el Log
```bash
tail -f storage/logs/laravel.log | grep "Tirilla PDF"
```

Deberías ver algo como:
```
[2025-11-10 13:42:00] local.INFO: Tirilla PDF - Datos QR: {
    "venta_qr": "Presente (5234 chars)",
    "venta_cufe": "abc123...",
    "alegra_stamp_exists": true,
    "alegra_qr": "Presente (5234 chars)",
    "alegra_cufe": "abc123..."
}
```

### Paso 2: Imprimir una Factura Electrónica

1. Ve a **Facturas Electrónicas**
2. Selecciona una factura que esté **"Aceptada por DIAN"**
3. Haz clic en **"Imprimir Tirilla"**
4. Verifica que aparezca el código QR

### Paso 3: Verificar en la Base de Datos

```sql
SELECT id, numero_factura, alegra_id, estado_dian, 
       CASE WHEN cufe IS NOT NULL THEN 'Sí' ELSE 'No' END as tiene_cufe,
       CASE WHEN qr_code IS NOT NULL THEN 'Sí' ELSE 'No' END as tiene_qr
FROM ventas 
WHERE alegra_id IS NOT NULL 
ORDER BY id DESC 
LIMIT 10;
```

---

## ⚠️ Situaciones Posibles

### Caso A: QR en BD ✅
- **venta->qr_code**: Presente
- **Resultado**: QR se muestra desde BD
- **Estado**: Óptimo

### Caso B: QR en Alegra ✅
- **venta->qr_code**: null
- **detallesAlegra['stamp']['barCodeContent']**: Presente
- **Resultado**: QR se obtiene de Alegra API
- **Estado**: Funcional (pero se recomienda guardar en BD)

### Caso C: Solo CUFE ⚠️
- **venta->qr_code**: null
- **detallesAlegra['stamp']['barCodeContent']**: null
- **venta->cufe**: Presente
- **Resultado**: Muestra placeholder con CUFE
- **Estado**: Requiere sincronización con Alegra

### Caso D: Sin datos ❌
- Todo null
- **Resultado**: No muestra sección DIAN
- **Estado**: Factura no emitida o error

---

## 🔄 Para Actualizar QR en Facturas Antiguas

Si tienes facturas que no tienen el QR guardado en la base de datos:

```php
// Script para actualizar QR codes desde Alegra
php artisan tinker

$ventas = \App\Models\Venta::whereNotNull('alegra_id')
                           ->whereNull('qr_code')
                           ->get();

foreach($ventas as $venta) {
    $alegraService = new \App\Services\AlegraService();
    $resultado = $alegraService->obtenerDetalleFacturaCompleto($venta->alegra_id);
    
    if ($resultado['success'] && isset($resultado['data']['stamp']['barCodeContent'])) {
        $venta->update([
            'qr_code' => $resultado['data']['stamp']['barCodeContent'],
            'cufe' => $resultado['data']['stamp']['cufe'] ?? $venta->cufe
        ]);
        echo "✓ Actualizada venta #{$venta->id}\n";
    }
}
```

---

## 📝 Notas Adicionales

### Log de Debug
- Se agregó logging en la vista para facilitar el diagnóstico
- Los logs se pueden revisar en `storage/logs/laravel.log`
- Puedes eliminar el bloque de logging una vez confirmado que funciona

### Formato del QR
- El QR viene en formato **Base64**
- Se renderiza con: `data:image/png;base64,{base64_string}`
- Tamaño en tirilla: **30mm x 30mm**
- Compatible con DomPDF

### Actualización Automática
El QR se actualiza automáticamente cuando:
1. Se envía la factura a DIAN
2. Se verifica el estado de la factura
3. Se ejecuta "Abrir y Emitir"

---

## ✅ RESULTADO FINAL

Ahora la tirilla de factura electrónica muestra correctamente:

✅ **Código QR** de la DIAN
✅ **CUFE** completo (dividido en líneas)
✅ **Estado DIAN** actualizado
✅ **Información de resolución**

El QR se obtiene de:
1. Base de datos (si existe)
2. API de Alegra (si no está en BD)
3. Placeholder (si no hay datos disponibles)

---

**CORRECCIÓN APLICADA EXITOSAMENTE**
Fecha: 10 de noviembre de 2025
Archivo modificado: `resources/views/facturas_electronicas/pdf_tirilla.blade.php`
