#!/bin/bash
echo "=== INSTALACIÓN RÁPIDA - SISTEMA DE VENTAS ==="
echo "Configurando permisos..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache
echo "Instalando dependencias..."
composer install --no-dev --optimize-autoloader
echo "Configurando aplicación..."
php artisan key:generate
php artisan migrate:fresh --seed
echo "¡Instalación completada!"
echo "Acceder a: http://localhost/public"
echo "Usuario: admin@admin.com"
echo "Contraseña: password"
