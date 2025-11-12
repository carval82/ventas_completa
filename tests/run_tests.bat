@echo off
echo Ejecutando pruebas unitarias para el módulo contable...
cd /d %~dp0..
php artisan test --filter=IvaValidationServiceTest
php artisan test --filter=PlantillaComprobanteServiceTest
php artisan test --filter=ContabilidadServiceTest
php artisan test --filter=ContabilidadQueryServiceTest
echo Pruebas completadas.
pause
