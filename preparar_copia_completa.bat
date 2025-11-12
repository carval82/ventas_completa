@echo off
echo ===================================================
echo    PREPARANDO COPIA COMPLETA DEL SISTEMA
echo ===================================================
echo.

set FECHA=%date:~-4,4%%date:~-7,2%%date:~-10,2%
set NOMBRE_CARPETA=sistema_ventas_completo_%FECHA%
set DESTINO=%USERPROFILE%\Documents\%NOMBRE_CARPETA%

echo Limpiando directorios anteriores...
if exist "%DESTINO%" rmdir /S /Q "%DESTINO%"

echo Creando directorio de destino...
mkdir "%DESTINO%"
if %ERRORLEVEL% NEQ 0 (
    echo Error al crear directorio de destino.
    pause
    exit /b %ERRORLEVEL%
)

echo Copiando todos los archivos del proyecto...
xcopy /E /I /Y . "%DESTINO%\"
if %ERRORLEVEL% NEQ 0 (
    echo Error al copiar archivos.
    pause
    exit /b %ERRORLEVEL%
)

echo Eliminando archivos y directorios innecesarios...
if exist "%DESTINO%\.git" rmdir /S /Q "%DESTINO%\.git"
if exist "%DESTINO%\.github" rmdir /S /Q "%DESTINO%\.github"
if exist "%DESTINO%\.idea" rmdir /S /Q "%DESTINO%\.idea"
if exist "%DESTINO%\.vscode" rmdir /S /Q "%DESTINO%\.vscode"
if exist "%DESTINO%\storage\logs\*.log" del /Q "%DESTINO%\storage\logs\*.log"
if exist "%DESTINO%\storage\framework\cache\data\*" del /Q "%DESTINO%\storage\framework\cache\data\*"
if exist "%DESTINO%\storage\framework\sessions\*" del /Q "%DESTINO%\storage\framework\sessions\*"
if exist "%DESTINO%\storage\framework\views\*.php" del /Q "%DESTINO%\storage\framework\views\*.php"

echo Creando archivo de configuración para el cliente...
copy "%DESTINO%\.env.example" "%DESTINO%\.env"
echo Editando archivo .env para configuración básica...
powershell -Command "(Get-Content '%DESTINO%\.env') -replace 'APP_ENV=.*', 'APP_ENV=production' | Set-Content '%DESTINO%\.env'"
powershell -Command "(Get-Content '%DESTINO%\.env') -replace 'APP_DEBUG=.*', 'APP_DEBUG=false' | Set-Content '%DESTINO%\.env'"

echo Creando archivo de instrucciones para el cliente...
echo # Instrucciones de Instalación > "%DESTINO%\LEEME_PRIMERO.txt"
echo. >> "%DESTINO%\LEEME_PRIMERO.txt"
echo 1. Copie esta carpeta completa a la ubicación deseada en el servidor (por ejemplo, C:\xampp\htdocs\ventas) >> "%DESTINO%\LEEME_PRIMERO.txt"
echo 2. Abra el archivo .env y configure la conexión a la base de datos: >> "%DESTINO%\LEEME_PRIMERO.txt"
echo    - DB_DATABASE: nombre de la base de datos >> "%DESTINO%\LEEME_PRIMERO.txt"
echo    - DB_USERNAME: usuario de la base de datos >> "%DESTINO%\LEEME_PRIMERO.txt"
echo    - DB_PASSWORD: contraseña de la base de datos >> "%DESTINO%\LEEME_PRIMERO.txt"
echo 3. Abra phpMyAdmin y cree una base de datos con el nombre configurado en el paso anterior >> "%DESTINO%\LEEME_PRIMERO.txt"
echo 4. Abra una terminal en esta carpeta y ejecute: >> "%DESTINO%\LEEME_PRIMERO.txt"
echo    - php artisan key:generate >> "%DESTINO%\LEEME_PRIMERO.txt"
echo    - php artisan migrate:fresh --seed >> "%DESTINO%\LEEME_PRIMERO.txt"
echo 5. Configure su servidor web para apuntar a la carpeta public de esta instalación >> "%DESTINO%\LEEME_PRIMERO.txt"
echo 6. Acceda al sistema con las credenciales por defecto: >> "%DESTINO%\LEEME_PRIMERO.txt"
echo    - Usuario: admin@admin.com >> "%DESTINO%\LEEME_PRIMERO.txt"
echo    - Contraseña: password >> "%DESTINO%\LEEME_PRIMERO.txt"
echo. >> "%DESTINO%\LEEME_PRIMERO.txt"
echo Para soporte técnico, contacte a: soporte@ejemplo.com >> "%DESTINO%\LEEME_PRIMERO.txt"

echo.
echo ===================================================
echo    COPIA COMPLETA CREADA EXITOSAMENTE
echo ===================================================
echo.
echo La copia completa del sistema ha sido creada en:
echo %DESTINO%
echo.
echo Esta carpeta contiene todos los archivos necesarios para
echo instalar el sistema directamente en el servidor del cliente.
echo.
echo Instrucciones para el cliente:
echo 1. Copiar esta carpeta completa a la ubicación deseada en el servidor
echo 2. Seguir las instrucciones en el archivo LEEME_PRIMERO.txt
echo.

pause
