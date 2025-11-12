<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ConfiguracionDian;
use App\Models\EmailBuzon;

echo "📧 AGREGANDO EMAILS DE PRUEBA PARA LA VISTA\n";
echo "==========================================\n\n";

$config = ConfiguracionDian::where('activo', true)->first();
$empresa = $config->empresa;

echo "🏢 Empresa: " . $empresa->nombre . "\n";
echo "📧 Email: " . $config->email_dian . "\n\n";

// Limpiar emails anteriores
echo "🧹 Limpiando emails anteriores...\n";
EmailBuzon::where('empresa_id', $empresa->id)->delete();

echo "📧 Creando emails de prueba...\n\n";

// Email 1: De Agrosander con factura
$email1 = EmailBuzon::create([
    'empresa_id' => $empresa->id,
    'mensaje_id' => 'PRUEBA_001_' . time(),
    'cuenta_email' => $config->email_dian,
    'remitente_email' => 'facturacion@agrosander.com',
    'remitente_nombre' => 'Agrosander Don Jorge S A S',
    'asunto' => 'Factura Electrónica FE-2024-001 - Agrosander',
    'contenido_texto' => 'Estimado cliente, adjuntamos la factura electrónica FE-2024-001...',
    'fecha_email' => now()->subHours(2),
    'fecha_descarga' => now(),
    'archivos_adjuntos' => [
        ['nombre' => 'FE-2024-001.xml', 'tamaño' => 15420, 'es_factura' => true],
        ['nombre' => 'FE-2024-001.pdf', 'tamaño' => 85630, 'es_factura' => false]
    ],
    'tiene_facturas' => true,
    'procesado' => true,
    'estado' => 'procesado',
    'metadatos' => [
        'tipo' => 'email_real',
        'cufe' => 'CUFE123456789AGROSANDER001',
        'tipo_documento' => 'factura_venta',
        'proveedor_autorizado' => [
            'id' => 1,
            'nombre' => 'Agrosander Don Jorge S A S',
            'nit' => 'JRME130551'
        ]
    ]
]);

// Email 2: De World Office con factura
$email2 = EmailBuzon::create([
    'empresa_id' => $empresa->id,
    'mensaje_id' => 'PRUEBA_002_' . time(),
    'cuenta_email' => $config->email_dian,
    'remitente_email' => 'no-responder@worldoffice.com.co',
    'remitente_nombre' => 'World Office Colombia',
    'asunto' => 'Documento Electrónico - Factura WO-2024-0156',
    'contenido_texto' => 'Cordial saludo, le enviamos su documento electrónico...',
    'fecha_email' => now()->subHours(5),
    'fecha_descarga' => now(),
    'archivos_adjuntos' => [
        ['nombre' => 'WO-2024-0156.zip', 'tamaño' => 45230, 'es_factura' => true]
    ],
    'tiene_facturas' => true,
    'procesado' => false,
    'estado' => 'nuevo',
    'metadatos' => [
        'tipo' => 'email_real',
        'cufe' => 'CUFE789456123WORLDOFFICE156',
        'tipo_documento' => 'factura_venta',
        'proveedor_autorizado' => [
            'id' => 5,
            'nombre' => 'World Office',
            'nit' => 'WO123456789'
        ]
    ]
]);

// Email 3: De Automatafe procesando
$email3 = EmailBuzon::create([
    'empresa_id' => $empresa->id,
    'mensaje_id' => 'PRUEBA_003_' . time(),
    'cuenta_email' => $config->email_dian,
    'remitente_email' => 'facturacion@automatafe.com',
    'remitente_nombre' => 'Automatafe Ltda',
    'asunto' => 'Factura Electrónica AT-2024-789',
    'contenido_texto' => 'Estimado cliente, le enviamos la factura electrónica...',
    'fecha_email' => now()->subHours(1),
    'fecha_descarga' => now(),
    'archivos_adjuntos' => [
        ['nombre' => 'AT-2024-789.xml', 'tamaño' => 12850, 'es_factura' => true]
    ],
    'tiene_facturas' => true,
    'procesado' => false,
    'estado' => 'procesando',
    'metadatos' => [
        'tipo' => 'email_real',
        'cufe' => 'CUFE456789123AUTOMATAFE789',
        'tipo_documento' => 'factura_venta',
        'proveedor_autorizado' => [
            'id' => 2,
            'nombre' => 'Automatafe',
            'nit' => 'AT987654321'
        ]
    ]
]);

// Email 4: De Equiredes con error
$email4 = EmailBuzon::create([
    'empresa_id' => $empresa->id,
    'mensaje_id' => 'PRUEBA_004_' . time(),
    'cuenta_email' => $config->email_dian,
    'remitente_email' => 'facturacion@equiredes.com',
    'remitente_nombre' => 'Equiredes Soluciones Integrales',
    'asunto' => 'Factura Electrónica EQ-2024-456',
    'contenido_texto' => 'Adjunto encontrará la factura electrónica...',
    'fecha_email' => now()->subDays(1),
    'fecha_descarga' => now(),
    'archivos_adjuntos' => [
        ['nombre' => 'EQ-2024-456.rar', 'tamaño' => 25630, 'es_factura' => true]
    ],
    'tiene_facturas' => true,
    'procesado' => false,
    'estado' => 'error',
    'metadatos' => [
        'tipo' => 'email_real',
        'error' => 'Archivo corrupto - no se pudo extraer',
        'proveedor_autorizado' => [
            'id' => 3,
            'nombre' => 'Equiredes Soluciones',
            'nit' => 'EQ456789123'
        ]
    ]
]);

// Email 5: De hoy sin facturas
$email5 = EmailBuzon::create([
    'empresa_id' => $empresa->id,
    'mensaje_id' => 'PRUEBA_005_' . time(),
    'cuenta_email' => $config->email_dian,
    'remitente_email' => 'info@colcomercio.com.co',
    'remitente_nombre' => 'Colcomercio',
    'asunto' => 'Información comercial - Nuevos productos',
    'contenido_texto' => 'Le informamos sobre nuestros nuevos productos...',
    'fecha_email' => now()->subMinutes(30),
    'fecha_descarga' => now(),
    'archivos_adjuntos' => [
        ['nombre' => 'catalogo.pdf', 'tamaño' => 1250000, 'es_factura' => false]
    ],
    'tiene_facturas' => false,
    'procesado' => true,
    'estado' => 'procesado',
    'metadatos' => [
        'tipo' => 'email_real',
        'nota' => 'Email comercial sin facturas',
        'proveedor_autorizado' => [
            'id' => 4,
            'nombre' => 'Colcomercio',
            'nit' => 'CC123456789'
        ]
    ]
]);

echo "✅ Emails de prueba creados:\n";
echo "📧 Email 1: Agrosander - Procesado con factura\n";
echo "📧 Email 2: World Office - Nuevo con factura\n";
echo "📧 Email 3: Automatafe - Procesando con factura\n";
echo "📧 Email 4: Equiredes - Error con factura\n";
echo "📧 Email 5: Colcomercio - Procesado sin factura\n\n";

echo "📊 ESTADÍSTICAS ACTUALIZADAS:\n";
echo "=============================\n";
$total = EmailBuzon::where('empresa_id', $empresa->id)->count();
$conFacturas = EmailBuzon::where('empresa_id', $empresa->id)->where('tiene_facturas', true)->count();
$hoy = EmailBuzon::where('empresa_id', $empresa->id)->whereDate('fecha_email', today())->count();

echo "📧 Total emails: $total\n";
echo "💼 Con facturas: $conFacturas\n";
echo "📅 De hoy: $hoy\n\n";

echo "🌐 AHORA PUEDES ACCEDER A:\n";
echo "==========================\n";
echo "📊 Dashboard: http://127.0.0.1:8000/dian\n";
echo "📧 Buzón: http://127.0.0.1:8000/dian/buzon\n\n";

echo "🎯 FILTROS PARA PROBAR:\n";
echo "=======================\n";
echo "🔹 Estado: Nuevo (1), Procesando (1), Procesado (2), Error (1)\n";
echo "🔹 Con facturas: Sí (4), No (1)\n";
echo "🔹 Proveedores: Agrosander, World Office, Automatafe, Equiredes, Colcomercio\n";
echo "🔹 Fechas: Hoy, ayer, hace 2 horas, etc.\n\n";

echo "🏁 Emails de prueba agregados exitosamente\n";
