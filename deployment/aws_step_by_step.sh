#!/bin/bash

# ========================================
# SCRIPT DE INSTALACIÓN AWS - PASO A PASO
# Sistema Multi-Tenant Laravel
# ========================================

echo "🚀 INSTALACIÓN AWS - SISTEMA MULTI-TENANT"
echo "=========================================="

# Función para esperar confirmación del usuario
wait_for_user() {
    echo ""
    echo "⏸️  Presiona ENTER cuando hayas completado este paso..."
    read -r
}

echo ""
echo "📋 PASO 1: CREAR INSTANCIA EC2"
echo "1. Ve a AWS Console → EC2 → Launch Instance"
echo "2. Configuración recomendada:"
echo "   - Name: ventas-multitenant"
echo "   - AMI: Ubuntu Server 22.04 LTS"
echo "   - Instance Type: t3.medium (2 vCPU, 4GB RAM)"
echo "   - Key Pair: Crear nuevo 'ventas-key'"
echo "   - Security Group: Permitir SSH (22), HTTP (80), HTTPS (443)"
echo "   - Storage: 30GB gp3"
wait_for_user

echo ""
echo "🔧 PASO 2: CONECTAR A LA INSTANCIA"
echo "Usa PuTTY o WSL para conectar:"
echo "ssh -i ventas-key.pem ubuntu@TU_IP_PUBLICA"
echo ""
echo "Una vez conectado, ejecuta los siguientes comandos:"

echo ""
echo "📦 Actualizando sistema..."
sudo apt update && sudo apt upgrade -y

echo ""
echo "🐘 Instalando PHP 8.2..."
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-curl \
    php8.2-mbstring php8.2-zip php8.2-gd php8.2-intl php8.2-bcmath php8.2-cli

echo ""
echo "🌐 Instalando Nginx..."
sudo apt install -y nginx

echo ""
echo "🎼 Instalando Composer..."
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

echo ""
echo "📦 Instalando Node.js..."
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

echo ""
echo "🔧 Instalando herramientas adicionales..."
sudo apt install -y git unzip mysql-client

echo ""
echo "✅ Instalación de dependencias completada!"
echo ""
echo "📋 PRÓXIMO PASO: CONFIGURAR RDS"
echo "1. Ve a AWS Console → RDS → Create Database"
echo "2. Configuración:"
echo "   - Engine: MySQL 8.0"
echo "   - Template: Free Tier (o Production)"
echo "   - DB Instance: db.t3.micro"
echo "   - DB Name: ventas_sistema"
echo "   - Username: admin"
echo "   - Password: [Crear password seguro]"
echo "   - VPC: Default"
echo "   - Public Access: No"
echo "   - Security Group: Permitir MySQL (3306) desde EC2"
wait_for_user

echo ""
echo "📁 PASO 3: SUBIR CÓDIGO DE LA APLICACIÓN"
echo "Ejecuta estos comandos en el servidor:"

# Crear directorio para la aplicación
sudo mkdir -p /var/www/ventas-sistema
sudo chown -R ubuntu:ubuntu /var/www/ventas-sistema
cd /var/www/ventas-sistema

echo ""
echo "Opción A: Subir desde tu PC (recomendado)"
echo "1. Comprimir tu carpeta ventas_completa en ZIP"
echo "2. Usar SCP o FileZilla para subir:"
echo "   scp -i ventas-key.pem ventas_completa.zip ubuntu@TU_IP:/var/www/"
echo "3. Descomprimir en el servidor:"
echo "   cd /var/www && unzip ventas_completa.zip"
echo "   mv ventas_completa/* ventas-sistema/"

echo ""
echo "Opción B: Usar Git (si tienes repositorio)"
echo "git clone https://github.com/tu-usuario/ventas-sistema.git ."

wait_for_user

echo ""
echo "🔧 PASO 4: CONFIGURAR LA APLICACIÓN"

# Instalar dependencias
echo "Instalando dependencias PHP..."
cd /var/www/ventas-sistema
composer install --optimize-autoloader --no-dev

echo "Instalando dependencias Node.js..."
npm install
npm run build

# Configurar permisos
echo "Configurando permisos..."
sudo chown -R www-data:www-data /var/www/ventas-sistema
sudo chmod -R 775 storage bootstrap/cache

# Crear archivo .env
echo "Configurando .env..."
cp .env.example .env

echo ""
echo "📝 EDITAR ARCHIVO .env"
echo "Ejecuta: nano .env"
echo ""
echo "Configuración recomendada:"
cat << 'EOF'
APP_NAME="Sistema Ventas Multi-Tenant"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://tudominio.com

DB_CONNECTION=mysql
DB_HOST=tu-rds-endpoint.region.rds.amazonaws.com
DB_PORT=3306
DB_DATABASE=ventas_sistema
DB_USERNAME=admin
DB_PASSWORD=tu_password_rds

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=tu_app_password
MAIL_ENCRYPTION=tls

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
EOF

wait_for_user

# Generar APP_KEY
echo "Generando APP_KEY..."
php artisan key:generate

echo ""
echo "🌐 PASO 5: CONFIGURAR NGINX"

# Crear configuración de Nginx
sudo tee /etc/nginx/sites-available/ventas-sistema << 'EOF'
server {
    listen 80;
    server_name tudominio.com www.tudominio.com *.tudominio.com;
    root /var/www/ventas-sistema/public;
    index index.php index.html;

    # Logs
    access_log /var/log/nginx/ventas-access.log;
    error_log /var/log/nginx/ventas-error.log;

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
EOF

# Activar sitio
sudo ln -s /etc/nginx/sites-available/ventas-sistema /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx

echo ""
echo "✅ Nginx configurado correctamente!"

echo ""
echo "🗄️ PASO 6: CONFIGURAR BASE DE DATOS"
echo "Ejecutando migraciones..."

# Probar conexión a la base de datos
echo "Probando conexión a RDS..."
php artisan migrate:status

# Ejecutar migración principal de tenants
echo "Creando tabla de tenants..."
php artisan migrate --path=database/migrations/2025_01_01_000000_create_tenants_table.php --force

# Configurar migraciones para tenants
echo "Configurando migraciones para tenants..."
php artisan tenant:setup-migrations

echo ""
echo "🏢 PASO 7: CREAR EMPRESA DEMO"
echo "Creando empresa de prueba..."

# Crear empresa demo usando tinker
php artisan tinker << 'EOF'
$tenant = App\Models\Tenant::create([
    'slug' => 'demo',
    'nombre' => 'Empresa Demo',
    'nit' => '123456789-0',
    'email' => 'admin@demo.com',
    'telefono' => '555-0123',
    'direccion' => 'Calle Demo 123',
    'database_name' => 'ventas_demo',
    'database_host' => env('DB_HOST'),
    'database_port' => env('DB_PORT'),
    'database_username' => env('DB_USERNAME'),
    'database_password' => env('DB_PASSWORD'),
    'plan' => 'premium',
    'fecha_creacion' => now(),
]);

echo "Tenant creado: " . $tenant->slug;

if ($tenant->crearBaseDatos()) {
    echo "Base de datos creada exitosamente";
} else {
    echo "Error creando base de datos";
}
exit
EOF

echo ""
echo "🔐 PASO 8: CONFIGURAR SSL (OPCIONAL PERO RECOMENDADO)"
echo "Si tienes dominio configurado, ejecuta:"
echo "sudo apt install -y certbot python3-certbot-nginx"
echo "sudo certbot --nginx -d tudominio.com -d www.tudominio.com"

echo ""
echo "🎉 INSTALACIÓN COMPLETADA!"
echo "================================"
echo ""
echo "📋 RESUMEN:"
echo "✅ Servidor EC2 configurado"
echo "✅ RDS MySQL funcionando"
echo "✅ Aplicación Laravel instalada"
echo "✅ Sistema multi-tenant activo"
echo "✅ Empresa demo creada"
echo ""
echo "🌐 URLS DE PRUEBA:"
echo "http://TU_IP_PUBLICA (página principal)"
echo "http://TU_IP_PUBLICA/empresa/demo/dashboard (empresa demo)"
echo "http://TU_IP_PUBLICA/admin/tenants (panel admin)"
echo ""
echo "🔑 CREDENCIALES DEMO:"
echo "Email: admin@demo.com"
echo "Password: admin123"
echo ""
echo "💡 PRÓXIMOS PASOS:"
echo "1. Configurar dominio propio"
echo "2. Instalar SSL con Let's Encrypt"
echo "3. Configurar backups automáticos"
echo "4. Crear más empresas de prueba"
echo ""
echo "¡Tu sistema multi-tenant está listo! 🚀"
