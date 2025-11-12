@echo off
echo ===================================================
echo  Backup Completo - Sistema de Ventas
echo ===================================================
echo.

REM Configurar variables
set FECHA=%date:~6,4%-%date:~3,2%-%date:~0,2%_%time:~0,2%-%time:~3,2%-%time:~6,2%
set FECHA=%FECHA: =0%
set BACKUP_DIR=C:\backups_ventas
set PROYECTO_DIR=C:\xampp\htdocs\laravel\ventas_completa
set BACKUP_NOMBRE=ventas_completa_backup_%FECHA%
set BACKUP_ARCHIVO=%BACKUP_DIR%\%BACKUP_NOMBRE%.zip
set BACKUP_SQL=%BACKUP_DIR%\%BACKUP_NOMBRE%_database.sql

REM Crear directorio de backups si no existe
if not exist "%BACKUP_DIR%" (
    mkdir "%BACKUP_DIR%"
    echo Directorio de backups creado: %BACKUP_DIR%
)

echo.
echo Creando backup de archivos...
echo.

REM Usar 7-Zip si está disponible, de lo contrario usar compresión nativa de Windows
where 7z >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo Usando 7-Zip para comprimir archivos...
    7z a -tzip "%BACKUP_ARCHIVO%" "%PROYECTO_DIR%\*" -xr!node_modules -xr!vendor -xr!storage\logs\* -xr!storage\framework\cache\* -xr!.git
) else (
    echo Usando compresión nativa de Windows...
    powershell -Command "Add-Type -Assembly 'System.IO.Compression.FileSystem'; [System.IO.Compression.ZipFile]::CreateFromDirectory('%PROYECTO_DIR%', '%BACKUP_ARCHIVO%')"
)

echo.
echo Creando backup de la base de datos...
echo.

REM Ejecutar el comando Artisan para hacer backup de la base de datos
cd /d "%PROYECTO_DIR%"
php artisan backup:database

REM Copiar el archivo SQL más reciente al directorio de backups
for /f "tokens=*" %%a in ('dir /b /od "%PROYECTO_DIR%\storage\app\backups\*.sql"') do set "ULTIMO_SQL=%%a"
copy "%PROYECTO_DIR%\storage\app\backups\%ULTIMO_SQL%" "%BACKUP_SQL%"

echo.
echo ===================================================
echo  Backup Completo Finalizado
echo ===================================================
echo.
echo Los archivos de backup se encuentran en:
echo.
echo 1. Archivos del proyecto: %BACKUP_ARCHIVO%
echo 2. Base de datos: %BACKUP_SQL%
echo.
echo Ahora puedes proceder con la reestructuración para hacer el sistema responsive.
echo.
pause
