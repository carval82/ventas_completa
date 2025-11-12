@echo off
echo ===================================================
echo  Backup Simple - Sistema de Ventas
echo ===================================================
echo.

REM Crear directorio de backup si no existe
if not exist "C:\backups_ventas" mkdir "C:\backups_ventas"

echo Copiando archivos del proyecto...
echo Este proceso puede tardar varios minutos...
echo.

REM Copiar todo el proyecto a la carpeta de backup
xcopy "C:\xampp\htdocs\laravel\ventas_completa" "C:\backups_ventas\ventas_completa_backup" /E /H /C /I /Y /EXCLUDE:C:\backups_ventas\exclusiones.txt

REM Crear archivo de exclusiones
echo node_modules > "C:\backups_ventas\exclusiones.txt"
echo vendor >> "C:\backups_ventas\exclusiones.txt"
echo .git >> "C:\backups_ventas\exclusiones.txt"
echo storage\logs >> "C:\backups_ventas\exclusiones.txt"
echo storage\framework\cache >> "C:\backups_ventas\exclusiones.txt"

echo.
echo ===================================================
echo  Backup Completo Finalizado
echo ===================================================
echo.
echo Los archivos de backup se encuentran en:
echo.
echo 1. Archivos del proyecto: C:\backups_ventas\ventas_completa_backup
echo 2. Base de datos: C:\backups_ventas\ventas_completa_backup_*.sql (del backup anterior)
echo.
echo Ahora puedes proceder con la reestructuracion para hacer el sistema responsive.
echo.
pause
