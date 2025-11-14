# 🔧 SOLUCIÓN: Error 404 al Imprimir Facturas

## ❌ Problema:
Al hacer clic en "Imprimir" aparece **Error 404** y debes usar el botón "Atrás" del navegador.

---

## ✅ SOLUCIONES (Ejecutar en orden)

### 1️⃣ **SOLUCIÓN RÁPIDA - Limpiar Cachés**

```bash
cd /ruta/de/tu/proyecto
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

Luego:
```bash
php artisan route:cache
php artisan config:cache
```

---

### 2️⃣ **Verificar archivo .htaccess**

**Ubicación:** `/public/.htaccess`

**Debe contener:**

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

**Si NO existe, créalo con el contenido de arriba.**

---

### 3️⃣ **Verificar mod_rewrite (Apache)**

En tu servidor, verifica que mod_rewrite esté habilitado:

```bash
# En Linux:
sudo a2enmod rewrite
sudo systemctl restart apache2

# En XAMPP (Windows):
# Abrir: C:\xampp\apache\conf\httpd.conf
# Buscar: LoadModule rewrite_module modules/mod_rewrite.so
# Quitar el # al inicio si lo tiene
# Reiniciar Apache desde XAMPP Control Panel
```

---

### 4️⃣ **Verificar permisos (Linux/Ubuntu)**

```bash
sudo chown -R www-data:www-data /ruta/proyecto
sudo chmod -R 755 /ruta/proyecto
sudo chmod -R 775 /ruta/proyecto/storage
sudo chmod -R 775 /ruta/proyecto/bootstrap/cache
```

---

### 5️⃣ **Script Automático de Solución**

Ejecuta este script en el servidor:

```bash
php solucionar_404_impresion.php
```

---

## 🧪 PROBAR LA SOLUCIÓN:

1. **Abrir navegador en modo incógnito**
   - Chrome: `Ctrl + Shift + N`
   - Firefox: `Ctrl + Shift + P`

2. **Ir a tu sistema:**
   ```
   http://tu-dominio.com/ventas
   ```

3. **Hacer clic en el botón de Imprimir** (icono de impresora 🖨️)

4. **Debería abrir la factura sin error 404**

---

## 🔍 SI AÚN NO FUNCIONA:

### Verificar URL que genera error:

Cuando aparezca el error 404, copia la URL de la barra del navegador.

**¿La URL se ve así?**
```
✅ CORRECTO:
http://tu-dominio.com/ventas/123/print

❌ INCORRECTO (falta public):
http://tu-dominio.com/public/ventas/123/print
```

---

### Si la URL incluye "/public/":

Tu DocumentRoot está mal configurado en Apache.

**Solución:**

Editar la configuración de Apache:

```apache
# En /etc/apache2/sites-available/tu-sitio.conf
# o en XAMPP: httpd-vhosts.conf

<VirtualHost *:80>
    ServerName tu-dominio.com
    DocumentRoot /ruta/proyecto/public   # ← Debe apuntar a /public
    
    <Directory /ruta/proyecto/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Luego reiniciar Apache:
```bash
sudo systemctl restart apache2  # Linux
# o reiniciar desde XAMPP Control Panel (Windows)
```

---

## 📋 CHECKLIST DE VERIFICACIÓN:

- [ ] Ejecuté `php artisan optimize:clear`
- [ ] Ejecuté `php artisan route:cache`
- [ ] Verifiqué que existe `/public/.htaccess`
- [ ] Verifiqué que mod_rewrite está habilitado
- [ ] Probé en navegador incógnito
- [ ] La URL NO incluye "/public/"
- [ ] Los permisos están correctos (Linux)

---

## 🆘 AYUDA ADICIONAL:

Si nada de esto funciona, ejecuta:

```bash
php diagnosticar_impresion.php
```

Y envía el resultado completo para análisis.

---

## 💡 NOTA IMPORTANTE:

**SIEMPRE** usar modo incógnito después de hacer cambios para evitar cachés del navegador.

```
Ctrl + Shift + N  (Chrome)
Ctrl + Shift + P  (Firefox)
```
