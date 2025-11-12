@echo off
echo === SOLUCION DEFINITIVA IMAP PARA XAMPP ===
echo.

echo 1. Deteniendo Apache...
taskkill /F /IM httpd.exe >nul 2>&1
timeout /t 3 >nul

echo 2. Verificando php.ini...
if exist "C:\xampp\php\php.ini" (
    echo    - Encontrado: C:\xampp\php\php.ini
    findstr /C:"extension=imap" "C:\xampp\php\php.ini" >nul
    if errorlevel 1 (
        echo    - IMAP no encontrado, agregando...
        echo extension=imap >> "C:\xampp\php\php.ini"
    ) else (
        echo    - IMAP ya configurado
    )
) else (
    echo    - ERROR: php.ini no encontrado
)

echo 3. Verificando DLL...
if exist "C:\xampp\php\ext\php_imap.dll" (
    echo    - php_imap.dll encontrada
) else (
    echo    - ERROR: php_imap.dll no encontrada
)

echo 4. Limpiando cache de PHP...
if exist "C:\xampp\tmp" rmdir /s /q "C:\xampp\tmp" >nul 2>&1
if exist "C:\Windows\Temp\php*" del /q "C:\Windows\Temp\php*" >nul 2>&1

echo 5. Iniciando Apache...
start "" "C:\xampp\apache\bin\httpd.exe"
timeout /t 5 >nul

echo.
echo === PROCESO COMPLETADO ===
echo Verifica en: http://127.0.0.1:8000/prueba_imap_robusta.php
echo.
pause
