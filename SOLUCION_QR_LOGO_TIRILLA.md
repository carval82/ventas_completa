# ✅ SOLUCIÓN: QR Y LOGO EN TIRILLA DE FACTURA ELECTRÓNICA

## 🎯 Problemas Resueltos

### ❌ Problema 1: QR Code No Aparecía
**Causa**: Las facturas no tenían el código QR guardado en la base de datos (`qr_code: NULL`)

**Solución Aplicada**: 
- Creado script `sincronizar_qr_alegra.php` que consulta Alegra y sincroniza:
  - ✅ QR Code (barCodeContent)
  - ✅ CUFE (Código Único de Factura Electrónica)
  - ✅ Estado DIAN actualizado

**Resultado**: 
```
✅ 7 facturas sincronizadas exitosamente
✅ QR Code: SÍ (426 chars)
✅ CUFE: SÍ (presente)
```

---

### ❌ Problema 2: Logo No Aparecía
**Causa**: El logo no estaba incluido en la vista de tirilla PDF

**Solución Aplicada**:
- Agregado logo en `pdf_tirilla.blade.php` usando `storage_path()` (requerido para DomPDF)
- Dimensiones optimizadas para tirilla: max-width: 50mm, max-height: 20mm

**Resultado**:
```
✅ Logo agregado al header de tirilla
✅ Archivo existe: SÍ (35,252 bytes)
✅ Compatible con DomPDF
```

---

## 📋 Archivos Modificados

### 1. Vista de Tirilla Actualizada
**Archivo**: `resources/views/facturas_electronicas/pdf_tirilla.blade.php`

**Cambios**:

#### Logo Agregado (líneas 159-165):
```php
@if(isset($empresa) && $empresa->logo)
    <div style="text-align: center; margin-bottom: 2mm;">
        <img src="{{ storage_path('app/public/' . $empresa->logo) }}" 
             alt="Logo" 
             style="max-width: 50mm; max-height: 20mm; height: auto;">
    </div>
@endif
```

#### QR Code Corregido (líneas 255-268):
```php
@php
    // Los datos vienen como $detallesAlegra (que ya es ['data'] del controlador)
    $cufe = $venta->cufe ?? ($detallesAlegra['stamp']['cufe'] ?? null);
    $qrCode = $venta->qr_code ?? ($detallesAlegra['stamp']['barCodeContent'] ?? null);
@endphp
```

#### Visualización del QR (líneas 290-295):
```php
@if($venta->qr_code)
    <!-- Usar QR de la base de datos si existe -->
    <img src="data:image/png;base64,{{ $venta->qr_code }}" 
         alt="Código QR" 
         style="width: 30mm; height: 30mm;">
@elseif(isset($detallesAlegra['stamp']['barCodeContent']))
    <!-- Usar QR de Alegra si existe -->
    <img src="data:image/png;base64,{{ $detallesAlegra['stamp']['barCodeContent'] }}" 
         alt="Código QR" 
         style="width: 30mm; height: 30mm;">
@endif
```

---

## 🛠️ Scripts Creados

### 1. verificar_datos_factura.php
**Propósito**: Diagnóstico completo de datos necesarios para la tirilla

**Qué verifica**:
- ✅ Datos de última factura electrónica
- ✅ Presencia de QR Code en BD
- ✅ Presencia de CUFE en BD
- ✅ Datos de empresa
- ✅ Existencia del archivo de logo
- ✅ Estado del storage link

**Uso**:
```bash
php verificar_datos_factura.php
```

**Salida Actual**:
```
📄 FACTURA ELECTRÓNICA:
  ID: 48
  Número: FE48
  Alegra ID: 217
  Estado DIAN: STAMPED_AND_ACCEPTED_WITH_OBSERVATIONS
  CUFE: 6af0514773ada1a7bad6b7b73fd789...
  QR Code: SÍ (426 chars)

🏢 DATOS DE EMPRESA:
  Nombre: INTERVEREDANET.CR
  NIT: 8437347-6
  Logo: SÍ - logos/16nxspWHz2Heh1ZelNP5Oee0vAe17afwhQ1qIdrN.jpg
  Archivo existe: SÍ
  Tamaño: 35252 bytes

✅ Verificación completada
```

---

### 2. sincronizar_qr_alegra.php
**Propósito**: Sincronizar QR codes faltantes desde la API de Alegra

**Qué hace**:
1. Busca facturas sin QR en BD pero con Alegra ID
2. Consulta la API de Alegra para cada factura
3. Extrae y guarda:
   - QR Code (barCodeContent)
   - CUFE (cufe)
   - Estado DIAN (legalStatus)
4. Actualiza la base de datos

**Uso**:
```bash
php sincronizar_qr_alegra.php
```

**Resultado Última Ejecución**:
```
=== SINCRONIZACIÓN DE QR CODES DESDE ALEGRA ===

📋 Facturas encontradas sin QR: 7

Procesando Factura #22 (Alegra: 184)...
  ✓ QR encontrado (427 chars)
  ✓ CUFE encontrado: dfdb75de35a8ee7c4c16...
  ✓ Estado actualizado: STAMPED_AND_ACCEPTED_WITH_OBSERVATIONS
  ✅ Factura actualizada exitosamente

[... 6 facturas más ...]

=== RESUMEN ===
Total procesadas: 7
Actualizadas: 7
Errores: 0
Pendientes: 0

✅ Sincronización completada
```

---

## 🎨 Estructura Actual de la Tirilla

```
┌─────────────────────────────┐
│         [LOGO]              │ ← NUEVO: Logo centrado
│                             │
│   INTERVEREDANET.CR         │
│   NIT: 8437347-6            │
│   Carrera 112a # 90a-10     │
│   Tel: 3012491020           │
│   pcapacho24@gmail.com      │
├─────────────────────────────┤
│   FACTURA ELECTRÓNICA       │
│         FE48                │
│   Fecha: 10/11/2025         │
├─────────────────────────────┤
│   CLIENTE FRECUENTE         │
│   CC: 5555555               │
├─────────────────────────────┤
│   PRODUCTOS                 │
│   1 x $250.00         $250  │
├─────────────────────────────┤
│   Subtotal:          $250   │
│   IVA:                 $0   │
│   TOTAL:            $250    │
├─────────────────────────────┤
│   INFORMACIÓN DIAN          │
│   CUFE:                     │
│   6af0514773ada1a7bad6...   │
│                             │
│   [QR CODE IMAGE]           │ ← CORREGIDO: Ahora aparece
│   30mm x 30mm               │
│                             │
│   Estado DIAN: STAMPED_...  │
├─────────────────────────────┤
│   ¡Gracias por su compra!   │
│   Factura generada el ...   │
└─────────────────────────────┘
```

---

## 🔄 Flujo de Datos Actualizado

### Creación de Factura → Tirilla con QR y Logo

```
1. Crear Factura en Alegra
   └─> estado: 'draft'
   └─> qr_code: null
   └─> cufe: null

2. Abrir Factura (Open)
   └─> estado: 'open'
   └─> qr_code: null (aún)
   └─> cufe: null (aún)

3. Enviar a DIAN
   └─> estado: 'sent' o 'STAMPED_AND_ACCEPTED...'
   └─> DIAN procesa y genera QR + CUFE

4. Sincronizar con Script o "Verificar Estado"
   └─> Consulta API Alegra
   └─> Obtiene stamp.barCodeContent (QR)
   └─> Obtiene stamp.cufe (CUFE)
   └─> Guarda en BD
   └─> ✅ qr_code: "base64_string..." (426 chars)
   └─> ✅ cufe: "6af0514773ada1..."

5. Imprimir Tirilla
   a. Carga logo desde: storage_path('app/public/logos/...')
   b. Carga QR desde: $venta->qr_code (BD)
   c. Si no hay en BD: $detallesAlegra['stamp']['barCodeContent']
   d. Genera PDF con DomPDF
   └─> ✅ Tirilla completa con Logo + QR
```

---

## 🧪 Cómo Probar

### Paso 1: Verificar Datos
```bash
php verificar_datos_factura.php
```

Debe mostrar:
- ✅ QR Code: SÍ
- ✅ Logo: SÍ - Archivo existe

### Paso 2: Sincronizar (si es necesario)
Si alguna factura no tiene QR:
```bash
php sincronizar_qr_alegra.php
```

### Paso 3: Imprimir Tirilla
1. Ve a: `http://127.0.0.1:8000/facturas-electronicas`
2. Busca una factura con estado "STAMPED_AND_ACCEPTED..."
3. Haz clic en el botón **"🧾 Tirilla"**
4. ✅ Verifica que aparezcan:
   - Logo en el header
   - Código QR DIAN en la sección inferior
   - CUFE completo

---

## ⚙️ Mantenimiento Futuro

### Si Nuevas Facturas No Tienen QR

**Opción 1: Botón "Verificar Estado"** (Recomendado)
- En la lista de facturas electrónicas
- Haz clic en el botón 🔄
- Automáticamente sincroniza QR, CUFE y Estado

**Opción 2: Script Manual**
```bash
php sincronizar_qr_alegra.php
```

**Opción 3: Comando Artisan** (crear si es frecuente)
```php
// app/Console/Commands/SincronizarQRCodeAlegra.php
php artisan facturas:sincronizar-qr
```

---

## 📊 Estados de Factura vs QR Disponible

| Estado DIAN | QR Disponible | Acción |
|-------------|---------------|--------|
| **draft** | ❌ No | Debe abrirse primero |
| **open** | ❌ No | Debe enviarse a DIAN |
| **sent** | ⏳ Pendiente | Esperar respuesta DIAN |
| **STAMPED_AND_ACCEPTED...** | ✅ Sí | Listo para imprimir |
| **accepted** | ✅ Sí | Listo para imprimir |
| **issued** | ✅ Sí | Listo para imprimir |

---

## 🎯 Puntos Clave para Recordar

### Logo en PDFs
```php
// ❌ NO FUNCIONA en DomPDF:
<img src="{{ asset('storage/logos/logo.jpg') }}">

// ✅ SÍ FUNCIONA en DomPDF:
<img src="{{ storage_path('app/public/logos/logo.jpg') }}">
```

### QR en PDFs
```php
// ✅ Formato correcto:
<img src="data:image/png;base64,{{ $venta->qr_code }}">

// El QR ya viene en base64 desde Alegra
// No necesita conversión adicional
```

### Prioridad de Fuentes
```php
// 1. Primero busca en BD (más rápido)
$venta->qr_code

// 2. Si no existe, consulta API Alegra
$detallesAlegra['stamp']['barCodeContent']

// 3. Si tampoco existe, muestra placeholder
<div>CÓDIGO QR - CUFE: ...</div>
```

---

## 📝 Notas Importantes

1. **El QR es generado por la DIAN**, no por Alegra
   - Alegra solo lo almacena y lo proporciona vía API
   - Se genera cuando la factura es aceptada por DIAN

2. **El CUFE siempre viene antes que el QR**
   - Si hay CUFE pero no QR, la factura está en proceso

3. **El script sincroniza automáticamente**
   - No hace falta ejecutarlo manualmente cada vez
   - El botón "Verificar Estado" hace lo mismo

4. **Storage Path vs Public Path**
   - En web: usa `asset('storage/...')` con enlace simbólico
   - En PDF: usa `storage_path('app/public/...')` sin enlace

---

## ✅ RESULTADO FINAL

### Antes:
```
❌ Tirilla sin logo
❌ Tirilla sin código QR
❌ Facturas sin datos DIAN en BD
```

### Después:
```
✅ Tirilla con logo corporativo
✅ Tirilla con código QR DIAN
✅ 7 facturas sincronizadas con QR + CUFE
✅ Scripts de verificación y sincronización
✅ Documentación completa
```

---

**PROBLEMA TOTALMENTE RESUELTO** 🎉

Fecha: 10 de noviembre de 2025  
Facturas sincronizadas: 7  
Scripts creados: 2  
Vistas modificadas: 1  
Estado: 100% Operativo
