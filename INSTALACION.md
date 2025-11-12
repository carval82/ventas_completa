# Guía de Instalación - Sistema de Ventas

Esta guía te ayudará a instalar el sistema de ventas en un nuevo servidor o computadora.

## Requisitos previos
- XAMPP (con PHP 8.1 o superior)
- Git
- Node.js y npm
- Composer

## Pasos de instalación

### 1. Clonar el repositorio
```
git clone [URL_DEL_REPOSITORIO] ventas_sistema
cd ventas_sistema
```

### 2. Instalar dependencias de PHP
```
composer install
```

### 3. Instalar dependencias de JavaScript
```
npm install
npm run build
```

### 4. Configurar el entorno
- Copia el archivo `.env.example` a `.env`
```
cp .env.example .env
```
- Genera una clave de aplicación
```
php artisan key:generate
```
- Edita el archivo `.env` con la configuración de tu base de datos:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ventas_completa
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Crear la base de datos
- Abre phpMyAdmin (http://localhost/phpmyadmin)
- Crea una nueva base de datos llamada `ventas_completa`

### 6. Ejecutar migraciones y seeders
```
php artisan migrate:fresh --seed
```

### 7. Configurar permisos (solo en Linux/Mac)
```
chmod -R 775 storage bootstrap/cache
```

### 8. Iniciar el servidor
```
php artisan serve
```

### 9. Acceder al sistema
- Abre tu navegador y visita: http://localhost:8000
- Credenciales por defecto:
  - Usuario: admin@admin.com
  - Contraseña: password

## Configuración de Alegra (Facturación Electrónica)
Para configurar la integración con Alegra, debes editar la información de la empresa en el sistema:
1. Inicia sesión como administrador
2. Ve a Configuración > Empresa
3. Completa los campos de API Key y API Token de Alegra

## Solución de problemas comunes

### Error de permisos
Si encuentras errores de permisos, asegúrate de que el servidor web tenga permisos de escritura en:
- La carpeta `storage`
- La carpeta `bootstrap/cache`

### Error de conexión a la base de datos
Verifica que:
- El servicio MySQL esté ejecutándose
- Las credenciales en el archivo `.env` sean correctas
- La base de datos exista

### Error al ejecutar migraciones
Si las migraciones fallan, intenta:
```
php artisan config:clear
php artisan cache:clear
```
Y luego ejecuta las migraciones nuevamente.

## Contacto para soporte
Si necesitas ayuda adicional, contacta a soporte técnico:
- Email: soporte@ejemplo.com
- Teléfono: +57 123 456 7890
