<?php

echo "🔧 ACTUALIZACIÓN DE CREDENCIALES ALEGRA\n";
echo "======================================\n\n";

$envFile = __DIR__ . '/.env';
$emailCorrecto = 'pcapacho24@hotmail.com';
$tokenCorrecto = '4398994d2a44f8153123';

echo "📋 Credenciales a configurar:\n";
echo "   - Email: {$emailCorrecto}\n";
echo "   - Token: {$tokenCorrecto}\n\n";

try {
    if (!file_exists($envFile)) {
        echo "❌ Error: Archivo .env no encontrado\n";
        exit(1);
    }

    // Leer el archivo .env
    $envContent = file_get_contents($envFile);
    
    if ($envContent === false) {
        echo "❌ Error: No se pudo leer el archivo .env\n";
        exit(1);
    }

    echo "📄 Actualizando archivo .env...\n";

    // Actualizar ALEGRA_USER
    if (preg_match('/^ALEGRA_USER=.*$/m', $envContent)) {
        $envContent = preg_replace('/^ALEGRA_USER=.*$/m', "ALEGRA_USER={$emailCorrecto}", $envContent);
        echo "   ✅ ALEGRA_USER actualizado\n";
    } else {
        $envContent .= "\nALEGRA_USER={$emailCorrecto}\n";
        echo "   ✅ ALEGRA_USER agregado\n";
    }

    // Actualizar ALEGRA_TOKEN
    if (preg_match('/^ALEGRA_TOKEN=.*$/m', $envContent)) {
        $envContent = preg_replace('/^ALEGRA_TOKEN=.*$/m', "ALEGRA_TOKEN={$tokenCorrecto}", $envContent);
        echo "   ✅ ALEGRA_TOKEN actualizado\n";
    } else {
        $envContent .= "ALEGRA_TOKEN={$tokenCorrecto}\n";
        echo "   ✅ ALEGRA_TOKEN agregado\n";
    }

    // Crear backup del archivo original
    $backupFile = $envFile . '.backup.' . date('Y-m-d_H-i-s');
    if (copy($envFile, $backupFile)) {
        echo "   💾 Backup creado: " . basename($backupFile) . "\n";
    }

    // Escribir el archivo actualizado
    if (file_put_contents($envFile, $envContent) !== false) {
        echo "   ✅ Archivo .env actualizado exitosamente\n\n";
        
        echo "🎯 PRÓXIMOS PASOS:\n";
        echo "1. Limpiar cache de configuración:\n";
        echo "   php artisan config:clear\n";
        echo "   php artisan cache:clear\n\n";
        echo "2. Verificar las credenciales:\n";
        echo "   php artisan tinker\n";
        echo "   >>> config('alegra.user')\n";
        echo "   >>> config('alegra.token')\n\n";
        echo "3. Probar conexión con Alegra:\n";
        echo "   php artisan alegra:test\n\n";
        
        echo "✅ CREDENCIALES ACTUALIZADAS CORRECTAMENTE\n";
        
    } else {
        echo "❌ Error: No se pudo escribir el archivo .env\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 Proceso completado\n";
