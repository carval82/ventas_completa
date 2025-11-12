<?php
echo "📧 GUÍA PARA VERIFICAR EMAIL EN SENDGRID\n";
echo "========================================\n\n";

echo "🚨 PROBLEMA DETECTADO:\n";
echo "======================\n";
echo "El email 'interveredanet.cr@gmail.com' NO está verificado en SendGrid\n";
echo "Error: 550 The from address does not match a verified Sender Identity\n\n";

echo "🔧 SOLUCIÓN PASO A PASO:\n";
echo "========================\n\n";

echo "PASO 1: ACCEDER A SENDGRID\n";
echo "---------------------------\n";
echo "1. Ve a: https://app.sendgrid.com/login\n";
echo "2. Inicia sesión con tu cuenta\n\n";

echo "PASO 2: VERIFICAR SENDER IDENTITY\n";
echo "----------------------------------\n";
echo "1. En el menú izquierdo, ve a 'Settings'\n";
echo "2. Click en 'Sender Authentication'\n";
echo "3. En la sección 'Single Sender Verification', click 'Create New Sender'\n\n";

echo "PASO 3: COMPLETAR FORMULARIO\n";
echo "-----------------------------\n";
echo "Completa el formulario con estos datos:\n";
echo "• From Name: Sistema DIAN\n";
echo "• From Email Address: interveredanet.cr@gmail.com\n";
echo "• Reply To: interveredanet.cr@gmail.com\n";
echo "• Company Address: Tu dirección\n";
echo "• City: Tu ciudad\n";
echo "• State: Tu estado/departamento\n";
echo "• Zip Code: Tu código postal\n";
echo "• Country: Colombia\n";
echo "• Nickname: Sistema DIAN Principal\n\n";

echo "PASO 4: VERIFICAR EMAIL\n";
echo "------------------------\n";
echo "1. Click 'Create' para crear el sender\n";
echo "2. SendGrid enviará un email de verificación a: interveredanet.cr@gmail.com\n";
echo "3. Ve a la bandeja de entrada de Gmail\n";
echo "4. Busca el email de SendGrid (revisa spam si no lo ves)\n";
echo "5. Click en el enlace de verificación\n";
echo "6. Confirma la verificación\n\n";

echo "PASO 5: CONFIRMAR VERIFICACIÓN\n";
echo "-------------------------------\n";
echo "1. Regresa a SendGrid Dashboard\n";
echo "2. Ve a Settings > Sender Authentication\n";
echo "3. Verifica que el email aparezca como 'Verified' (con checkmark verde)\n\n";

echo "⚠️ ALTERNATIVA RÁPIDA:\n";
echo "======================\n";
echo "Si no tienes acceso a 'interveredanet.cr@gmail.com', puedes:\n";
echo "1. Usar tu propio email personal\n";
echo "2. Cambiar la configuración en el sistema\n\n";

echo "🔄 CAMBIAR EMAIL EN EL SISTEMA:\n";
echo "===============================\n";
echo "Si quieres usar otro email, ejecuta:\n";
echo "php cambiar_email_sistema.php tu_email@gmail.com\n\n";

echo "🧪 DESPUÉS DE VERIFICAR:\n";
echo "========================\n";
echo "1. Ejecuta: php test_sendgrid_directo.php\n";
echo "2. Ejecuta: php artisan backup:database --send-email\n";
echo "3. Verifica que los emails lleguen correctamente\n\n";

echo "📞 SOPORTE SENDGRID:\n";
echo "====================\n";
echo "Si tienes problemas:\n";
echo "• Documentación: https://docs.sendgrid.com\n";
echo "• Soporte: https://support.sendgrid.com\n";
echo "• Status: https://status.sendgrid.com\n\n";

echo "🎯 RESUMEN:\n";
echo "===========\n";
echo "El problema es simple: SendGrid requiere que verifiques\n";
echo "cualquier email que uses como remitente. Una vez verificado,\n";
echo "el sistema funcionará perfectamente.\n\n";

echo "🏁 Sigue estos pasos y el sistema estará listo!\n";
