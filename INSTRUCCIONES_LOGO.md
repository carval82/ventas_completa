# 📸 Cómo Configurar el Logo de la Empresa

El logo aparecerá en todas las facturas, cotizaciones, remisiones y documentos PDF del sistema.

## 📋 Pasos para Configurar el Logo

### Opción 1: Usando el Script Automático (Recomendado)

1. **Prepara tu logo**:
   - Formato: PNG, JPG, JPEG, GIF o SVG
   - Tamaño recomendado: 200x80 pixeles (o proporcional)
   - Fondo transparente (PNG recomendado)

2. **Sube el logo**:
   - Copia tu archivo de logo a: `storage/app/public/`
   - Ejemplo: `storage/app/public/logo_empresa.png`

3. **Ejecuta el script**:
   ```bash
   php configurar_logo_empresa.php logo_empresa.png
   ```

4. **¡Listo!** El logo ya está configurado.

---

### Opción 2: Configuración Manual (Base de Datos)

1. **Sube el logo** a `storage/app/public/logo_empresa.png`

2. **Ejecuta en MySQL**:
   ```sql
   UPDATE empresas SET logo = 'logo_empresa.png' WHERE id = 1;
   ```

3. **Verifica el enlace simbólico**:
   ```bash
   php artisan storage:link
   ```

---

### Opción 3: Desde la Interfaz Web (Próximamente)

> Nota: Esta funcionalidad se puede implementar en la página de configuración de empresa.

---

## 🔍 Verificar que el Logo está Configurado

Ejecuta el script de verificación:
```bash
php verificar_logo.php
```

Deberías ver algo como:
```
Empresa encontrada: INTERVEREDANET.CR
Logo en BD: logo_empresa.png
Ruta completa: C:\xampp\htdocs\laravel\ventas_completa\storage\app\public\logo_empresa.png
Archivo existe: SI
Tamaño: 45678 bytes
```

---

## 📁 Estructura de Archivos

```
ventas_completa/
├── storage/
│   ├── app/
│   │   └── public/
│   │       └── logo_empresa.png  ← Aquí va tu logo
│   └── ...
├── public/
│   └── storage/  ← Enlace simbólico creado por artisan
└── ...
```

---

## ✅ Dónde Aparecerá el Logo

Una vez configurado, el logo aparecerá automáticamente en:

- ✓ Facturas electrónicas PDF
- ✓ Facturas de venta (ticket 80mm)
- ✓ Facturas de venta (media carta)
- ✓ Cotizaciones PDF
- ✓ Remisiones PDF
- ✓ Reportes contables
- ✓ Todos los documentos impresos

---

## 🎨 Recomendaciones de Diseño

- **Tamaño**: 200x80 pixeles (ancho x alto)
- **Formato**: PNG con fondo transparente
- **Peso**: Máximo 500 KB
- **Colores**: Preferiblemente en alta resolución
- **Aspecto**: Horizontal (landscape) funciona mejor

---

## ❓ Solución de Problemas

### El logo no aparece en los PDFs

1. Verifica que el archivo existe:
   ```bash
   php verificar_logo.php
   ```

2. Verifica los permisos de la carpeta:
   ```bash
   chmod -R 775 storage/app/public/
   ```

3. Recrea el enlace simbólico:
   ```bash
   php artisan storage:link
   ```

### El logo se ve muy grande o muy pequeño

Edita el tamaño en las vistas CSS:
- Facturas PDF: max-width: 200px (modificar según necesites)
- Tickets: max-width: 60mm

---

## 📞 Soporte

Para más ayuda, contacta al desarrollador o revisa la documentación del sistema.
