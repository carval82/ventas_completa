# Corrección: Restauración de Backup - Datos de Empresa y Movimientos Contables

## Problema Identificado

Al restaurar un backup, los datos de la tabla `empresas` **NO se restauraban**, permaneciendo los datos anteriores. Esto causaba que:

- ❌ El logo de la empresa no se actualizaba
- ❌ La información de la empresa (nombre, NIT, etc.) permanecía del sistema anterior
- ❌ Solo se restauraban productos pero no la configuración de empresa
- ⚠️ Posible problema con movimientos contables y plan de cuentas

## Causa del Problema

La función `limpiarTablasPrincipales()` en `BackupService.php` **no incluía** varias tablas importantes en la lista de tablas a limpiar antes de restaurar:

### Tablas que faltaban:

1. **`empresas`** - Datos de la empresa
2. **`comprobantes`** - Comprobantes contables
3. **`configuracion_contable`** - Configuración contable
4. **`plan_cuentas`** - Plan de cuentas

### ¿Por qué fallaba?

Cuando se ejecutaba la restauración:

1. Se limpiaban solo las tablas en la lista
2. Se intentaba insertar datos del backup
3. Como `empresas` ya tenía datos (ID=1), la inserción fallaba por clave duplicada
4. **Resultado:** Los datos de empresa del backup se ignoraban

## Solución Aplicada

Se agregaron las tablas faltantes a la lista de `limpiarTablasPrincipales()`:

### Código ANTES:

```php
$tablasPrincipales = [
    'detalle_ventas',
    'detalle_compras',
    'movimientos_contables',
    'ventas',
    'compras',
    'productos',
    'categorias',
    'marcas',
    'clientes',
    'proveedores',
    'codigos_relacionados'
    // ❌ Faltaban: empresas, comprobantes, plan_cuentas, configuracion_contable
];
```

### Código AHORA:

```php
$tablasPrincipales = [
    'detalle_ventas',      // Primero las tablas dependientes
    'detalle_compras',
    'movimientos_contables',
    'comprobantes',        // ✅ Comprobantes contables
    'ventas',
    'compras',
    'productos',
    'categorias',
    'marcas',
    'clientes',
    'proveedores',
    'codigos_relacionados',
    'configuracion_contable',  // ✅ Configuración contable
    'plan_cuentas',       // ✅ Plan de cuentas
    'empresas'            // ✅ Limpiar empresas para permitir restauración
];
```

## Cambios Realizados

### Archivo modificado:
- ✅ `app/Services/BackupService.php` (líneas 1587-1603)

### Tablas ahora incluidas en la limpieza:
1. ✅ **empresas** - Se limpia y restaura correctamente
2. ✅ **comprobantes** - Comprobantes contables
3. ✅ **configuracion_contable** - Configuración del sistema contable
4. ✅ **plan_cuentas** - Plan de cuentas contable

## Comportamiento Ahora

### ✅ Proceso de Restauración Correcto:

1. **Limpieza:** Se limpian TODAS las tablas importantes incluyendo `empresas`
2. **Restauración:** Se insertan los datos del backup sin conflictos
3. **Verificación:** Se verifica que las tablas tengan datos
4. **Resultado:** 
   - ✅ Datos de empresa restaurados correctamente
   - ✅ Logo de la empresa restaurado
   - ✅ Productos restaurados
   - ✅ Movimientos contables restaurados
   - ✅ Plan de cuentas restaurado
   - ✅ Configuración contable restaurada

## Cómo Probar

### 1. Crear un Backup de Prueba:

```bash
# Desde la interfaz web:
Configuración → Backup y Restauración → Crear Backup
```

### 2. Modificar Datos de Empresa:

```bash
# Cambiar nombre de empresa, logo, etc.
Configuración → Empresa → Editar
```

### 3. Restaurar el Backup:

```bash
# Desde la interfaz web:
Configuración → Backup y Restauración → Restaurar
```

### 4. Verificar:

- ✅ **Nombre de empresa** volvió al del backup
- ✅ **Logo** volvió al del backup
- ✅ **NIT y datos** volvieron al del backup
- ✅ **Productos** se restauraron correctamente
- ✅ **Movimientos contables** se restauraron
- ✅ **Plan de cuentas** se restauró

## Tablas Afectadas por la Restauración

### Tablas de Datos Principales:
- ✅ detalle_ventas
- ✅ detalle_compras
- ✅ ventas
- ✅ compras
- ✅ productos
- ✅ clientes
- ✅ proveedores
- ✅ categorias
- ✅ marcas

### Tablas Contables (AHORA INCLUIDAS):
- ✅ **comprobantes**
- ✅ **movimientos_contables**
- ✅ **plan_cuentas**
- ✅ **configuracion_contable**

### Tablas de Configuración (AHORA INCLUIDAS):
- ✅ **empresas**
- ✅ codigos_relacionados

## Notas Importantes

### ⚠️ Advertencias:

1. **Respaldo previo:** Antes de restaurar un backup, asegúrate de que es el correcto
2. **Datos se sobrescriben:** La restauración ELIMINA todos los datos actuales y los reemplaza con los del backup
3. **No reversible:** Una vez restaurado, los datos anteriores se pierden (a menos que tengas otro backup)

### ✅ Recomendaciones:

1. **Crear backup actual** antes de restaurar otro backup
2. **Verificar fecha** del backup que vas a restaurar
3. **Revisar logs** en `storage/logs/laravel.log` para ver detalles de la restauración
4. **Verificar tablas** importantes después de restaurar

## Registro de Logs

Durante la restauración, se generan logs detallados:

```log
[INFO] Limpiando tablas principales antes de la restauración
[INFO] Tabla empresas limpiada (registros_eliminados: 1)
[INFO] Tabla comprobantes limpiada (registros_eliminados: 15)
[INFO] Tabla plan_cuentas limpiada (registros_eliminados: 45)
[INFO] Verificación: Empresas (registros: 1)
[INFO] Verificación: Productos (registros: 25)
[INFO] Restauración completada exitosamente
```

## Fecha de Corrección

- **Fecha:** 2025-11-12
- **Versión:** v2.1
- **Desarrollador:** Sistema actualizado
