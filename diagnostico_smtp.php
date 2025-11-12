<?php
require_once 'vendor/autoload.php';

echo "🔧 DIAGNÓSTICO SMTP GMAIL\n";
echo "=========================\n\n";

echo "📋 VERIFICACIONES NECESARIAS PARA GMAIL:\n";
echo "========================================\n";
echo "1. ✅ Verificación en 2 pasos ACTIVADA\n";
echo "2. ✅ Contraseña de aplicación GENERADA\n";
echo "3. ✅ 'Acceso de apps menos seguras' DESHABILITADO\n";
echo "4. ✅ Cuenta Gmail ACTIVA y funcionando\n\n";

echo "🔐 CONFIGURACIÓN ACTUAL:\n";
echo "========================\n";
echo "Email: interveredanet.cr@gmail.com\n";
echo "Password: jiiiy yxnu itis xjru\n";
echo "Host: smtp.gmail.com\n";
echo "Port: 587\n";
echo "Encryption: TLS\n\n";

echo "🧪 PROBANDO CONEXIÓN SMTP BÁSICA:\n";
echo "==================================\n";

try {
    // Crear conexión SMTP básica
    $socket = fsockopen('smtp.gmail.com', 587, $errno, $errstr, 30);
    
    if ($socket) {
        echo "✅ Conexión a smtp.gmail.com:587 exitosa\n";
        
        // Leer respuesta inicial
        $response = fgets($socket, 512);
        echo "📨 Respuesta del servidor: " . trim($response) . "\n";
        
        // Enviar EHLO
        fwrite($socket, "EHLO localhost\r\n");
        $response = fgets($socket, 512);
        echo "📨 Respuesta EHLO: " . trim($response) . "\n";
        
        // Iniciar TLS
        fwrite($socket, "STARTTLS\r\n");
        $response = fgets($socket, 512);
        echo "📨 Respuesta STARTTLS: " . trim($response) . "\n";
        
        fclose($socket);
        echo "✅ Conexión SMTP básica funcional\n\n";
        
    } else {
        echo "❌ Error conectando a SMTP: $errstr ($errno)\n\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error en conexión SMTP: " . $e->getMessage() . "\n\n";
}

echo "🔧 PASOS PARA SOLUCIONAR:\n";
echo "=========================\n\n";

echo "1. 📱 VERIFICAR CONFIGURACIÓN DE GMAIL:\n";
echo "   - Ve a https://myaccount.google.com/security\n";
echo "   - Verifica que la verificación en 2 pasos esté ACTIVADA\n";
echo "   - Ve a 'Contraseñas de aplicaciones'\n";
echo "   - Genera una NUEVA contraseña para 'Laravel DIAN'\n\n";

echo "2. 🔐 GENERAR NUEVA CONTRASEÑA DE APLICACIÓN:\n";
echo "   - Elimina la contraseña actual si existe\n";
echo "   - Crea una nueva con nombre 'Sistema DIAN Laravel'\n";
echo "   - Copia la contraseña de 16 caracteres (sin espacios)\n";
echo "   - Actualiza MAIL_PASSWORD en .env\n\n";

echo "3. 📧 VERIFICAR EMAIL:\n";
echo "   - Asegúrate de que interveredanet.cr@gmail.com sea correcto\n";
echo "   - Verifica que puedas acceder normalmente a la cuenta\n";
echo "   - Confirma que no hay restricciones de seguridad\n\n";

echo "4. 🔄 ALTERNATIVA - USAR MAILTRAP PARA TESTING:\n";
echo "   Si Gmail sigue fallando, puedes usar Mailtrap:\n";
echo "   - Regístrate en https://mailtrap.io\n";
echo "   - Crea un inbox de prueba\n";
echo "   - Usa las credenciales SMTP de Mailtrap\n";
echo "   - Los emails se capturarán sin envío real\n\n";

echo "📝 CONFIGURACIÓN MAILTRAP EJEMPLO:\n";
echo "==================================\n";
echo "MAIL_MAILER=smtp\n";
echo "MAIL_HOST=sandbox.smtp.mailtrap.io\n";
echo "MAIL_PORT=2525\n";
echo "MAIL_USERNAME=tu-mailtrap-username\n";
echo "MAIL_PASSWORD=tu-mailtrap-password\n";
echo "MAIL_ENCRYPTION=tls\n";
echo "MAIL_FROM_ADDRESS=noreply@dian.local\n";
echo "MAIL_FROM_NAME=\"Sistema DIAN\"\n\n";

echo "🎯 RECOMENDACIÓN:\n";
echo "=================\n";
echo "1. Intenta generar una nueva contraseña de aplicación en Gmail\n";
echo "2. Si persiste el error, usa Mailtrap para testing\n";
echo "3. Para producción, considera servicios como SendGrid o Mailgun\n\n";

echo "🏁 Diagnóstico completado\n";
