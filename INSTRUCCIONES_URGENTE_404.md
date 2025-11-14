# 🚨 SOLUCIÓN URGENTE: Error 404 al Imprimir

## ❌ PROBLEMA:
Al hacer clic en "Imprimir" aparece **Error 404** y debes usar el botón "Atrás" del navegador.

---

## ✅ SOLUCIÓN EN 3 PASOS (5 MINUTOS)

### 📥 **PASO 1: Descargar los cambios**

```bash
cd /ruta/de/tu/proyecto
git pull origin main
```

---

### 🔧 **PASO 2: Ejecutar script automático**

```bash
php solucionar_404_impresion.php
```

**Este script:**
- ✅ Limpia todas las cachés
- ✅ Verifica el archivo .htaccess
- ✅ Revisa permisos
- ✅ Regenera rutas
- ✅ Te dice exactamente qué hacer

---

### 🧪 **PASO 3: Probar**

1. **Abrir navegador en modo INCÓGNITO**
   ```
   Ctrl + Shift + N  (Chrome)
   Ctrl + Shift + P  (Firefox)
   ```

2. **Ir a tu sistema:**
   ```
   http://tu-dominio.com/ventas
   ```

3. **Hacer clic en Imprimir** 🖨️

4. **¡Debería funcionar!** 🎉

---

## 🔍 SI AÚN NO FUNCIONA:

### Ejecuta el diagnóstico:

```bash
php diagnosticar_impresion.php
```

Este script te dirá exactamente qué está mal.

---

## 📋 CAUSAS COMUNES Y SOLUCIONES:

### 1️⃣ **Cache corrupto**
```bash
php artisan optimize:clear
php artisan route:cache
```

### 2️⃣ **Archivo .htaccess faltante**
El script `solucionar_404_impresion.php` lo crea automáticamente.

### 3️⃣ **mod_rewrite deshabilitado**

**En Linux/Ubuntu:**
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

**En XAMPP (Windows):**
1. Abrir: `C:\xampp\apache\conf\httpd.conf`
2. Buscar: `LoadModule rewrite_module modules/mod_rewrite.so`
3. Quitar el `#` al inicio si lo tiene
4. Guardar y reiniciar Apache desde XAMPP Control Panel

### 4️⃣ **Permisos incorrectos (Linux)**
```bash
sudo chown -R www-data:www-data /ruta/proyecto
sudo chmod -R 755 /ruta/proyecto
sudo chmod -R 775 /ruta/proyecto/storage
sudo chmod -R 775 /ruta/proyecto/bootstrap/cache
```

---

## 🆘 AYUDA RÁPIDA:

| Síntoma | Solución |
|---------|----------|
| Error 404 al imprimir | `php solucionar_404_impresion.php` |
| URL incluye "/public/" | Configurar DocumentRoot a `/public` |
| Cache no se limpia | Reiniciar Apache |
| Permisos denegados | Ejecutar comandos chmod (Linux) |

---

## 📞 SOPORTE:

Si nada funciona:

1. Ejecutar:
   ```bash
   php diagnosticar_impresion.php > reporte.txt
   ```

2. Enviar el archivo `reporte.txt` para análisis detallado

---

## 💡 RECUERDA:

✅ **SIEMPRE** probar en modo incógnito después de hacer cambios

✅ **SIEMPRE** ejecutar `php artisan optimize:clear` después de actualizar código

✅ **SIEMPRE** verificar que estés en la URL correcta (sin "/public/")

---

## ✨ RESULTADO ESPERADO:

Al hacer clic en "Imprimir", debería:
1. Abrir una nueva pestaña
2. Mostrar la factura formateada
3. Abrir el diálogo de impresión automáticamente
4. ✅ **SIN ERROR 404**

---

## 📚 MÁS INFORMACIÓN:

Lee el archivo `SOLUCIONAR_ERROR_404.md` para detalles completos.
