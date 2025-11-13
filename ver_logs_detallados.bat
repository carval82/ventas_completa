@echo off
echo ============================================
echo    LOGS DETALLADOS DE RESTAURACION
echo ============================================
echo.
powershell -Command "Get-Content storage\logs\laravel.log -Tail 150 | Select-String -Pattern 'INSERT encontradas|Tablas agrupadas|Procesando tabla|Registros totales|Conteo final|bloques_insert|total_valores' -Context 1"
echo.
echo ============================================
pause
