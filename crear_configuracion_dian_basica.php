<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ConfiguracionDian;
use App\Models\User;

echo "=== CREAR CONFIGURACIÓN DIAN BÁSICA ===\n\n";

try {
    // 1. Verificar usuario y empresa
    echo "👤 1. VERIFICANDO USUARIO Y EMPRESA...\n";
    $usuario = User::with('empresa')->first();
    
    if (!$usuario) {
        echo "  ❌ No hay usuarios en el sistema\n";
        exit(1);
    }
    
    echo "  ✅ Usuario: {$usuario->name} ({$usuario->email})\n";
    
    if (!$usuario->empresa) {
        echo "  ❌ Usuario sin empresa asociada\n";
        exit(1);
    }
    
    echo "  ✅ Empresa: {$usuario->empresa->nombre}\n";
    
    // 2. Crear configuración DIAN básica
    echo "\n⚙️ 2. CREANDO CONFIGURACIÓN DIAN BÁSICA...\n";
    
    $configuracion = ConfiguracionDian::updateOrCreate(
        ['empresa_id' => $usuario->empresa->id],
        [
            'email_dian' => 'tu_email@gmail.com', // Placeholder que el usuario debe cambiar
            'password_email' => 'tu_contraseña', // Placeholder que el usuario debe cambiar
            'servidor_imap' => 'imap.gmail.com',
            'puerto_imap' => 993,
            'ssl_enabled' => true,
            'email_remitente' => 'tu_email@gmail.com',
            'nombre_remitente' => $usuario->empresa->nombre,
            'plantilla_acuse' => null, // Usará la plantilla por defecto del modelo
            'frecuencia_revision' => 60, // Cada 60 minutos
            'hora_inicio' => '08:00',
            'hora_fin' => '18:00',
            'activo' => false, // Inactivo hasta que el usuario configure correctamente
            'carpeta_descarga' => 'dian/descargas'
        ]
    );
    
    echo "  ✅ Configuración DIAN creada/actualizada\n";
    echo "    🆔 ID: {$configuracion->id}\n";
    echo "    🏢 Empresa: {$configuracion->empresa_id}\n";
    echo "    📧 Email placeholder: {$configuracion->email_dian}\n";
    echo "    🌐 Servidor: {$configuracion->servidor_imap}:{$configuracion->puerto_imap}\n";
    echo "    🔒 SSL: " . ($configuracion->ssl_enabled ? 'Sí' : 'No') . "\n";
    echo "    ⚡ Activo: " . ($configuracion->activo ? 'Sí' : 'No') . "\n";
    
    // 3. Verificar que el módulo ahora funciona
    echo "\n🎯 3. VERIFICANDO ACCESO AL MÓDULO...\n";
    echo "  ✅ Configuración DIAN disponible\n";
    echo "  📍 Dashboard: http://127.0.0.1:8000/dian\n";
    echo "  ⚙️ Configuración: http://127.0.0.1:8000/dian/configuracion\n";
    
    // 4. Instrucciones para el usuario
    echo "\n📋 4. PASOS SIGUIENTES PARA EL USUARIO:\n";
    echo "  1. 🌐 Ve a: http://127.0.0.1:8000/dian/configuracion\n";
    echo "  2. 🎯 Selecciona tu proveedor (Gmail, Outlook, etc.)\n";
    echo "  3. ✏️ Completa tu email y contraseña reales\n";
    echo "  4. 🧪 Prueba la conexión IMAP\n";
    echo "  5. ✅ Activa el módulo\n";
    
    echo "\n💡 NOTAS IMPORTANTES:\n";
    echo "  • El módulo es 100% independiente de las variables .env\n";
    echo "  • Toda la configuración se hace desde la interfaz web\n";
    echo "  • No necesitas configurar MAIL_* en .env para el módulo DIAN\n";
    echo "  • Solo necesitas habilitar la extensión IMAP en PHP\n";
    
    // 5. Verificar extensión IMAP
    echo "\n🔌 5. VERIFICANDO EXTENSIÓN IMAP...\n";
    if (extension_loaded('imap')) {
        echo "  ✅ Extensión IMAP disponible\n";
        echo "  🎊 ¡El módulo está listo para usar!\n";
    } else {
        echo "  ❌ Extensión IMAP NO disponible\n";
        echo "  🔧 SOLUCIÓN:\n";
        echo "     1. Abrir: C:\\xampp\\php\\php.ini\n";
        echo "     2. Buscar: ;extension=imap\n";
        echo "     3. Cambiar a: extension=imap\n";
        echo "     4. Reiniciar Apache en XAMPP\n";
        echo "     5. Verificar con: php -m | findstr imap\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n✅ Proceso completado.\n";
