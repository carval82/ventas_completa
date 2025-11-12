<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

try {
    echo "=== PREPARACIÓN DEL SISTEMA PARA CLIENTE ===\n\n";
    
    // 1. Limpiar archivos temporales y cache
    echo "🧹 LIMPIANDO ARCHIVOS TEMPORALES...\n";
    
    $directoriosLimpiar = [
        'storage/logs',
        'storage/framework/cache',
        'storage/framework/sessions',
        'storage/framework/views',
        'bootstrap/cache'
    ];
    
    foreach ($directoriosLimpiar as $directorio) {
        if (File::exists($directorio)) {
            $archivos = File::files($directorio);
            foreach ($archivos as $archivo) {
                if (pathinfo($archivo, PATHINFO_EXTENSION) !== 'gitignore') {
                    File::delete($archivo);
                }
            }
            echo "   ✅ Limpiado: {$directorio}\n";
        }
    }
    
    // 2. Limpiar archivos de desarrollo
    echo "\n🔧 REMOVIENDO ARCHIVOS DE DESARROLLO...\n";
    
    $archivosDesarrollo = [
        'test_*.php',
        'verificar_*.php',
        'restaurar_*.php',
        'configurar_*.php',
        'debug_*.php',
        'probar_*.php'
    ];
    
    foreach ($archivosDesarrollo as $patron) {
        $archivos = glob($patron);
        foreach ($archivos as $archivo) {
            if (File::exists($archivo)) {
                File::delete($archivo);
                echo "   ✅ Removido: {$archivo}\n";
            }
        }
    }
    
    // 3. Crear archivo de configuración para cliente
    echo "\n📋 CREANDO DOCUMENTACIÓN PARA CLIENTE...\n";
    
    $documentacionCliente = "# SISTEMA DE VENTAS COMPLETO - VERSIÓN CLIENTE\n\n";
    $documentacionCliente .= "## CARACTERÍSTICAS PRINCIPALES:\n";
    $documentacionCliente .= "✅ Sistema completo de ventas y facturación\n";
    $documentacionCliente .= "✅ Facturación electrónica integrada con Alegra\n";
    $documentacionCliente .= "✅ Gestión de inventario y productos\n";
    $documentacionCliente .= "✅ Control de clientes y proveedores\n";
    $documentacionCliente .= "✅ Reportes y estadísticas\n";
    $documentacionCliente .= "✅ Sistema de backup y restauración\n";
    $documentacionCliente .= "✅ Multi-usuario con roles y permisos\n\n";
    
    $documentacionCliente .= "## INSTALACIÓN:\n";
    $documentacionCliente .= "1. Copiar toda la carpeta al servidor web\n";
    $documentacionCliente .= "2. Configurar base de datos en archivo .env\n";
    $documentacionCliente .= "3. Ejecutar: php artisan migrate:fresh --seed\n";
    $documentacionCliente .= "4. Configurar credenciales de Alegra en la aplicación\n";
    $documentacionCliente .= "5. Probar conexión y sincronizar datos\n\n";
    
    $documentacionCliente .= "## CREDENCIALES INICIALES:\n";
    $documentacionCliente .= "Usuario: admin@admin.com\n";
    $documentacionCliente .= "Contraseña: password\n\n";
    
    $documentacionCliente .= "## SOPORTE TÉCNICO:\n";
    $documentacionCliente .= "Para soporte técnico y configuración personalizada,\n";
    $documentacionCliente .= "contactar al desarrollador.\n\n";
    
    $documentacionCliente .= "## VERSIÓN:\n";
    $documentacionCliente .= "Sistema de Ventas Completo v2.0\n";
    $documentacionCliente .= "Fecha: " . date('Y-m-d H:i:s') . "\n";
    $documentacionCliente .= "Incluye facturación electrónica completa\n";
    
    File::put('INSTRUCCIONES_CLIENTE.md', $documentacionCliente);
    echo "   ✅ Creado: INSTRUCCIONES_CLIENTE.md\n";
    
    // 4. Crear script de instalación rápida
    echo "\n⚡ CREANDO SCRIPT DE INSTALACIÓN RÁPIDA...\n";
    
    $scriptInstalacion = "#!/bin/bash\n";
    $scriptInstalacion .= "echo \"=== INSTALACIÓN RÁPIDA - SISTEMA DE VENTAS ===\"\n";
    $scriptInstalacion .= "echo \"Configurando permisos...\"\n";
    $scriptInstalacion .= "chmod -R 755 storage\n";
    $scriptInstalacion .= "chmod -R 755 bootstrap/cache\n";
    $scriptInstalacion .= "echo \"Instalando dependencias...\"\n";
    $scriptInstalacion .= "composer install --no-dev --optimize-autoloader\n";
    $scriptInstalacion .= "echo \"Configurando aplicación...\"\n";
    $scriptInstalacion .= "php artisan key:generate\n";
    $scriptInstalacion .= "php artisan migrate:fresh --seed\n";
    $scriptInstalacion .= "echo \"¡Instalación completada!\"\n";
    $scriptInstalacion .= "echo \"Acceder a: http://localhost/public\"\n";
    $scriptInstalacion .= "echo \"Usuario: admin@admin.com\"\n";
    $scriptInstalacion .= "echo \"Contraseña: password\"\n";
    
    File::put('instalar_rapido.sh', $scriptInstalacion);
    
    // Versión Windows
    $scriptWindows = "@echo off\n";
    $scriptWindows .= "echo === INSTALACION RAPIDA - SISTEMA DE VENTAS ===\n";
    $scriptWindows .= "echo Instalando dependencias...\n";
    $scriptWindows .= "composer install --no-dev --optimize-autoloader\n";
    $scriptWindows .= "echo Configurando aplicacion...\n";
    $scriptWindows .= "php artisan key:generate\n";
    $scriptWindows .= "php artisan migrate:fresh --seed\n";
    $scriptWindows .= "echo ¡Instalacion completada!\n";
    $scriptWindows .= "echo Acceder a: http://localhost/public\n";
    $scriptWindows .= "echo Usuario: admin@admin.com\n";
    $scriptWindows .= "echo Contraseña: password\n";
    $scriptWindows .= "pause\n";
    
    File::put('instalar_rapido.bat', $scriptWindows);
    
    echo "   ✅ Creado: instalar_rapido.sh (Linux/Mac)\n";
    echo "   ✅ Creado: instalar_rapido.bat (Windows)\n";
    
    // 5. Verificar estructura final
    echo "\n🔍 VERIFICANDO ESTRUCTURA FINAL...\n";
    
    $elementosEsenciales = [
        'app' => 'Aplicación principal',
        'database' => 'Migraciones y seeders',
        'public' => 'Punto de entrada web',
        'resources' => 'Vistas y assets',
        'routes' => 'Rutas de la aplicación',
        '.env' => 'Configuración de entorno',
        'composer.json' => 'Dependencias PHP',
        'artisan' => 'Herramienta de línea de comandos'
    ];
    
    $todoOk = true;
    foreach ($elementosEsenciales as $elemento => $descripcion) {
        if (File::exists($elemento)) {
            echo "   ✅ {$descripcion}\n";
        } else {
            echo "   ❌ FALTANTE: {$descripcion}\n";
            $todoOk = false;
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🎉 PREPARACIÓN PARA CLIENTE COMPLETADA\n\n";
    
    if ($todoOk) {
        echo "✅ SISTEMA LISTO PARA DISTRIBUCIÓN\n";
        echo "✅ Archivos de desarrollo removidos\n";
        echo "✅ Cache limpiado\n";
        echo "✅ Documentación incluida\n";
        echo "✅ Scripts de instalación creados\n";
        echo "✅ Estructura verificada\n\n";
        
        echo "📦 PRÓXIMOS PASOS:\n";
        echo "1. Copiar toda la carpeta al pendrive\n";
        echo "2. Entregar al cliente con INSTRUCCIONES_CLIENTE.md\n";
        echo "3. Cliente ejecuta instalar_rapido.bat (Windows)\n";
        echo "4. Configurar credenciales de Alegra\n";
        echo "5. ¡Sistema listo para usar!\n\n";
        
        echo "🎊 ¡EL SISTEMA ESTÁ PREPARADO PARA EL CLIENTE!\n";
        
    } else {
        echo "⚠️ Revisar elementos faltantes antes de distribuir\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error preparando sistema: " . $e->getMessage() . "\n";
}
