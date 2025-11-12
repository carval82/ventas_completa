<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Config;

echo "📧 CONFIGURANDO ENVÍO REAL DE EMAILS\n";
echo "====================================\n\n";

echo "📋 CONFIGURACIÓN ACTUAL:\n";
echo "========================\n";
echo "MAIL_MAILER: " . env('MAIL_MAILER', 'log') . "\n";
echo "MAIL_HOST: " . env('MAIL_HOST', 'N/A') . "\n";
echo "MAIL_PORT: " . env('MAIL_PORT', 'N/A') . "\n";
echo "MAIL_USERNAME: " . env('MAIL_USERNAME', 'N/A') . "\n";
echo "MAIL_FROM_ADDRESS: " . env('MAIL_FROM_ADDRESS', 'N/A') . "\n\n";

echo "🔧 CONFIGURACIONES RECOMENDADAS PARA GMAIL:\n";
echo "===========================================\n";
echo "Para enviar emails reales desde Gmail, necesitas:\n\n";

echo "1. 📧 CONFIGURACIÓN GMAIL SMTP:\n";
echo "   MAIL_MAILER=smtp\n";
echo "   MAIL_HOST=smtp.gmail.com\n";
echo "   MAIL_PORT=587\n";
echo "   MAIL_USERNAME=tu-email@gmail.com\n";
echo "   MAIL_PASSWORD=tu-app-password\n";
echo "   MAIL_ENCRYPTION=tls\n";
echo "   MAIL_FROM_ADDRESS=tu-email@gmail.com\n";
echo "   MAIL_FROM_NAME=\"Sistema DIAN\"\n\n";

echo "2. 🔐 GENERAR APP PASSWORD EN GMAIL:\n";
echo "   - Ve a tu cuenta de Google\n";
echo "   - Seguridad > Verificación en 2 pasos\n";
echo "   - Contraseñas de aplicaciones\n";
echo "   - Genera una contraseña para 'Laravel DIAN'\n";
echo "   - Usa esa contraseña en MAIL_PASSWORD\n\n";

echo "3. 📧 CONFIGURACIÓN ALTERNATIVA CON MAILTRAP (TESTING):\n";
echo "   MAIL_MAILER=smtp\n";
echo "   MAIL_HOST=sandbox.smtp.mailtrap.io\n";
echo "   MAIL_PORT=2525\n";
echo "   MAIL_USERNAME=tu-mailtrap-username\n";
echo "   MAIL_PASSWORD=tu-mailtrap-password\n";
echo "   MAIL_ENCRYPTION=tls\n";
echo "   MAIL_FROM_ADDRESS=noreply@dian.local\n";
echo "   MAIL_FROM_NAME=\"Sistema DIAN\"\n\n";

echo "🛠️ PASOS PARA CONFIGURAR:\n";
echo "=========================\n";
echo "1. Edita el archivo .env en la raíz del proyecto\n";
echo "2. Cambia las variables MAIL_* según tu proveedor\n";
echo "3. Reinicia el servidor si está corriendo\n";
echo "4. Ejecuta el test de envío real\n\n";

echo "⚠️ IMPORTANTE:\n";
echo "==============\n";
echo "- Nunca subas credenciales reales al repositorio\n";
echo "- Usa variables de entorno para datos sensibles\n";
echo "- Para producción, considera usar servicios como SendGrid, Mailgun, etc.\n";
echo "- Para testing, Mailtrap es ideal para capturar emails sin enviarlos realmente\n\n";

// Verificar si podemos escribir en .env
$envPath = base_path('.env');
if (file_exists($envPath) && is_writable($envPath)) {
    echo "✅ Archivo .env encontrado y escribible\n";
    echo "📁 Ubicación: $envPath\n\n";
    
    echo "🔧 ¿QUIERES CONFIGURAR MAILTRAP AUTOMÁTICAMENTE? (s/n): ";
    $handle = fopen("php://stdin", "r");
    $respuesta = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($respuesta) === 's' || strtolower($respuesta) === 'si') {
        echo "\n📧 CONFIGURANDO MAILTRAP...\n";
        
        echo "Ingresa tu Mailtrap Username: ";
        $handle = fopen("php://stdin", "r");
        $username = trim(fgets($handle));
        fclose($handle);
        
        echo "Ingresa tu Mailtrap Password: ";
        $handle = fopen("php://stdin", "r");
        $password = trim(fgets($handle));
        fclose($handle);
        
        if (!empty($username) && !empty($password)) {
            $envContent = file_get_contents($envPath);
            
            // Actualizar configuraciones de mail
            $envContent = preg_replace('/MAIL_MAILER=.*/', 'MAIL_MAILER=smtp', $envContent);
            $envContent = preg_replace('/MAIL_HOST=.*/', 'MAIL_HOST=sandbox.smtp.mailtrap.io', $envContent);
            $envContent = preg_replace('/MAIL_PORT=.*/', 'MAIL_PORT=2525', $envContent);
            $envContent = preg_replace('/MAIL_USERNAME=.*/', "MAIL_USERNAME=$username", $envContent);
            $envContent = preg_replace('/MAIL_PASSWORD=.*/', "MAIL_PASSWORD=$password", $envContent);
            $envContent = preg_replace('/MAIL_ENCRYPTION=.*/', 'MAIL_ENCRYPTION=tls', $envContent);
            $envContent = preg_replace('/MAIL_FROM_ADDRESS=.*/', 'MAIL_FROM_ADDRESS="noreply@dian.local"', $envContent);
            
            // Si no existen las variables, agregarlas
            if (!strpos($envContent, 'MAIL_MAILER=')) {
                $envContent .= "\nMAIL_MAILER=smtp";
            }
            if (!strpos($envContent, 'MAIL_HOST=')) {
                $envContent .= "\nMAIL_HOST=sandbox.smtp.mailtrap.io";
            }
            if (!strpos($envContent, 'MAIL_PORT=')) {
                $envContent .= "\nMAIL_PORT=2525";
            }
            if (!strpos($envContent, 'MAIL_USERNAME=')) {
                $envContent .= "\nMAIL_USERNAME=$username";
            }
            if (!strpos($envContent, 'MAIL_PASSWORD=')) {
                $envContent .= "\nMAIL_PASSWORD=$password";
            }
            if (!strpos($envContent, 'MAIL_ENCRYPTION=')) {
                $envContent .= "\nMAIL_ENCRYPTION=tls";
            }
            if (!strpos($envContent, 'MAIL_FROM_ADDRESS=')) {
                $envContent .= "\nMAIL_FROM_ADDRESS=\"noreply@dian.local\"";
            }
            if (!strpos($envContent, 'MAIL_FROM_NAME=')) {
                $envContent .= "\nMAIL_FROM_NAME=\"Sistema DIAN\"";
            }
            
            file_put_contents($envPath, $envContent);
            
            echo "✅ Configuración de Mailtrap guardada en .env\n";
            echo "🔄 Reinicia el servidor para aplicar cambios\n\n";
        } else {
            echo "❌ Credenciales no válidas, configuración manual requerida\n\n";
        }
    }
} else {
    echo "❌ No se puede escribir en .env, configuración manual requerida\n\n";
}

echo "🧪 PRÓXIMO PASO:\n";
echo "================\n";
echo "Ejecuta: php test_envio_real_final.php\n";
echo "Para probar el envío real de acuses\n\n";

echo "🏁 Configuración de email completada\n";
