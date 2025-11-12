<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ConfiguracionDian;

$config = ConfiguracionDian::where('activo', true)->first();
if ($config) {
    $config->update(['password_email' => 'jiiy yxnu itis xjru']);
    echo "✅ Contraseña de aplicación configurada correctamente\n";
    echo "Email: " . $config->email_dian . "\n";
    echo "Nueva contraseña: " . $config->password_email . "\n";
} else {
    echo "❌ No se encontró configuración activa\n";
}
