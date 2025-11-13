# 📦 INSTRUCCIONES DE DESPLIEGUE - Cliente

## 🚀 Después de hacer `git pull`

Ejecuta estos comandos **EN ORDEN** en el servidor del cliente:

### 1️⃣ Bajar cambios de GitHub
```bash
cd /ruta/del/proyecto
git pull origin main
```

### 2️⃣ Ejecutar migraciones (crea campos nuevos en BD)
```bash
php artisan migrate
```
**IMPORTANTE:** Esto crea el campo `usar_formato_electronico` en la tabla `empresas`

### 3️⃣ Limpiar cachés de Laravel
```bash
php artisan optimize:clear
```

### 4️⃣ Regenerar autoload
```bash
composer dump-autoload
```

### 5️⃣ Activar formato electrónico automáticamente
```bash
php activar_formato_cliente.php
```

Este script:
- ✅ Verifica que la migración se ejecutó correctamente
- ✅ Activa el formato electrónico para todas las facturas
- ✅ Limpia cachés automáticamente
- ✅ Muestra la configuración actual
- ✅ Verifica que los archivos existan

---

## 🌐 Probar en el Navegador

### ⚠️ MUY IMPORTANTE: Abrir en modo INCÓGNITO

**Por qué:** El navegador guarda caché de los estilos y vistas antiguas.

#### En Chrome/Edge:
```
Ctrl + Shift + N
```

#### En Firefox:
```
Ctrl + Shift + P
```

### Luego:
1. Ve a: `http://tu-dominio.com/ventas`
2. Click en **"Ver"** cualquier venta
3. Click en **"Imprimir"**
4. ✅ Verás el nuevo diseño profesional

---

## ❌ Si No Funciona

### Problema 1: "Campo usar_formato_electronico no existe"
**Solución:**
```bash
php artisan migrate
```

### Problema 2: "Sigue mostrando el diseño antiguo"
**Solución:**
```bash
php artisan optimize:clear
php artisan view:clear
php artisan cache:clear
php artisan config:clear
```

Luego abre en **modo incógnito** (Ctrl + Shift + N)

### Problema 3: "Error 500 al imprimir"
**Solución:**
```bash
# Ver logs
tail -f storage/logs/laravel.log

# Dar permisos
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Problema 4: "No aparecen productos en la factura"
**Causa:** Esa venta no tiene detalles en la BD.
**Solución:** Crear una nueva venta de prueba.

---

## 🔄 Desactivar Formato Electrónico

Si quieres volver al formato simple:

```bash
php artisan tinker
```

Dentro de tinker:
```php
DB::table('empresas')->update(['usar_formato_electronico' => false]);
exit
```

O desde **Configuración → Empresa** → Desmarcar el checkbox.

---

## 📋 Verificar Archivos Clave

```bash
# Verificar que la vista existe
ls -lh resources/views/ventas/print_factura_electronica.blade.php

# Verificar migración
ls -lh database/migrations/*usar_formato_electronico*

# Verificar controlador
grep -n "print_factura_electronica" app/Http/Controllers/VentaController.php
```

---

## ✅ Checklist Post-Deploy

- [ ] `git pull` ejecutado
- [ ] `php artisan migrate` ejecutado
- [ ] `php artisan optimize:clear` ejecutado
- [ ] `php activar_formato_cliente.php` ejecutado
- [ ] Navegador abierto en **modo incógnito**
- [ ] Factura de prueba impresa correctamente
- [ ] QR code visible
- [ ] Productos mostrándose
- [ ] Totales calculados correctamente

---

## 📞 Soporte

Si algo falla:
1. Revisa `storage/logs/laravel.log`
2. Ejecuta el script de diagnóstico: `php activar_formato_cliente.php`
3. Toma captura de pantalla del error

---

## 🎯 Archivos Nuevos en Este Deploy

- `resources/views/ventas/print_factura_electronica.blade.php` - Nueva vista
- `database/migrations/*_add_usar_formato_electronico_to_empresas_table.php` - Migración
- `activar_formato_cliente.php` - Script de configuración
- `DESPLIEGUE_CLIENTE.md` - Este archivo

---

**Fecha de última actualización:** 13 Nov 2025
**Versión:** 2.0 - Factura Electrónica Profesional
