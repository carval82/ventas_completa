@echo off
echo 🔧 PROBANDO CORRECCIÓN ALEGRA - MÚLTIPLES IMPUESTOS
echo ===================================================
echo.

echo 📋 Paso 1: Configurando empresa...
php artisan tinker --execute="$empresa = \App\Models\Empresa::first(); $empresa->alegra_multiples_impuestos = false; $empresa->save(); echo 'Empresa configurada: ' . $empresa->nombre_comercial . PHP_EOL; echo 'Múltiples impuestos: NO' . PHP_EOL;"

echo.
echo 📦 Paso 2: Preparando producto...
php artisan tinker --execute="$producto = \App\Models\Producto::find(43); echo 'Producto: ' . $producto->nombre . PHP_EOL; echo 'Código: ' . $producto->codigo . PHP_EOL; echo 'IVA: ' . $producto->iva . '%%' . PHP_EOL; $producto->id_alegra = null; $producto->save(); echo 'ID Alegra limpiado' . PHP_EOL;"

echo.
echo 🚀 Paso 3: Sincronizando con Alegra...
php artisan tinker --execute="$producto = \App\Models\Producto::find(43); $resultado = $producto->syncToAlegra(); if ($resultado['success']) { echo '✅ ÉXITO: Producto sincronizado' . PHP_EOL; echo 'ID Alegra: ' . $resultado['id_alegra'] . PHP_EOL; } else { echo '❌ ERROR: ' . $resultado['message'] . PHP_EOL; if (isset($resultado['error'])) echo 'Detalle: ' . $resultado['error'] . PHP_EOL; }"

echo.
echo 🎉 Prueba completada
pause
