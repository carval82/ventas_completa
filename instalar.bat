@echo off
echo ===================================================
echo    INSTALACION DEL SISTEMA DE VENTAS
echo ===================================================
echo.

echo Paso 1: Instalando dependencias de PHP...
call composer install
if %ERRORLEVEL% NEQ 0 (
    echo Error al instalar dependencias de PHP.
    pause
    exit /b %ERRORLEVEL%
)
echo Dependencias de PHP instaladas correctamente.
echo.

echo Paso 2: Instalando dependencias de JavaScript...
call npm install
if %ERRORLEVEL% NEQ 0 (
    echo Error al instalar dependencias de JavaScript.
    pause
    exit /b %ERRORLEVEL%
)
echo Dependencias de JavaScript instaladas correctamente.
echo.

echo Paso 3: Compilando assets...
call npm run build
if %ERRORLEVEL% NEQ 0 (
    echo Error al compilar assets.
    pause
    exit /b %ERRORLEVEL%
)
echo Assets compilados correctamente.
echo.

echo Paso 4: Configurando el entorno...
if not exist .env (
    copy .env.example .env
    echo Archivo .env creado. Por favor, configura la conexión a la base de datos.
    notepad .env
) else (
    echo El archivo .env ya existe.
)
echo.

echo Paso 5: Generando clave de aplicación...
call php artisan key:generate
if %ERRORLEVEL% NEQ 0 (
    echo Error al generar la clave de aplicación.
    pause
    exit /b %ERRORLEVEL%
)
echo Clave de aplicación generada correctamente.
echo.

echo Paso 6: ¿Deseas crear la base de datos y ejecutar las migraciones? (S/N)
set /p ejecutar_migraciones=
if /i "%ejecutar_migraciones%"=="S" (
    echo Ejecutando migraciones y seeders...
    call php artisan migrate:fresh --seed
    if %ERRORLEVEL% NEQ 0 (
        echo Error al ejecutar las migraciones.
        pause
        exit /b %ERRORLEVEL%
    )
    echo Migraciones y seeders ejecutados correctamente.
) else (
    echo Migraciones omitidas. Deberás ejecutarlas manualmente.
)
echo.

echo Paso 7: Limpiando caché...
call php artisan config:clear
call php artisan cache:clear
call php artisan view:clear
echo Caché limpiada correctamente.
echo.

echo ===================================================
echo    INSTALACIÓN COMPLETADA
echo ===================================================
echo.
echo Para iniciar el servidor, ejecuta: php artisan serve
echo Luego abre tu navegador en: http://localhost:8000
echo.
echo Credenciales por defecto:
echo Usuario: admin@admin.com
echo Contraseña: password
echo.
echo Para más información, consulta el archivo INSTALACION.md
echo.

pause
