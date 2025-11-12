# SISTEMA DE VENTAS COMPLETO - VERSIÓN CLIENTE

## CARACTERÍSTICAS PRINCIPALES:
✅ Sistema completo de ventas y facturación
✅ Facturación electrónica integrada con Alegra
✅ Gestión de inventario y productos
✅ Control de clientes y proveedores
✅ Reportes y estadísticas
✅ Sistema de backup y restauración
✅ Multi-usuario con roles y permisos

## INSTALACIÓN:
1. Copiar toda la carpeta al servidor web
2. Configurar base de datos en archivo .env
3. Ejecutar: php artisan migrate:fresh --seed
4. **IMPORTANTE:** Ejecutar crear_enlace_storage.bat (o php artisan storage:link)
5. Configurar credenciales de Alegra en la aplicación
6. Probar conexión y sincronizar datos

## CREDENCIALES INICIALES:
Usuario: admin@admin.com
Contraseña: password

## SOPORTE TÉCNICO:
Para soporte técnico y configuración personalizada,
contactar al desarrollador.

## VERSIÓN:
Sistema de Ventas Completo v2.0
Fecha: 2025-09-24 13:55:39
Incluye facturación electrónica completa

## SOLUCIÓN PROBLEMA DEL LOGO:

Si el logo de la empresa NO se visualiza:

### Opción 1 (Más Rápida):
1. Ejecutar el archivo: `crear_enlace_storage.bat`
2. Refrescar el navegador

### Opción 2 (Línea de Comandos):
```bash
php artisan storage:link
```

### Verificar que funciona:
- Ir a: http://localhost/ventas_completa/public/verificar_logo.php
- Debe mostrar ✓ en todas las verificaciones

### ¿Por qué pasa esto?
El logo se guarda en `storage/app/public/logos/` pero necesita ser accesible desde `public/storage/`. El comando `storage:link` crea este enlace automáticamente.

**NOTA:** Este comando solo se ejecuta UNA vez después de clonar desde Git.

Para más detalles, ver el archivo: `SOLUCION_LOGO.md`
