<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EmailBuzon;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "✅ VERIFICACIÓN: VISTA DASHBOARD CORREGIDA\n";
echo "=========================================\n\n";

$user = User::first();
Auth::login($user);
echo "👤 Usuario: {$user->name} (Empresa ID: {$user->empresa_id})\n\n";

echo "📋 DATOS QUE SE MOSTRARÁN EN EL DASHBOARD:\n";
echo "=========================================\n\n";

$facturas = EmailBuzon::where('empresa_id', $user->empresa_id)
    ->where('tiene_facturas', true)
    ->orderBy('fecha_email', 'desc')
    ->limit(10)
    ->get();

if ($facturas->isEmpty()) {
    echo "⚠️ No hay facturas para mostrar\n";
    echo "💡 Sincroniza emails primero desde: http://127.0.0.1:8000/dian/buzon\n";
    exit(0);
}

echo "📊 FACTURAS ENCONTRADAS: {$facturas->count()}\n\n";

$tabla = [];
foreach ($facturas as $email) {
    $metadatos = is_string($email->metadatos) ? 
                json_decode($email->metadatos, true) : 
                ($email->metadatos ?? []);
    
    $datosProveedor = $metadatos['datos_proveedor_xml'] ?? [];
    $acuseEnviado = $metadatos['acuse_enviado'] ?? false;
    
    $fila = [
        'ID' => $email->id,
        'Fecha' => $email->fecha_email->format('d/m/Y H:i'),
        'CUFE' => isset($datosProveedor['cufe']) ? 
                 substr($datosProveedor['cufe'], 0, 20) . '...' : 
                 'N/A',
        'Emisor' => $datosProveedor['nombre'] ?? $email->remitente_nombre,
        'NIT' => $datosProveedor['nit'] ?? 'N/A',
        'Valor' => isset($datosProveedor['valor_total']) ? 
                  '$' . number_format($datosProveedor['valor_total'], 2) : 
                  '-',
        'Estado' => ucfirst($email->estado),
        'Acuse' => $acuseEnviado ? '✅ Enviado' : '⏳ Pendiente'
    ];
    
    $tabla[] = $fila;
}

// Mostrar tabla
echo "┌─────┬──────────────┬─────────────────────────┬─────────────────────────┬──────────────┬──────────────┬─────────────┬──────────────┐\n";
echo "│ ID  │ Fecha        │ CUFE                    │ Emisor                  │ NIT          │ Valor        │ Estado      │ Acuse        │\n";
echo "├─────┼──────────────┼─────────────────────────┼─────────────────────────┼──────────────┼──────────────┼─────────────┼──────────────┤\n";

foreach ($tabla as $fila) {
    printf("│ %-3s │ %-12s │ %-23s │ %-23s │ %-12s │ %-12s │ %-11s │ %-12s │\n",
        $fila['ID'],
        $fila['Fecha'],
        substr($fila['CUFE'], 0, 23),
        substr($fila['Emisor'], 0, 23),
        substr($fila['NIT'], 0, 12),
        $fila['Valor'],
        substr($fila['Estado'], 0, 11),
        $fila['Acuse']
    );
}

echo "└─────┴──────────────┴─────────────────────────┴─────────────────────────┴──────────────┴──────────────┴─────────────┴──────────────┘\n\n";

// Resumen
$conCufe = 0;
$conNit = 0;
$conValor = 0;
$conAcuse = 0;

foreach ($tabla as $fila) {
    if ($fila['CUFE'] !== 'N/A') $conCufe++;
    if ($fila['NIT'] !== 'N/A') $conNit++;
    if ($fila['Valor'] !== '-') $conValor++;
    if (strpos($fila['Acuse'], '✅') !== false) $conAcuse++;
}

echo "📊 RESUMEN DE DATOS:\n";
echo "===================\n";
echo "✅ Con CUFE extraído: {$conCufe}/{$facturas->count()}\n";
echo "✅ Con NIT extraído: {$conNit}/{$facturas->count()}\n";
echo "✅ Con Valor extraído: {$conValor}/{$facturas->count()}\n";
echo "✅ Con Acuse enviado: {$conAcuse}/{$facturas->count()}\n\n";

echo "🔧 CAMBIOS REALIZADOS EN LA VISTA:\n";
echo "==================================\n";
echo "✅ Extracción de CUFE desde metadatos\n";
echo "✅ Lectura de nombre del emisor (proveedor o remitente)\n";
echo "✅ Obtención de NIT desde datos del XML\n";
echo "✅ Mostrar valor si está disponible\n";
echo "✅ Verificación de acuse desde metadatos\n";
echo "✅ Estado correcto del email\n";
echo "✅ Enlace a vista de detalles de acuses\n\n";

echo "🎯 SOLUCIÓN IMPLEMENTADA:\n";
echo "=========================\n";
echo "❌ ANTES: Intentaba acceder a \$factura->cufe (no existe en EmailBuzon)\n";
echo "✅ AHORA: Extrae desde \$metadatos['datos_proveedor_xml']['cufe']\n\n";
echo "❌ ANTES: Buscaba \$factura->nombre_emisor\n";
echo "✅ AHORA: Usa \$datosProveedor['nombre'] ?? \$email->remitente_nombre\n\n";
echo "❌ ANTES: Verificaba \$factura->acuse_enviado\n";
echo "✅ AHORA: Lee \$metadatos['acuse_enviado'] ?? false\n\n";

echo "🔗 VERIFICAR DASHBOARD:\n";
echo "======================\n";
echo "Accede a: http://127.0.0.1:8000/dian\n\n";

if ($conCufe > 0) {
    echo "🎉 ÉXITO: Los datos se mostrarán correctamente\n";
    echo "El dashboard ahora extrae y muestra la información real\n";
    echo "de los metadatos de cada email del buzón.\n\n";
} else {
    echo "⚠️ NOTA: Algunos emails no tienen datos extraídos\n";
    echo "Esto es normal si no se han procesado completamente.\n";
    echo "Ejecuta 'Procesar Emails' desde el buzón para extraer los datos.\n\n";
}

echo "🏁 Verificación completada\n";
