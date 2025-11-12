<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Empresa;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use App\Http\Services\AlegraService;
use Illuminate\Support\Facades\DB;

try {
    echo "=== VERIFICACIÓN COMPLETA PARA PRODUCCIÓN ===\n\n";
    
    $errores = [];
    $advertencias = [];
    $exitoso = [];
    
    // 1. VERIFICAR BASE DE DATOS
    echo "🗄️ VERIFICANDO BASE DE DATOS...\n";
    try {
        $tablas = [
            'users' => User::count(),
            'empresas' => Empresa::count(),
            'clientes' => Cliente::count(),
            'productos' => Producto::count(),
            'ventas' => Venta::count()
        ];
        
        foreach ($tablas as $tabla => $count) {
            if ($count > 0) {
                echo "   ✅ {$tabla}: {$count} registros\n";
                $exitoso[] = "Tabla {$tabla} con datos";
            } else {
                echo "   ⚠️ {$tabla}: Sin datos\n";
                $advertencias[] = "Tabla {$tabla} vacía";
            }
        }
    } catch (Exception $e) {
        $errores[] = "Error en base de datos: " . $e->getMessage();
        echo "   ❌ Error en base de datos\n";
    }
    echo "\n";
    
    // 2. VERIFICAR CONFIGURACIÓN DE EMPRESA
    echo "🏢 VERIFICANDO CONFIGURACIÓN DE EMPRESA...\n";
    $empresa = Empresa::first();
    if ($empresa) {
        echo "   ✅ Empresa: {$empresa->nombre}\n";
        echo "   ✅ NIT: {$empresa->nit}\n";
        echo "   ✅ Régimen: {$empresa->regimen_tributario}\n";
        
        if ($empresa->alegra_user && $empresa->alegra_token) {
            echo "   ✅ Credenciales Alegra configuradas\n";
            $exitoso[] = "Configuración de Alegra completa";
        } else {
            echo "   ❌ Credenciales Alegra faltantes\n";
            $errores[] = "Credenciales de Alegra no configuradas";
        }
        
        if ($empresa->porcentaje_iva) {
            echo "   ✅ IVA configurado: {$empresa->porcentaje_iva}%\n";
        } else {
            echo "   ⚠️ IVA no configurado\n";
            $advertencias[] = "Porcentaje de IVA no definido";
        }
    } else {
        echo "   ❌ No hay empresa configurada\n";
        $errores[] = "Empresa no configurada";
    }
    echo "\n";
    
    // 3. VERIFICAR CONEXIÓN CON ALEGRA
    echo "🔗 VERIFICANDO CONEXIÓN CON ALEGRA...\n";
    if ($empresa && $empresa->alegra_user && $empresa->alegra_token) {
        try {
            $alegraService = new AlegraService();
            $response = $alegraService->http->get('/company');
            
            if ($response->successful()) {
                $companyData = $response->json();
                echo "   ✅ Conexión exitosa\n";
                echo "   ✅ Empresa Alegra: " . ($companyData['name'] ?? 'N/A') . "\n";
                echo "   ✅ Plan: " . ($companyData['plan']['name'] ?? 'N/A') . "\n";
                
                if ($companyData['electronicInvoicing'] ?? false) {
                    echo "   ✅ Facturación electrónica habilitada\n";
                    $exitoso[] = "Facturación electrónica activa en Alegra";
                } else {
                    echo "   ❌ Facturación electrónica NO habilitada\n";
                    $errores[] = "Facturación electrónica no habilitada en Alegra";
                }
            } else {
                echo "   ❌ Error de conexión: " . $response->status() . "\n";
                $errores[] = "No se puede conectar con Alegra";
            }
        } catch (Exception $e) {
            echo "   ❌ Excepción: " . $e->getMessage() . "\n";
            $errores[] = "Error conectando con Alegra: " . $e->getMessage();
        }
    } else {
        echo "   ❌ Credenciales no configuradas\n";
        $errores[] = "Credenciales de Alegra faltantes";
    }
    echo "\n";
    
    // 4. VERIFICAR DATOS CRÍTICOS
    echo "📊 VERIFICANDO DATOS CRÍTICOS...\n";
    
    // Clientes con Alegra ID
    $clientesConAlegra = Cliente::whereNotNull('id_alegra')->count();
    $clientesConEmail = Cliente::whereNotNull('email')->where('email', '!=', '')->count();
    
    echo "   ✅ Clientes con ID Alegra: {$clientesConAlegra}\n";
    echo "   ✅ Clientes con email: {$clientesConEmail}\n";
    
    if ($clientesConAlegra > 0) {
        $exitoso[] = "Clientes sincronizados con Alegra";
    } else {
        $errores[] = "No hay clientes sincronizados con Alegra";
    }
    
    // Productos con Alegra ID
    $productosConAlegra = Producto::whereNotNull('id_alegra')->count();
    echo "   ✅ Productos con ID Alegra: {$productosConAlegra}\n";
    
    if ($productosConAlegra > 0) {
        $exitoso[] = "Productos sincronizados con Alegra";
    } else {
        $errores[] = "No hay productos sincronizados con Alegra";
    }
    
    // Ventas electrónicas
    $ventasElectronicas = Venta::whereNotNull('cufe')->count();
    echo "   ✅ Ventas electrónicas: {$ventasElectronicas}\n";
    
    if ($ventasElectronicas > 0) {
        $exitoso[] = "Sistema de facturación electrónica probado";
    }
    echo "\n";
    
    // 5. VERIFICAR ARCHIVOS CRÍTICOS
    echo "📁 VERIFICANDO ARCHIVOS CRÍTICOS...\n";
    $archivosCriticos = [
        'app/Http/Services/AlegraService.php' => 'Servicio de Alegra',
        'app/Models/Venta.php' => 'Modelo de Venta',
        'app/Http/Controllers/FacturaElectronicaController.php' => 'Controlador de Facturación',
        '.env' => 'Configuración de entorno'
    ];
    
    foreach ($archivosCriticos as $archivo => $descripcion) {
        if (file_exists($archivo)) {
            echo "   ✅ {$descripcion}\n";
            $exitoso[] = "Archivo {$descripcion} presente";
        } else {
            echo "   ❌ {$descripcion} faltante\n";
            $errores[] = "Archivo {$descripcion} no encontrado";
        }
    }
    echo "\n";
    
    // 6. VERIFICAR PERMISOS
    echo "🔐 VERIFICANDO PERMISOS...\n";
    $directorios = [
        'storage/logs' => 'Logs del sistema',
        'storage/app' => 'Almacenamiento de archivos',
        'bootstrap/cache' => 'Cache de bootstrap'
    ];
    
    foreach ($directorios as $directorio => $descripcion) {
        if (is_writable($directorio)) {
            echo "   ✅ {$descripcion} escribible\n";
            $exitoso[] = "Permisos correctos en {$descripcion}";
        } else {
            echo "   ❌ {$descripcion} sin permisos de escritura\n";
            $errores[] = "Permisos incorrectos en {$descripcion}";
        }
    }
    echo "\n";
    
    // 7. RESUMEN FINAL
    echo str_repeat("=", 60) . "\n";
    echo "🏆 RESUMEN DE VERIFICACIÓN PARA PRODUCCIÓN\n\n";
    
    echo "✅ ELEMENTOS EXITOSOS (" . count($exitoso) . "):\n";
    foreach ($exitoso as $item) {
        echo "   ✅ {$item}\n";
    }
    echo "\n";
    
    if (!empty($advertencias)) {
        echo "⚠️ ADVERTENCIAS (" . count($advertencias) . "):\n";
        foreach ($advertencias as $item) {
            echo "   ⚠️ {$item}\n";
        }
        echo "\n";
    }
    
    if (!empty($errores)) {
        echo "❌ ERRORES CRÍTICOS (" . count($errores) . "):\n";
        foreach ($errores as $item) {
            echo "   ❌ {$item}\n";
        }
        echo "\n";
    }
    
    // 8. EVALUACIÓN FINAL
    $totalVerificaciones = count($exitoso) + count($advertencias) + count($errores);
    $porcentajeExito = round((count($exitoso) / $totalVerificaciones) * 100, 1);
    
    echo "📊 EVALUACIÓN FINAL:\n";
    echo "   Porcentaje de éxito: {$porcentajeExito}%\n";
    echo "   Elementos exitosos: " . count($exitoso) . "\n";
    echo "   Advertencias: " . count($advertencias) . "\n";
    echo "   Errores críticos: " . count($errores) . "\n\n";
    
    if (count($errores) === 0 && $porcentajeExito >= 90) {
        echo "🎉 SISTEMA LISTO PARA PRODUCCIÓN\n";
        echo "✅ Todos los componentes críticos funcionando\n";
        echo "✅ Facturación electrónica operativa\n";
        echo "✅ Integración con Alegra exitosa\n\n";
        
        echo "🚀 PRÓXIMOS PASOS PARA PRODUCCIÓN:\n";
        echo "1. Implementar sistema de seguridad y encriptación\n";
        echo "2. Configurar backups automáticos\n";
        echo "3. Optimizar rendimiento\n";
        echo "4. Documentar procedimientos\n";
        echo "5. Preparar paquete de distribución\n";
        
    } elseif (count($errores) === 0) {
        echo "⚠️ SISTEMA CASI LISTO PARA PRODUCCIÓN\n";
        echo "Resolver advertencias antes del despliegue\n";
        
    } else {
        echo "❌ SISTEMA NO LISTO PARA PRODUCCIÓN\n";
        echo "Resolver errores críticos antes de continuar\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error crítico en verificación: " . $e->getMessage() . "\n";
}
