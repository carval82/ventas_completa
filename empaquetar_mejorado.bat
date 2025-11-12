@echo off
echo ===================================================
echo    EMPAQUETANDO SISTEMA DE VENTAS PARA DISTRIBUCION
echo ===================================================
echo.

set FECHA=%date:~-4,4%%date:~-7,2%%date:~-10,2%
set NOMBRE_PAQUETE=sistema_ventas_%FECHA%
set TEMP_DIR=%USERPROFILE%\Documents\temp_%NOMBRE_PAQUETE%
set DESTINO=%USERPROFILE%\Documents\%NOMBRE_PAQUETE%.zip

echo Limpiando directorios temporales anteriores...
if exist "%TEMP_DIR%" rmdir /S /Q "%TEMP_DIR%"
if exist "%DESTINO%" del "%DESTINO%"

echo Creando directorio temporal en Documentos...
mkdir "%TEMP_DIR%"
if %ERRORLEVEL% NEQ 0 (
    echo Error al crear directorio temporal.
    pause
    exit /b %ERRORLEVEL%
)

echo Copiando archivos del proyecto...
xcopy /E /I /Y app "%TEMP_DIR%\app\"
xcopy /E /I /Y bootstrap "%TEMP_DIR%\bootstrap\"
xcopy /E /I /Y config "%TEMP_DIR%\config\"
xcopy /E /I /Y database "%TEMP_DIR%\database\"
xcopy /E /I /Y lang "%TEMP_DIR%\lang\"
xcopy /E /I /Y public "%TEMP_DIR%\public\"
xcopy /E /I /Y resources "%TEMP_DIR%\resources\"
xcopy /E /I /Y routes "%TEMP_DIR%\routes\"
mkdir "%TEMP_DIR%\storage"
xcopy /E /I /Y storage\app "%TEMP_DIR%\storage\app\"
xcopy /E /I /Y storage\framework "%TEMP_DIR%\storage\framework\"
mkdir "%TEMP_DIR%\storage\logs"
type nul > "%TEMP_DIR%\storage\logs\.gitkeep"

echo Copiando archivos adicionales...
copy .env.example "%TEMP_DIR%\"
copy artisan "%TEMP_DIR%\"
copy composer.json "%TEMP_DIR%\"
copy composer.lock "%TEMP_DIR%\"
copy package.json "%TEMP_DIR%\"
copy package-lock.json "%TEMP_DIR%\"
copy vite.config.js "%TEMP_DIR%\"
copy README.md "%TEMP_DIR%\"
copy INSTALACION.md "%TEMP_DIR%\"
copy instalar.bat "%TEMP_DIR%\"
copy instalar.sh "%TEMP_DIR%\"

echo Creando directorios vacíos necesarios...
mkdir "%TEMP_DIR%\storage\framework\cache"
mkdir "%TEMP_DIR%\storage\framework\sessions"
mkdir "%TEMP_DIR%\storage\framework\views"
mkdir "%TEMP_DIR%\bootstrap\cache"

echo Empaquetando archivos...
powershell -Command "Compress-Archive -Path '%TEMP_DIR%\*' -DestinationPath '%DESTINO%'"
if %ERRORLEVEL% NEQ 0 (
    echo Error al empaquetar archivos.
    pause
    exit /b %ERRORLEVEL%
)

echo Limpiando archivos temporales...
rmdir /S /Q "%TEMP_DIR%"

echo.
echo ===================================================
echo    EMPAQUETADO COMPLETADO
echo ===================================================
echo.
echo El paquete ha sido creado como: %DESTINO%
echo.
echo Este paquete contiene todos los archivos necesarios para
echo instalar el sistema en un nuevo servidor.
echo.
echo Instrucciones para el cliente:
echo 1. Descomprimir el archivo ZIP
echo 2. Ejecutar instalar.bat (Windows) o instalar.sh (Linux/Mac)
echo 3. Seguir las instrucciones en pantalla
echo.

pause
