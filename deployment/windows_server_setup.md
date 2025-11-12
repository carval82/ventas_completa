# 🖥️ IMPLEMENTACIÓN EN SERVIDOR WINDOWS

## PASO 1: CONFIGURAR SERVIDOR WEB

### Opción A: XAMPP (Recomendado)

#### 1.1 Descargar e Instalar XAMPP
```
1. Ir a: https://www.apachefriends.org/
2. Descargar XAMPP para Windows (PHP 8.2)
3. Ejecutar como Administrador
4. Instalar en: C:\xampp
5. Seleccionar: Apache, MySQL, PHP, phpMyAdmin
```

#### 1.2 Configurar XAMPP
```
1. Abrir XAMPP Control Panel como Administrador
2. Iniciar Apache y MySQL
3. Configurar como servicios de Windows:
   - Apache: Hacer clic en "Install" como servicio
   - MySQL: Hacer clic en "Install" como servicio
```

#### 1.3 Configurar PHP
```
1. Editar: C:\xampp\php\php.ini
2. Buscar y modificar:
   - max_execution_time = 300
   - memory_limit = 512M
   - upload_max_filesize = 100M
   - post_max_size = 100M
   - extension=zip (descomentar)
   - extension=curl (descomentar)
   - extension=gd (descomentar)
3. Reiniciar Apache
```

## PASO 2: CONFIGURAR DOMINIO Y DNS

### 2.1 Registrar Dominio
```
Opciones recomendadas:
- Namecheap.com
- GoDaddy.com
- Cloudflare Registrar

Ejemplo: tuempresa.com
```

### 2.2 Configurar DNS
```
Crear registros DNS:
- A Record: @ → IP_DEL_SERVIDOR
- A Record: www → IP_DEL_SERVIDOR
- A Record: * → IP_DEL_SERVIDOR (para subdominios)

Ejemplo:
@ → 192.168.1.100
www → 192.168.1.100
* → 192.168.1.100
```

### 2.3 Configurar Virtual Hosts
```
1. Editar: C:\xampp\apache\conf\extra\httpd-vhosts.conf
2. Agregar:

<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/ventas_sistema/public"
    ServerName tuempresa.com
    ServerAlias www.tuempresa.com
    ServerAlias *.tuempresa.com
    
    <Directory "C:/xampp/htdocs/ventas_sistema/public">
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog "logs/ventas_error.log"
    CustomLog "logs/ventas_access.log" common
</VirtualHost>
```

## PASO 3: SUBIR APLICACIÓN

### 3.1 Preparar Archivos
```
1. Comprimir tu proyecto Laravel en ZIP
2. Subir al servidor via:
   - FTP/SFTP (FileZilla)
   - RDP (Escritorio Remoto)
   - Panel de control del hosting
```

### 3.2 Ubicación en Servidor
```
Extraer en: C:\xampp\htdocs\ventas_sistema\
Estructura:
C:\xampp\htdocs\ventas_sistema\
├── app/
├── database/
├── public/
├── resources/
├── routes/
├── .env
└── composer.json
```

### 3.3 Instalar Dependencias
```
1. Abrir CMD como Administrador
2. cd C:\xampp\htdocs\ventas_sistema
3. composer install --optimize-autoloader --no-dev
4. npm install
5. npm run build
```

## PASO 4: CONFIGURAR BASE DE DATOS

### 4.1 Crear Base de Datos Principal
```
1. Abrir: http://localhost/phpmyadmin
2. Crear nueva base de datos: "ventas_sistema"
3. Collation: utf8mb4_unicode_ci
```

### 4.2 Configurar .env
```
APP_NAME="Sistema Ventas Multi-Tenant"
APP_ENV=production
APP_KEY=base64:TU_APP_KEY_AQUI
APP_DEBUG=false
APP_URL=https://tuempresa.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ventas_sistema
DB_USERNAME=root
DB_PASSWORD=tu_password_mysql

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=tu_app_password
MAIL_ENCRYPTION=tls
```

### 4.3 Generar APP_KEY
```
php artisan key:generate
```

## PASO 5: EJECUTAR INSTALACIÓN MULTI-TENANT

### 5.1 Migrar Tabla Principal
```
php artisan migrate --path=database/migrations/2025_01_01_000000_create_tenants_table.php
```

### 5.2 Configurar Migraciones Tenant
```
php artisan tenant:setup-migrations
```

### 5.3 Crear Empresa Demo
```
php artisan tinker

$tenant = App\Models\Tenant::create([
    'slug' => 'demo',
    'nombre' => 'Empresa Demo',
    'nit' => '123456789-0',
    'email' => 'admin@demo.com',
    'telefono' => '555-0123',
    'direccion' => 'Calle Demo 123',
    'database_name' => 'ventas_demo',
    'database_host' => '127.0.0.1',
    'database_port' => '3306',
    'database_username' => 'root',
    'database_password' => 'tu_password_mysql',
    'plan' => 'premium',
    'fecha_creacion' => now(),
]);

$tenant->crearBaseDatos();
```

## PASO 6: CONFIGURAR RUTAS DE PRODUCCIÓN

### 6.1 Actualizar web.php
```
1. Hacer backup de routes/web.php
2. Reemplazar contenido con routes/web_multitenant.php
3. Verificar que todas las rutas funcionen
```

### 6.2 Configurar Middleware
```
Verificar que esté registrado en app/Http/Kernel.php:
'tenant' => \App\Http\Middleware\TenantMiddleware::class,
```

## PASO 7: CONFIGURAR SSL (HTTPS)

### 7.1 Opción A: Let's Encrypt (Gratis)
```
1. Instalar Certbot para Windows
2. Generar certificado:
   certbot --apache -d tuempresa.com -d www.tuempresa.com
```

### 7.2 Opción B: Cloudflare (Recomendado)
```
1. Crear cuenta en Cloudflare
2. Agregar tu dominio
3. Cambiar nameservers
4. Activar SSL/TLS Full
5. Configurar reglas de página
```

## PASO 8: CONFIGURAR BACKUPS

### 8.1 Backup Automático de Bases de Datos
```
Crear script: backup_databases.bat

@echo off
set MYSQL_PATH=C:\xampp\mysql\bin
set BACKUP_PATH=C:\backups\mysql
set DATE=%date:~-4,4%%date:~-10,2%%date:~-7,2%

%MYSQL_PATH%\mysqldump -u root -p ventas_sistema > %BACKUP_PATH%\sistema_%DATE%.sql
%MYSQL_PATH%\mysqldump -u root -p ventas_demo > %BACKUP_PATH%\demo_%DATE%.sql

echo Backup completado: %DATE%
```

### 8.2 Programar en Tareas de Windows
```
1. Abrir "Programador de tareas"
2. Crear tarea básica
3. Ejecutar diariamente a las 2:00 AM
4. Acción: Iniciar programa
5. Programa: C:\backups\backup_databases.bat
```

## PASO 9: MONITOREO Y LOGS

### 9.1 Configurar Logs
```
En .env:
LOG_CHANNEL=daily
LOG_LEVEL=error
```

### 9.2 Monitorear Rendimiento
```
Herramientas recomendadas:
- Task Manager (Administrador de tareas)
- Resource Monitor
- XAMPP Control Panel
```

## PASO 10: PRUEBAS FINALES

### 10.1 URLs a Probar
```
✅ https://tuempresa.com (Página principal)
✅ https://tuempresa.com/empresa/demo/dashboard (Empresa demo)
✅ https://tuempresa.com/admin/tenants (Panel admin)
✅ https://tuempresa.com/registro (Registro nuevas empresas)
```

### 10.2 Funcionalidades a Verificar
```
✅ Crear nueva empresa
✅ Login en empresa demo
✅ Crear productos
✅ Realizar ventas
✅ Conversiones de unidades
✅ Reportes
```

## 🚀 URLS FINALES DEL SISTEMA

```
🏠 Página Principal: https://tuempresa.com
🏢 Empresa Demo: https://tuempresa.com/empresa/demo/dashboard
🔧 Panel Admin: https://tuempresa.com/admin/tenants
📝 Registro: https://tuempresa.com/registro
```

## 💰 MODELO DE NEGOCIO

### Planes Sugeridos:
```
📦 BÁSICO - $29/mes
   - 3 usuarios
   - 500 productos
   - 200 ventas/mes

💎 PREMIUM - $79/mes
   - 10 usuarios
   - 2000 productos
   - 1000 ventas/mes

🏢 ENTERPRISE - $199/mes
   - 50 usuarios
   - 10000 productos
   - 5000 ventas/mes
```

¡Sistema listo para recibir múltiples empresas! 🎉
