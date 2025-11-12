<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Setting;

echo "📧 VERIFICANDO CONFIGURACIÓN DE EMAIL PARA BACKUP\n";
echo "=================================================\n\n";

// Verificar configuración actual
$backupEmailSetting = Setting::where('key', 'backup_email')->first();

echo "🔍 CONFIGURACIÓN ACTUAL:\n";
echo "========================\n";
if ($backupEmailSetting) {
    echo "✅ Configuración encontrada\n";
    echo "📧 Email configurado: " . $backupEmailSetting->value . "\n";
    echo "📅 Creado: " . $backupEmailSetting->created_at . "\n";
    echo "📅 Actualizado: " . $backupEmailSetting->updated_at . "\n";
} else {
    echo "❌ No hay configuración de backup_email\n";
}

echo "\n📋 TODAS LAS CONFIGURACIONES:\n";
echo "=============================\n";
$allSettings = Setting::all();
foreach ($allSettings as $setting) {
    echo "🔧 {$setting->key}: {$setting->value}\n";
}

echo "\n🎯 PROBLEMA IDENTIFICADO:\n";
echo "=========================\n";
if ($backupEmailSetting && $backupEmailSetting->value === 'interveredanet.cr@gmail.com') {
    echo "⚠️ El email cambió de pcapacho24@gmail.com a interveredanet.cr@gmail.com\n";
    echo "📝 Esto puede haber pasado por:\n";
    echo "   1. Actualización manual en la base de datos\n";
    echo "   2. Cambio en configuración del sistema\n";
    echo "   3. Migración o seeder que actualizó el valor\n";
}

echo "\n🔧 SOLUCIÓN:\n";
echo "============\n";
echo "¿Quieres restaurar el email original? (s/n): ";
$handle = fopen("php://stdin", "r");
$respuesta = trim(fgets($handle));
fclose($handle);

if (strtolower($respuesta) === 's' || strtolower($respuesta) === 'si') {
    if ($backupEmailSetting) {
        $backupEmailSetting->value = 'pcapacho24@gmail.com';
        $backupEmailSetting->save();
        echo "✅ Email restaurado a: pcapacho24@gmail.com\n";
    } else {
        Setting::create([
            'key' => 'backup_email',
            'value' => 'pcapacho24@gmail.com'
        ]);
        echo "✅ Configuración creada con: pcapacho24@gmail.com\n";
    }
    
    echo "\n🧪 PROBANDO BACKUP CON EMAIL CORREGIDO:\n";
    echo "=======================================\n";
    echo "Ejecuta: php artisan backup:database --send-email\n";
} else {
    echo "❌ No se realizaron cambios\n";
}

echo "\n📧 CONFIGURACIÓN SMTP:\n";
echo "=====================\n";
echo "El sistema de backup usa la misma configuración SMTP que los acuses:\n";
echo "• MAIL_MAILER: " . config('mail.default') . "\n";
echo "• MAIL_HOST: " . config('mail.mailers.smtp.host', 'N/A') . "\n";
echo "• MAIL_FROM: " . config('mail.from.address', 'N/A') . "\n";

echo "\n🏁 Verificación completada\n";
