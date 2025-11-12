@echo off
echo ===================================================
echo  Backup de Archivos - Sistema de Ventas
echo ===================================================
echo.

REM Configurar variables
set FECHA=%date:~6,4%-%date:~3,2%-%date:~0,2%_%time:~0,2%-%time:~3,2%-%time:~6,2%
set FECHA=%FECHA: =0%
set BACKUP_DIR=C:\backups_ventas
set PROYECTO_DIR=C:\xampp\htdocs\laravel\ventas_completa
set BACKUP_NOMBRE=ventas_completa_archivos_%FECHA%
set BACKUP_ARCHIVO=%BACKUP_DIR%\%BACKUP_NOMBRE%.zip

REM Crear directorio de backups si no existe
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

echo Creando backup de archivos del proyecto...
echo Este proceso puede tardar varios minutos...
echo.

REM Usar xcopy para copiar archivos a un directorio temporal
set TEMP_DIR=%BACKUP_DIR%\temp_%FECHA%
mkdir "%TEMP_DIR%"

xcopy "%PROYECTO_DIR%\*" "%TEMP_DIR%\" /E /H /C /I /Y /EXCLUDE:exclusiones.txt

REM Crear archivo de exclusiones
echo node_modules > "%PROYECTO_DIR%\exclusiones.txt"
echo vendor >> "%PROYECTO_DIR%\exclusiones.txt"
echo .git >> "%PROYECTO_DIR%\exclusiones.txt"
echo storage\logs >> "%PROYECTO_DIR%\exclusiones.txt"
echo storage\framework\cache >> "%PROYECTO_DIR%\exclusiones.txt"

REM Comprimir usando PowerShell
powershell -Command "Add-Type -AssemblyName System.IO.Compression.FileSystem; [System.IO.Compression.ZipFile]::CreateFromDirectory('%TEMP_DIR%', '%BACKUP_ARCHIVO%')"

REM Limpiar directorio temporal
rmdir /S /Q "%TEMP_DIR%"
del "%PROYECTO_DIR%\exclusiones.txt"

echo.
echo Backup de archivos completado: %BACKUP_ARCHIVO%
echo.
echo ===================================================
echo  Backup Completo Finalizado
echo ===================================================
echo.
echo Los archivos de backup se encuentran en:
echo.
echo 1. Archivos del proyecto: %BACKUP_ARCHIVO%
echo 2. Base de datos: %BACKUP_DIR%\ventas_completa_backup_*.sql
echo.
echo Ahora puedes proceder con la reestructuracion para hacer el sistema responsive.
echo.
pause
