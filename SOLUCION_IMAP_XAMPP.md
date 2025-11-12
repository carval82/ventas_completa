# 🔧 SOLUCIÓN: Habilitar IMAP en XAMPP para Módulo DIAN

## 🎯 PROBLEMA IDENTIFICADO

El módulo DIAN funciona perfectamente desde **línea de comandos (CLI)** pero falla desde el **navegador web** con el error:
```
Call to undefined function App\Http\Controllers\imap_open()
```

Esto ocurre porque **XAMPP puede tener diferentes configuraciones de PHP** para CLI y Apache.

## ✅ SOLUCIÓN PASO A PASO

### 📍 **PASO 1: Verificar Configuraciones de PHP**

1. **Verificar PHP CLI:**
   ```bash
   php -m | findstr imap
   ```
   ✅ Debería mostrar: `imap`

2. **Verificar PHP Web:**
   - Ve a: `http://127.0.0.1:8000/verificar_imap_web.php`
   - ❌ Probablemente muestre: "Extensión IMAP NO está cargada"

### 📍 **PASO 2: Localizar el php.ini Correcto**

XAMPP puede usar **diferentes archivos php.ini**:

1. **Para CLI:** `C:\xampp\php\php.ini`
2. **Para Apache:** Puede ser el mismo o diferente

**Verificar cuál usa Apache:**
- Ve a: `http://localhost/dashboard/phpinfo.php`
- Busca: "**Loaded Configuration File**"
- Anota la ruta exacta

### 📍 **PASO 3: Habilitar IMAP en el php.ini de Apache**

1. **Abrir el archivo php.ini correcto** (el que usa Apache)
2. **Buscar la línea:**
   ```ini
   ;extension=imap
   ```
3. **Cambiar a:**
   ```ini
   extension=imap
   ```
4. **Guardar el archivo**

### 📍 **PASO 4: Reiniciar Apache**

1. **Abrir XAMPP Control Panel**
2. **Detener Apache:** Click en "Stop"
3. **Iniciar Apache:** Click en "Start"
4. **Verificar que no hay errores**

### 📍 **PASO 5: Verificar que Funciona**

1. **Verificar desde web:**
   ```
   http://127.0.0.1:8000/verificar_imap_web.php
   ```
   ✅ Debería mostrar: "Extensión IMAP está cargada"

2. **Probar módulo DIAN:**
   ```
   http://127.0.0.1:8000/dian/configuracion
   ```
   - Click en "Probar Conexión"
   - ✅ Debería conectar exitosamente

## 🔍 DIAGNÓSTICO AVANZADO

Si aún no funciona, verifica:

### **Verificar Múltiples php.ini:**
```bash
# Buscar todos los php.ini
dir C:\xampp\php.ini /s
dir C:\xampp\apache\bin\php.ini /s
```

### **Verificar Logs de Apache:**
- `C:\xampp\apache\logs\error.log`
- Buscar errores relacionados con extensiones

### **Verificar DLLs de IMAP:**
- `C:\xampp\php\ext\php_imap.dll` debe existir
- Si no existe, reinstalar XAMPP o descargar la DLL

## 🎊 RESULTADO ESPERADO

Después de seguir estos pasos:

✅ **IMAP habilitado en web y CLI**
✅ **Módulo DIAN funcionando desde navegador**
✅ **Conexión exitosa a Gmail**
✅ **Procesamiento automático de facturas**

## 🚀 PRUEBA FINAL

1. **Ve a:** `http://127.0.0.1:8000/dian/configuracion`
2. **Datos de prueba:**
   - Email: `pcapacho24@gmail.com`
   - Contraseña: `adkq prqh vhii njnz`
   - Servidor: `imap.gmail.com`
   - Puerto: `993`
   - SSL: ✅ Activado
3. **Click "Probar Conexión"**
4. **Resultado esperado:** 🎉 "¡Conexión Exitosa!"

## 📞 SI AÚN NO FUNCIONA

**Alternativas:**

1. **Reinstalar XAMPP** con versión más reciente
2. **Usar WAMP/LAMP** que incluye IMAP por defecto
3. **Configurar PHP manualmente** con extensiones completas
4. **Usar Docker** con imagen PHP que incluya IMAP

## 📊 MONITOREO

Una vez funcionando, monitorea con:
```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log | grep DIAN

# Solo errores
tail -f storage/logs/laravel.log | grep "local.ERROR.*DIAN"
```

---

**🎯 El módulo DIAN está completamente implementado y solo necesita IMAP habilitado para funcionar al 100%**
