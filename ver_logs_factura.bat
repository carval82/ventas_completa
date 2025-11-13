@echo off
echo ============================================
echo    LOGS DE FACTURA ELECTRONICA
echo ============================================
echo.
powershell -Command "Get-Content storage\logs\laravel.log -Tail 500 | Select-String -Pattern '📥|🔍|🔄|✅|🚀|DETALLES|PROCESANDO|AGREGADO|FINALES' -Context 0"
echo.
echo ============================================
pause
