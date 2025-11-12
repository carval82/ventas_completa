<?php
echo "📧 CONFIGURACIÓN DE SENDGRID PARA LARAVEL\n";
echo "=========================================\n\n";

echo "🔧 PASOS PARA CONFIGURAR SENDGRID:\n";
echo "==================================\n\n";

echo "1. 📝 CREAR CUENTA EN SENDGRID:\n";
echo "===============================\n";
echo "• Ve a: https://sendgrid.com\n";
echo "• Regístrate gratis (100 emails/día)\n";
echo "• Verifica tu email\n";
echo "• Completa la configuración inicial\n\n";

echo "2. 🔑 CREAR API KEY:\n";
echo "===================\n";
echo "• Ve a Settings > API Keys\n";
echo "• Click 'Create API Key'\n";
echo "• Nombre: 'Laravel Sistema DIAN'\n";
echo "• Permisos: Full Access (o Mail Send)\n";
echo "• Copia la API Key (solo se muestra una vez)\n\n";

echo "3. ✉️ VERIFICAR SENDER IDENTITY:\n";
echo "===============================\n";
echo "• Ve a Settings > Sender Authentication\n";
echo "• Click 'Verify a Single Sender'\n";
echo "• Email: interveredanet.cr@gmail.com\n";
echo "• Nombre: Sistema DIAN\n";
echo "• Verifica el email\n\n";

echo "4. 🔧 CONFIGURAR .ENV:\n";
echo "=====================\n";
echo "Agrega estas líneas a tu archivo .env:\n\n";
echo "# SendGrid Configuration\n";
echo "MAIL_MAILER=smtp\n";
echo "MAIL_HOST=smtp.sendgrid.net\n";
echo "MAIL_PORT=587\n";
echo "MAIL_USERNAME=apikey\n";
echo "MAIL_PASSWORD=TU_API_KEY_AQUI\n";
echo "MAIL_ENCRYPTION=tls\n";
echo "MAIL_FROM_ADDRESS=interveredanet.cr@gmail.com\n";
echo "MAIL_FROM_NAME=\"Sistema DIAN\"\n\n";

echo "5. 📋 EJEMPLO DE API KEY:\n";
echo "========================\n";
echo "MAIL_PASSWORD=SG.abc123def456ghi789jkl012mno345pqr678stu901vwx234yz\n\n";

echo "⚠️ IMPORTANTE:\n";
echo "==============\n";
echo "• La API Key debe empezar con 'SG.'\n";
echo "• MAIL_USERNAME siempre es 'apikey'\n";
echo "• El email FROM debe estar verificado en SendGrid\n";
echo "• Guarda la API Key en lugar seguro\n\n";

echo "🧪 DESPUÉS DE CONFIGURAR:\n";
echo "=========================\n";
echo "1. Ejecuta: php artisan config:clear\n";
echo "2. Prueba con: php test_sendgrid.php\n";
echo "3. Envía backup real: php artisan backup:database --send-email\n";
echo "4. Prueba acuses DIAN desde el dashboard\n\n";

echo "📊 LÍMITES GRATUITOS:\n";
echo "=====================\n";
echo "• 100 emails por día\n";
echo "• Sin límite de destinatarios\n";
echo "• Estadísticas básicas\n";
echo "• Soporte por email\n\n";

echo "💰 PLANES PAGOS:\n";
echo "================\n";
echo "• Essentials: $19.95/mes (50,000 emails)\n";
echo "• Pro: $89.95/mes (100,000 emails)\n";
echo "• Premier: $399/mes (1,200,000 emails)\n\n";

echo "🎯 VENTAJAS DE SENDGRID:\n";
echo "========================\n";
echo "✅ Alta deliverability (99%+)\n";
echo "✅ Sin problemas de autenticación\n";
echo "✅ Estadísticas detalladas\n";
echo "✅ Manejo de bounces automático\n";
echo "✅ Templates profesionales\n";
echo "✅ APIs robustas\n";
echo "✅ Soporte 24/7\n\n";

echo "🔗 ENLACES ÚTILES:\n";
echo "==================\n";
echo "• Registro: https://sendgrid.com/free\n";
echo "• Documentación: https://docs.sendgrid.com\n";
echo "• Laravel Integration: https://docs.sendgrid.com/for-developers/sending-email/laravel\n\n";

echo "🏁 Configuración de SendGrid completada\n";
echo "Sigue los pasos arriba para obtener tu API Key\n";
