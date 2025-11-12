@echo off
echo Configurando sistema de equivalencias de productos...
echo.

echo 1. Ejecutando migracion de equivalencias...
php artisan migrate --path=database/migrations/2025_09_20_135503_create_producto_equivalencias_table.php

echo.
echo 2. Ejecutando seeder de equivalencias de ejemplo...
php artisan db:seed --class=ProductoEquivalenciasSeeder

echo.
echo ¡Sistema de equivalencias configurado!
echo.
echo Ejemplos creados:
echo - Paca de Arroz: 1 paca = 25 lb = 12.5 kg
echo - Bulto: 1 bulto = 40 kg = 88.18 lb  
echo - Liquido: 1 galon = 3.785 l = 3785 ml
echo.
pause
