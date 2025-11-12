@echo off
echo === INSTALACION RAPIDA - SISTEMA DE VENTAS ===
echo Instalando dependencias...
composer install --no-dev --optimize-autoloader
echo Configurando aplicacion...
php artisan key:generate
php artisan migrate:fresh --seed
echo ¡Instalacion completada!
echo Acceder a: http://localhost/public
echo Usuario: admin@admin.com
echo Contraseña: password
pause
