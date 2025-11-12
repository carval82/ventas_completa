<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ConfiguracionDian;
use App\Models\User;
use App\Models\Empresa;

echo "=== CONFIGURACIÓN BÁSICA MÓDULO DIAN ===\n\n";

try {
    // 1. Verificar usuario y empresa
    echo "👤 1. VERIFICANDO USUARIO Y EMPRESA...\n";
    $usuario = User::first();
    
    if (!$usuario) {
        echo "  ❌ No hay usuarios en el sistema\n";
        exit(1);
    }
    
    echo "  ✅ Usuario encontrado: {$usuario->name} ({$usuario->email})\n";
    
    if (!$usuario->empresa) {
        echo "  ❌ Usuario sin empresa asociada\n";
        exit(1);
    }
    
    echo "  ✅ Empresa asociada: {$usuario->empresa->nombre}\n";
    
    // 2. Verificar configuración DIAN existente
    echo "\n⚙️ 2. VERIFICANDO CONFIGURACIÓN DIAN...\n";
    $configuracion = ConfiguracionDian::where('empresa_id', $usuario->empresa->id)->first();
    
    if ($configuracion) {
        echo "  ✅ Configuración DIAN existente encontrada\n";
        echo "    📧 Email: {$configuracion->email_dian}\n";
        echo "    🌐 Servidor: {$configuracion->servidor_imap}:{$configuracion->puerto_imap}\n";
        echo "    🔒 SSL: " . ($configuracion->ssl_enabled ? 'Sí' : 'No') . "\n";
        echo "    ⚡ Activo: " . ($configuracion->activo ? 'Sí' : 'No') . "\n";
    } else {
        echo "  ⚠️ No hay configuración DIAN. Creando configuración básica...\n";
        
        // Crear configuración básica
        $configuracion = ConfiguracionDian::create([
            'empresa_id' => $usuario->empresa->id,
            'email_dian' => 'configurar@gmail.com', // Placeholder
            'password_email' => 'pendiente_configurar',
            'servidor_imap' => 'imap.gmail.com',
            'puerto_imap' => 993,
            'ssl_enabled' => true,
            'email_remitente' => 'configurar@gmail.com',
            'nombre_remitente' => $usuario->empresa->nombre,
            'plantilla_acuse' => null, // Usará la plantilla por defecto
            'frecuencia_revision' => 60,
            'hora_inicio' => '08:00',
            'hora_fin' => '18:00',
            'activo' => false, // Inactivo hasta configurar correctamente
            'carpeta_descarga' => 'dian/descargas'
        ]);
        
        echo "  ✅ Configuración básica creada\n";
    }
    
    // 3. Verificar extensión IMAP
    echo "\n🔌 3. VERIFICANDO EXTENSIÓN IMAP...\n";
    if (extension_loaded('imap')) {
        echo "  ✅ Extensión IMAP disponible\n";
    } else {
        echo "  ❌ Extensión IMAP NO disponible\n";
        echo "  🔧 SOLUCIÓN:\n";
        echo "     1. Abrir: C:\\xampp\\php\\php.ini\n";
        echo "     2. Buscar: ;extension=imap\n";
        echo "     3. Cambiar a: extension=imap\n";
        echo "     4. Reiniciar Apache en XAMPP\n";
    }
    
    // 4. Verificar configuración de email
    echo "\n📧 4. VERIFICANDO CONFIGURACIÓN DE EMAIL...\n";
    $mailHost = env('MAIL_HOST');
    $mailUsername = env('MAIL_USERNAME');
    $mailPassword = env('MAIL_PASSWORD');
    
    if ($mailHost && $mailUsername && $mailPassword) {
        echo "  ✅ Configuración de email encontrada\n";
        echo "    🌐 Host: {$mailHost}\n";
        echo "    👤 Usuario: {$mailUsername}\n";
        echo "    🔐 Contraseña: " . (strlen($mailPassword) > 0 ? 'Configurada' : 'No configurada') . "\n";
        
        // Actualizar configuración DIAN con datos de email si es Gmail
        if (strpos($mailHost, 'gmail') !== false) {
            echo "  🎯 Detectado Gmail, actualizando configuración DIAN...\n";
            $configuracion->update([
                'email_dian' => $mailUsername,
                'servidor_imap' => 'imap.gmail.com',
                'puerto_imap' => 993,
                'ssl_enabled' => true,
                'email_remitente' => $mailUsername
            ]);
            echo "  ✅ Configuración DIAN actualizada con datos de Gmail\n";
        }
    } else {
        echo "  ⚠️ Configuración de email incompleta\n";
        echo "  🔧 SOLUCIÓN:\n";
        echo "     Agregar al archivo .env:\n";
        echo "     MAIL_MAILER=smtp\n";
        echo "     MAIL_HOST=smtp.gmail.com\n";
        echo "     MAIL_PORT=587\n";
        echo "     MAIL_USERNAME=tu_email@gmail.com\n";
        echo "     MAIL_PASSWORD=tu_contraseña_de_aplicacion\n";
        echo "     MAIL_ENCRYPTION=tls\n";
    }
    
    // 5. Estado final
    echo "\n🎯 5. ESTADO FINAL DEL MÓDULO DIAN:\n";
    
    $configuracionFinal = ConfiguracionDian::where('empresa_id', $usuario->empresa->id)->first();
    
    if ($configuracionFinal) {
        echo "  ✅ Configuración DIAN disponible\n";
        echo "  📍 Acceso: http://127.0.0.1:8000/dian\n";
        echo "  ⚙️ Configuración: http://127.0.0.1:8000/dian/configuracion\n";
        
        if ($configuracionFinal->activo) {
            echo "  🟢 Estado: ACTIVO\n";
        } else {
            echo "  🟡 Estado: INACTIVO (necesita configuración)\n";
        }
        
        // Pasos siguientes
        echo "\n📋 PASOS SIGUIENTES:\n";
        if (!extension_loaded('imap')) {
            echo "  1. ⚠️ Habilitar extensión IMAP en PHP\n";
        }
        if (!$mailUsername) {
            echo "  2. ⚠️ Configurar variables de email en .env\n";
        }
        echo "  3. 🌐 Ir a configuración del módulo DIAN\n";
        echo "  4. ✏️ Completar datos de email y contraseña\n";
        echo "  5. 🧪 Probar conexión IMAP\n";
        echo "  6. ✅ Activar el módulo\n";
        
    } else {
        echo "  ❌ Error: No se pudo crear configuración DIAN\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n✅ Configuración básica completada.\n";
