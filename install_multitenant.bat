@echo off
echo ========================================
echo    INSTALACION SISTEMA MULTI-TENANT
echo ========================================
echo.

echo 🔄 Paso 1: Ejecutando migracion principal (tabla tenants)...
php artisan migrate --path=database/migrations/2025_01_01_000000_create_tenants_table.php
if %errorlevel% neq 0 (
    echo ❌ Error en migracion principal
    pause
    exit /b 1
)

echo.
echo 🔄 Paso 2: Configurando migraciones para tenants...
php artisan tenant:setup-migrations
if %errorlevel% neq 0 (
    echo ❌ Error configurando migraciones tenant
    pause
    exit /b 1
)

echo.
echo 🔄 Paso 3: Creando empresa de prueba...
php artisan tinker --execute="
use App\Models\Tenant;
$tenant = Tenant::create([
    'slug' => 'demo',
    'nombre' => 'Empresa Demo',
    'nit' => '123456789-0',
    'email' => 'admin@demo.com',
    'telefono' => '555-0123',
    'direccion' => 'Calle Demo 123',
    'database_name' => 'ventas_demo',
    'database_host' => 'localhost',
    'database_port' => '3306',
    'database_username' => env('DB_USERNAME'),
    'database_password' => env('DB_PASSWORD'),
    'plan' => 'premium',
    'fecha_creacion' => now(),
    'limite_usuarios' => 10,
    'limite_productos' => 2000,
    'limite_ventas_mes' => 1000,
]);
echo 'Tenant creado: ' . $tenant->slug;
"

echo.
echo 🔄 Paso 4: Creando base de datos para empresa demo...
php artisan tinker --execute="
$tenant = App\Models\Tenant::where('slug', 'demo')->first();
if ($tenant && $tenant->crearBaseDatos()) {
    echo 'Base de datos creada exitosamente';
} else {
    echo 'Error creando base de datos';
}
"

echo.
echo 🔄 Paso 5: Limpiando cache...
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo.
echo ========================================
echo ✅ INSTALACION COMPLETADA
echo ========================================
echo.
echo 🎉 Sistema Multi-Tenant instalado exitosamente!
echo.
echo 📋 INFORMACION IMPORTANTE:
echo.
echo 🏢 Empresa Demo Creada:
echo    • Slug: demo
echo    • URL: http://localhost/laravel/ventas_completa/public/empresa/demo/dashboard
echo    • Email: admin@demo.com
echo    • Password: admin123
echo.
echo 🔧 Panel de Administracion:
echo    • URL: http://localhost/laravel/ventas_completa/public/admin/tenants
echo.
echo 📝 Proximos pasos:
echo    1. Configurar rutas en web.php (usar web_multitenant.php como referencia)
echo    2. Crear usuario super-admin para panel de administracion
echo    3. Personalizar vistas segun necesidades
echo.
echo 🚀 Para crear nuevas empresas:
echo    POST /admin/tenants/crear
echo    {
echo        "nombre": "Nueva Empresa",
echo        "nit": "987654321-0", 
echo        "email": "admin@nuevaempresa.com",
echo        "plan": "basico"
echo    }
echo.
pause
