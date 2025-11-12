@echo off
echo ===================================================
echo    EMPAQUETANDO SISTEMA DE VENTAS PARA DISTRIBUCION
echo ===================================================
echo.

set FECHA=%date:~-4,4%%date:~-7,2%%date:~-10,2%
set NOMBRE_PAQUETE=sistema_ventas_%FECHA%

echo Limpiando directorios temporales anteriores...
rmdir /S /Q temp_%NOMBRE_PAQUETE% 2>nul

echo Creando directorio temporal...
mkdir temp_%NOMBRE_PAQUETE%
if %ERRORLEVEL% NEQ 0 (
    echo Error al crear directorio temporal.
    pause
    exit /b %ERRORLEVEL%
)

echo Copiando archivos del proyecto...
xcopy /E /I /Y . temp_%NOMBRE_PAQUETE%\
if %ERRORLEVEL% NEQ 0 (
    echo Error al copiar archivos.
    pause
    exit /b %ERRORLEVEL%
)

echo Eliminando archivos y directorios innecesarios...
rmdir /S /Q temp_%NOMBRE_PAQUETE%\.git 2>nul
rmdir /S /Q temp_%NOMBRE_PAQUETE%\node_modules 2>nul
rmdir /S /Q temp_%NOMBRE_PAQUETE%\vendor 2>nul
del temp_%NOMBRE_PAQUETE%\.env 2>nul
del temp_%NOMBRE_PAQUETE%\*.zip 2>nul
del temp_%NOMBRE_PAQUETE%\storage\logs\*.log 2>nul

echo Creando directorios necesarios...
mkdir temp_%NOMBRE_PAQUETE%\storage\logs
mkdir temp_%NOMBRE_PAQUETE%\storage\framework\cache
mkdir temp_%NOMBRE_PAQUETE%\storage\framework\sessions
mkdir temp_%NOMBRE_PAQUETE%\storage\framework\views
mkdir temp_%NOMBRE_PAQUETE%\bootstrap\cache

echo Creando archivo .env.example...
copy .env.example temp_%NOMBRE_PAQUETE%\.env.example

echo Empaquetando archivos...
powershell -Command "Compress-Archive -Path temp_%NOMBRE_PAQUETE%\* -DestinationPath %NOMBRE_PAQUETE%.zip"
if %ERRORLEVEL% NEQ 0 (
    echo Error al empaquetar archivos.
    pause
    exit /b %ERRORLEVEL%
)

echo Limpiando archivos temporales...
rmdir /S /Q temp_%NOMBRE_PAQUETE%

echo.
echo ===================================================
echo    EMPAQUETADO COMPLETADO
echo ===================================================
echo.
echo El paquete ha sido creado como: %NOMBRE_PAQUETE%.zip
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
