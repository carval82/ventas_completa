# Corrección: Refresco de Vista de Ventas Después de Imprimir

## Problema Identificado

En la vista `create_iva.blade.php` (ventas con IVA), después de guardar una venta e imprimir la factura desde el modal de confirmación, **la página NO se refrescaba automáticamente**, quedándose con los datos de la venta anterior en el formulario.

Esto causaba que:
- ❌ Los productos quedaban seleccionados
- ❌ El total anterior seguía mostrándose
- ❌ El usuario tenía que refrescar manualmente (F5)
- ❌ Riesgo de duplicar ventas si no se daba cuenta

## Causa

En `create_iva.blade.php` había comentarios que indicaban **"No redirigir automáticamente"** después de imprimir, dejando la página sin refrescar.

**Código problemático:**
```javascript
if (!printWindow) {
    alert('Por favor, permita las ventanas emergentes para imprimir');
    window.location.href = response.redirect_url || '/ventas/create';
} else {
    // No redirigir automáticamente, permitir que el usuario vea la impresión
    // y luego decida manualmente volver a la página de ventas
}
```

## Solución Aplicada

Se modificó `create_iva.blade.php` para que **siempre refresque** después de imprimir, igual que en `create.blade.php`:

**Código corregido:**
```javascript
if (!printWindow) {
    console.error('Bloqueador de popups detectado');
    alert('Por favor, permita las ventanas emergentes para imprimir');
}

// Refrescar el formulario para crear otra venta
setTimeout(() => {
    window.location.href = '/ventas/create-iva';
}, 500);
```

## Cambios Realizados

### Archivo modificado:
- ✅ `resources/views/ventas/create_iva.blade.php`

### Lugares corregidos:
1. **Líneas 1196-1215:** Refresco después de confirmar impresión (primera ocurrencia)
2. **Líneas 1529-1548:** Refresco después de confirmar impresión (segunda ocurrencia)

## Comportamiento Ahora

### ✅ Flujo Correcto:

1. Usuario completa una venta
2. Hace clic en "Guardar Venta" (F12)
3. Aparece modal de confirmación
4. **Opción A - "Imprimir Factura":**
   - Se abre ventana de impresión
   - Después de 500ms → **Refresca automáticamente**
   - Formulario queda limpio para nueva venta

5. **Opción B - "Nueva Venta":**
   - **Refresca inmediatamente**
   - Formulario queda limpio para nueva venta

### Beneficios:

- ✅ Formulario siempre limpio después de cada venta
- ✅ No hay riesgo de duplicar datos
- ✅ Experiencia consistente entre `create.blade.php` y `create_iva.blade.php`
- ✅ No requiere F5 manual del usuario

## Verificación

Para verificar que funciona correctamente:

1. Ir a: **Ventas → Nueva Venta (Con IVA)**
2. Agregar productos y completar venta
3. Guardar con **F12**
4. En el modal, hacer clic en **"Imprimir Factura"**
5. Verificar que:
   - ✅ Se abre la ventana de impresión
   - ✅ Después de 500ms la página se refresca
   - ✅ El formulario queda vacío
   - ✅ Listo para nueva venta

## Notas Técnicas

- El `setTimeout(500)` da tiempo para que se abra la ventana de impresión antes de refrescar
- Usa `/ventas/create-iva` para asegurar que vuelve a la vista correcta
- Funciona tanto si imprime como si no imprime
- Compatible con bloqueadores de popups

## Fecha de Corrección

- **Fecha:** 2025-11-12
- **Versión:** v2.1
- **Desarrollador:** Sistema actualizado
