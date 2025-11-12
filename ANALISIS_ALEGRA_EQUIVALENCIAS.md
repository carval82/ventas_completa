# 🔍 ANÁLISIS: ALEGRA + SISTEMA DE EQUIVALENCIAS

## ⚠️ PROBLEMA IDENTIFICADO

### **Situación Actual:**
El sistema de equivalencias permite vender productos en diferentes unidades (ej: arroz por paca, libra, kilo), pero la integración con Alegra **NO está considerando las unidades de medida** al crear facturas electrónicas.

### **Código Problemático:**
```php
// En app/Models/Venta.php línea 72
$itemData = [
    'id' => intval($detalle->producto->id_alegra),
    'price' => floatval($detalle->precio_unitario),
    'quantity' => floatval($detalle->cantidad) // ❌ PROBLEMA AQUÍ
];
```

## 🚨 ESCENARIOS PROBLEMÁTICOS

### **Escenario 1: Producto con Equivalencias**
```
🏪 En el sistema local:
- Producto: Arroz por Paca (ID: 43)
- Venta: 2 pacas × $50,000 = $100,000
- Unidad: "paca"

📄 En Alegra:
- Producto: Arroz Premium (ID: 123) 
- Cantidad: 2
- Unidad: ??? (no se especifica)
```

### **Escenario 2: Conversión de Unidades**
```
🏪 En el sistema local:
- Cliente compra: 50 libras de arroz
- Sistema convierte: 50 lb → 2 pacas
- Venta registrada: 2 pacas × $50,000

📄 En Alegra:
- Se envía: 2 unidades
- Pero Alegra no sabe que son "pacas"
- Podría interpretarse como 2 libras ❌
```

## 🔧 SOLUCIONES PROPUESTAS

### **Opción 1: Enviar Unidad Base a Alegra (Recomendado)**
```php
// Siempre enviar en la unidad base del producto
$itemData = [
    'id' => intval($detalle->producto->id_alegra),
    'price' => $this->calcularPrecioUnidadBase($detalle),
    'quantity' => $this->convertirACantidadBase($detalle),
    'description' => $detalle->producto->nombre . ' (' . $detalle->unidad_medida . ')'
];
```

### **Opción 2: Sincronizar Unidades con Alegra**
```php
// Crear productos equivalentes en Alegra
- Arroz por Paca (ID: 123)
- Arroz por Libra (ID: 124) 
- Arroz por Kilo (ID: 125)

// Enviar el ID correcto según la unidad vendida
$productoAlegra = $this->obtenerProductoAlegraPorUnidad($detalle);
$itemData = [
    'id' => $productoAlegra->id_alegra,
    'price' => floatval($detalle->precio_unitario),
    'quantity' => floatval($detalle->cantidad)
];
```

### **Opción 3: Descripción Detallada**
```php
// Agregar unidad en la descripción
$itemData = [
    'id' => intval($detalle->producto->id_alegra),
    'price' => floatval($detalle->precio_unitario),
    'quantity' => floatval($detalle->cantidad),
    'description' => $detalle->producto->nombre . ' - ' . $detalle->cantidad . ' ' . $detalle->unidad_medida
];
```

## 📋 INVESTIGACIÓN API ALEGRA

### **Campos Disponibles para Items:**
```json
{
  "id": 123,
  "name": "Nombre del producto",
  "description": "Descripción detallada",
  "price": 50000,
  "quantity": 2,
  "unit": "paca", // ❓ ¿Soporta unidades personalizadas?
  "tax": {...}
}
```

### **Preguntas Clave:**
1. ¿Alegra soporta el campo `unit` en los items?
2. ¿Qué unidades acepta Alegra? (kg, lb, pza, etc.)
3. ¿Cómo maneja Alegra productos con múltiples presentaciones?
4. ¿La DIAN requiere unidades específicas?

## 🧪 PLAN DE PRUEBAS

### **Prueba 1: Verificar Campos Soportados**
```php
// Enviar factura de prueba con campo 'unit'
$itemData = [
    'id' => 123,
    'price' => 50000,
    'quantity' => 2,
    'unit' => 'paca', // Probar si acepta este campo
    'description' => 'Arroz Premium - 2 pacas'
];
```

### **Prueba 2: Diferentes Unidades**
```php
// Probar unidades estándar
$unidadesPrueba = ['kg', 'lb', 'pza', 'und', 'paca', 'bulto'];
foreach ($unidadesPrueba as $unidad) {
    // Crear factura de prueba con cada unidad
}
```

### **Prueba 3: Respuesta de Error**
```php
// Verificar qué errores devuelve Alegra si enviamos datos incorrectos
try {
    $response = $alegraService->createInvoice($facturaConUnidadPersonalizada);
} catch (Exception $e) {
    Log::info('Error Alegra con unidad personalizada: ' . $e->getMessage());
}
```

## 💡 RECOMENDACIÓN INMEDIATA

### **Implementación Segura (Opción 1):**

```php
public function prepararFacturaAlegra()
{
    // ... código existente ...
    
    foreach ($this->detalles as $detalle) {
        // Convertir siempre a unidad base para Alegra
        $cantidadBase = $this->convertirACantidadBase($detalle);
        $precioBase = $this->calcularPrecioUnidadBase($detalle);
        
        $itemData = [
            'id' => intval($detalle->producto->id_alegra),
            'price' => $precioBase,
            'quantity' => $cantidadBase,
            'description' => $detalle->producto->nombre . 
                           ' (' . $detalle->cantidad . ' ' . $detalle->unidad_medida . ')'
        ];
        
        // Log para auditoría
        Log::info('Conversión para Alegra', [
            'original' => $detalle->cantidad . ' ' . $detalle->unidad_medida,
            'convertido' => $cantidadBase . ' unidades base',
            'precio_original' => $detalle->precio_unitario,
            'precio_base' => $precioBase
        ]);
        
        $items[] = $itemData;
    }
}

private function convertirACantidadBase($detalle)
{
    // Si el producto tiene equivalencias, convertir a unidad base
    if ($detalle->producto->es_producto_base === false && $detalle->producto->producto_base_id) {
        return $detalle->cantidad * $detalle->producto->factor_stock;
    }
    
    return $detalle->cantidad;
}

private function calcularPrecioUnidadBase($detalle)
{
    // Si el producto tiene equivalencias, ajustar precio
    if ($detalle->producto->es_producto_base === false && $detalle->producto->producto_base_id) {
        return $detalle->precio_unitario / $detalle->producto->factor_stock;
    }
    
    return $detalle->precio_unitario;
}
```

## 🎯 PRÓXIMOS PASOS

1. **Investigar API Alegra** - Verificar campos soportados
2. **Implementar conversión** a unidad base
3. **Crear pruebas** con diferentes escenarios
4. **Documentar** el comportamiento final
5. **Validar** con facturas reales

## ⚠️ RIESGOS IDENTIFICADOS

### **Riesgo Alto:**
- **Facturas incorrectas** en Alegra por unidades mal interpretadas
- **Problemas con DIAN** si las cantidades no coinciden
- **Confusión contable** entre sistema local y Alegra

### **Riesgo Medio:**
- **Descripciones confusas** en facturas electrónicas
- **Dificultad para conciliar** inventarios
- **Reportes inconsistentes** entre sistemas

### **Mitigación:**
- Implementar conversión automática a unidad base
- Agregar logs detallados de todas las conversiones
- Crear pruebas exhaustivas antes de producción
- Documentar claramente el comportamiento

## 📊 IMPACTO EN FACTURACIÓN ELECTRÓNICA

### **DIAN Requiere:**
- Cantidad exacta del producto
- Unidad de medida estándar
- Precio unitario correcto
- Descripción clara

### **Nuestro Sistema Debe:**
- Convertir cantidades a unidad DIAN
- Mantener trazabilidad de conversiones
- Generar descripciones claras
- Validar antes de enviar a Alegra

¡Es crítico resolver esto antes de usar el sistema de equivalencias en producción! 🚨
