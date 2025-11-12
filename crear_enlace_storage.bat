@echo off
echo ================================================
echo    CREAR ENLACE SIMBOLICO PARA STORAGE
echo ================================================
echo.

cd /d "%~dp0"

echo Ejecutando: php artisan storage:link
php artisan storage:link

echo.
echo ================================================
echo    PROCESO COMPLETADO
echo ================================================
echo.
echo El enlace simbolico ha sido creado.
echo Ahora puede acceder a los archivos publicos
echo incluyendo el logo de la empresa.
echo.
pause
