@echo off
echo ===================================================
echo    CREANDO PAQUETE DE INSTALACION
echo ===================================================
echo.

set FECHA=%date:~-4,4%%date:~-7,2%%date:~-10,2%
set NOMBRE_PAQUETE=sistema_ventas_%FECHA%

echo Eliminando paquetes anteriores...
del %NOMBRE_PAQUETE%.zip 2>nul

echo Creando lista de archivos a incluir...
echo @echo off > incluir_archivos.txt
echo app >> incluir_archivos.txt
echo bootstrap >> incluir_archivos.txt
echo config >> incluir_archivos.txt
echo database >> incluir_archivos.txt
echo lang >> incluir_archivos.txt
echo public >> incluir_archivos.txt
echo resources >> incluir_archivos.txt
echo routes >> incluir_archivos.txt
echo storage >> incluir_archivos.txt
echo .env.example >> incluir_archivos.txt
echo artisan >> incluir_archivos.txt
echo composer.json >> incluir_archivos.txt
echo composer.lock >> incluir_archivos.txt
echo package.json >> incluir_archivos.txt
echo package-lock.json >> incluir_archivos.txt
echo vite.config.js >> incluir_archivos.txt
echo README.md >> incluir_archivos.txt
echo INSTALACION.md >> incluir_archivos.txt
echo instalar.bat >> incluir_archivos.txt
echo instalar.sh >> incluir_archivos.txt

echo Creando el paquete...
powershell -Command "Compress-Archive -Path @(Get-Content incluir_archivos.txt) -DestinationPath %NOMBRE_PAQUETE%.zip"
if %ERRORLEVEL% NEQ 0 (
    echo Error al crear el paquete.
    pause
    exit /b %ERRORLEVEL%
)

echo Eliminando archivo temporal...
del incluir_archivos.txt

echo.
echo ===================================================
echo    PAQUETE CREADO EXITOSAMENTE
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
