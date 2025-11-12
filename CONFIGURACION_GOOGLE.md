# 🔧 CONFIGURACIÓN GOOGLE GMAIL API

## 📋 VARIABLES A AGREGAR EN .env

Agrega estas líneas al final de tu archivo `.env`:

```env
# Google Gmail API Configuration
GOOGLE_CLIENT_ID=tu_client_id_aqui
GOOGLE_CLIENT_SECRET=tu_client_secret_aqui
GOOGLE_PROJECT_ID=sistema-ventas-dian
```

## 🎯 EJEMPLO COMPLETO:

```env
# Google Gmail API Configuration
GOOGLE_CLIENT_ID=123456789-abcdefghijklmnop.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-abcdefghijklmnopqrstuvwxyz
GOOGLE_PROJECT_ID=sistema-ventas-dian
```

## ✅ PASOS COMPLETADOS:

1. ✅ Crear proyecto en Google Console
2. ✅ Habilitar Gmail API  
3. ✅ Configurar pantalla de consentimiento OAuth
4. ✅ Crear credenciales OAuth2
5. ✅ Configurar URIs de redirección:
   - http://127.0.0.1:8000/dian/oauth/callback
   - http://localhost:8000/dian/oauth/callback

## 🚀 DESPUÉS DE CONFIGURAR:

1. Reinicia el servidor Laravel: `php artisan serve`
2. Ve a: http://127.0.0.1:8000/dian
3. Click "Procesar Emails"
4. Te redirigirá a Google para autorizar
5. ¡Procesamiento REAL de emails funcionando!

## 🔍 VERIFICAR CONFIGURACIÓN:

Ejecuta este comando para verificar que las variables están configuradas:
```bash
php artisan tinker
>>> env('GOOGLE_CLIENT_ID')
>>> env('GOOGLE_CLIENT_SECRET')
```

## 📧 EMAILS QUE PROCESARÁ:

- ✅ Emails con archivos adjuntos
- ✅ Asunto contenga: factura, invoice, CUFE, FE, NC, ND
- ✅ Archivos XML, ZIP, RAR
- ✅ Últimos 30 días
- ✅ Máximo 50 emails por procesamiento
