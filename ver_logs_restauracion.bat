@echo off
echo ============================================
echo    LOGS DE RESTAURACION - ULTIMAS LINEAS
echo ============================================
echo.
powershell -Command "Get-Content storage\logs\laravel.log -Tail 300 | Select-String -Pattern 'Bloques|tabla|registros|Total' -Context 1"
echo.
echo ============================================
pause
