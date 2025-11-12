# Instrucciones para Distribuir el Sistema de Ventas

Este documento explica cómo preparar y distribuir el sistema de ventas a un cliente que ya tiene Git, XAMPP y Node.js instalados.

## Preparación del paquete

1. **Crear un archivo ZIP** con los siguientes archivos y carpetas:
   - `app/` - Contiene la lógica de la aplicación
   - `bootstrap/` - Archivos de arranque de Laravel
   - `config/` - Configuraciones de la aplicación
   - `database/` - Migraciones y seeders
   - `lang/` - Archivos de idioma
   - `public/` - Archivos públicos accesibles desde el navegador
   - `resources/` - Vistas, CSS, JS y otros recursos
   - `routes/` - Definiciones de rutas
   - `storage/` - Almacenamiento de archivos y logs
   - `.env.example` - Plantilla de configuración
   - `artisan` - CLI de Laravel
   - `composer.json` y `composer.lock` - Dependencias de PHP
   - `package.json` y `package-lock.json` - Dependencias de JavaScript
   - `vite.config.js` - Configuración de Vite
   - `README.md` - Información general del sistema
   - `INSTALACION.md` - Guía de instalación

2. **No incluir** los siguientes archivos y carpetas:
   - `vendor/` - Dependencias de PHP instaladas
   - `node_modules/` - Dependencias de JavaScript instaladas
   - `.git/` - Repositorio Git
   - `.env` - Archivo de configuración local
   - Cualquier archivo de log o caché

## Instrucciones para el cliente

Proporciona al cliente el archivo ZIP junto con las siguientes instrucciones:

### Requisitos previos
- XAMPP (con PHP 8.1 o superior)
- Git
- Node.js y npm
- Composer

### Pasos de instalación

1. **Descomprimir el archivo ZIP** en la carpeta `htdocs` de XAMPP (por ejemplo, `C:\xampp\htdocs\ventas_sistema`)

2. **Abrir una terminal** (PowerShell o CMD en Windows, Terminal en macOS/Linux) y navegar a la carpeta del proyecto:
   ```
   cd C:\xampp\htdocs\ventas_sistema
   ```

3. **Instalar dependencias de PHP**:
   ```
   composer install
   ```

4. **Instalar dependencias de JavaScript**:
   ```
   npm install
   npm run build
   ```

5. **Configurar el entorno**:
   - Copiar `.env.example` a `.env`:
     ```
     copy .env.example .env  # En Windows
     cp .env.example .env    # En macOS/Linux
     ```
   - Generar clave de aplicación:
     ```
     php artisan key:generate
     ```
   - Editar el archivo `.env` con la configuración de la base de datos:
     ```
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=ventas_completa
     DB_USERNAME=root
     DB_PASSWORD=
     ```

6. **Crear la base de datos**:
   - Abrir phpMyAdmin (http://localhost/phpmyadmin)
   - Crear una nueva base de datos llamada `ventas_completa`

7. **Ejecutar migraciones y seeders**:
   ```
   php artisan migrate:fresh --seed
   ```

8. **Configurar permisos** (solo en Linux/Mac):
   ```
   chmod -R 775 storage bootstrap/cache
   ```

9. **Iniciar el servidor**:
   ```
   php artisan serve
   ```

10. **Acceder al sistema**:
    - Abrir el navegador y visitar: http://localhost:8000
    - Credenciales por defecto:
      - Usuario: admin@admin.com
      - Contraseña: password

### Solución de problemas comunes

- **Error de permisos**: Asegurarse de que el servidor web tenga permisos de escritura en las carpetas `storage` y `bootstrap/cache`.
- **Error de conexión a la base de datos**: Verificar que el servicio MySQL esté ejecutándose y que las credenciales en el archivo `.env` sean correctas.
- **Error al ejecutar migraciones**: Intentar limpiar la caché con `php artisan config:clear` y `php artisan cache:clear` antes de ejecutar las migraciones nuevamente.

### Contacto para soporte
Si el cliente necesita ayuda adicional, puede contactar a soporte técnico:
- Email: soporte@ejemplo.com
- Teléfono: +57 123 456 7890
