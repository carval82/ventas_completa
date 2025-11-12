# ☁️ IMPLEMENTACIÓN EN AMAZON WEB SERVICES (AWS)

## 🎯 ARQUITECTURA RECOMENDADA

```
┌─────────────────────────────────────────────────────────────┐
│                        AWS CLOUD                           │
├─────────────────────────────────────────────────────────────┤
│  Route 53 (DNS)                                            │
│  ├── tuempresa.com                                         │
│  ├── *.tuempresa.com (subdominios)                         │
│  └── empresa1.tuempresa.com, empresa2.tuempresa.com        │
├─────────────────────────────────────────────────────────────┤
│  CloudFront (CDN) + SSL Certificate                        │
├─────────────────────────────────────────────────────────────┤
│  Application Load Balancer (ALB)                           │
├─────────────────────────────────────────────────────────────┤
│  EC2 Instance (Ubuntu 22.04)                              │
│  ├── Nginx + PHP 8.2 + Laravel                            │
│  ├── Sistema Multi-Tenant                                  │
│  └── Auto Scaling Group (opcional)                         │
├─────────────────────────────────────────────────────────────┤
│  RDS MySQL (Multi-AZ)                                      │
│  ├── Base de datos principal (tenants)                     │
│  ├── ventas_empresa1                                       │
│  ├── ventas_empresa2                                       │
│  └── ventas_empresaN                                       │
├─────────────────────────────────────────────────────────────┤
│  S3 Bucket                                                 │
│  ├── Backups automáticos                                   │
│  ├── Archivos estáticos                                    │
│  └── Logs del sistema                                      │
└─────────────────────────────────────────────────────────────┘
```

## 📋 PASO 1: CONFIGURAR CUENTA AWS

### 1.1 Crear Cuenta AWS
```
1. Ir a: https://aws.amazon.com/
2. Crear cuenta (necesitas tarjeta de crédito)
3. Verificar identidad
4. Seleccionar plan: Basic Support (gratis)
```

### 1.2 Configurar Billing Alerts
```
1. CloudWatch → Billing → Create Alarm
2. Configurar alerta cuando gasto > $50/mes
3. Esto evita sorpresas en la factura
```

### 1.3 Crear Usuario IAM
```
1. IAM → Users → Add User
2. Nombre: "deploy-user"
3. Permisos: EC2FullAccess, RDSFullAccess, S3FullAccess
4. Descargar credenciales CSV
```

## 📋 PASO 2: CONFIGURAR EC2 (SERVIDOR)

### 2.1 Lanzar Instancia EC2
```
1. EC2 Dashboard → Launch Instance
2. Configuración:
   - Name: "ventas-multitenant-server"
   - AMI: Ubuntu Server 22.04 LTS
   - Instance Type: t3.medium (2 vCPU, 4GB RAM)
   - Key Pair: Crear nuevo "ventas-keypair.pem"
   - Security Group: Crear "ventas-sg"
     * SSH (22) - Tu IP
     * HTTP (80) - 0.0.0.0/0
     * HTTPS (443) - 0.0.0.0/0
     * MySQL (3306) - Security Group interno
   - Storage: 30GB gp3
```

### 2.2 Conectar a la Instancia
```bash
# Desde Windows (usar PuTTY o WSL)
ssh -i "ventas-keypair.pem" ubuntu@EC2_PUBLIC_IP

# Actualizar sistema
sudo apt update && sudo apt upgrade -y
```

### 2.3 Instalar Stack LEMP
```bash
# Instalar Nginx
sudo apt install -y nginx

# Instalar PHP 8.2
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2-fpm php8.2-mysql php8.2-xml php8.2-curl \
    php8.2-mbstring php8.2-zip php8.2-gd php8.2-intl php8.2-bcmath

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Instalar Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Instalar Git
sudo apt install -y git unzip
```

## 📋 PASO 3: CONFIGURAR RDS (BASE DE DATOS)

### 3.1 Crear RDS Instance
```
1. RDS Dashboard → Create Database
2. Configuración:
   - Engine: MySQL 8.0
   - Template: Production (o Dev/Test para ahorrar)
   - DB Instance: db.t3.micro (1 vCPU, 1GB RAM)
   - DB Name: "ventas_sistema"
   - Master Username: "admin"
   - Master Password: "TuPasswordSeguro123!"
   - VPC: Default
   - Security Group: Crear "rds-sg"
     * MySQL (3306) - desde EC2 Security Group
   - Initial Database: "ventas_sistema"
```

### 3.2 Configurar Security Groups
```bash
# Permitir conexión desde EC2 a RDS
aws ec2 authorize-security-group-ingress \
    --group-id sg-RDS_SECURITY_GROUP_ID \
    --protocol tcp \
    --port 3306 \
    --source-group sg-EC2_SECURITY_GROUP_ID
```

## 📋 PASO 4: SUBIR APLICACIÓN

### 4.1 Clonar Repositorio
```bash
# En el servidor EC2
cd /var/www
sudo git clone https://github.com/tu-usuario/ventas-sistema.git
sudo chown -R www-data:www-data ventas-sistema
cd ventas-sistema
```

### 4.2 Instalar Dependencias
```bash
# Instalar dependencias PHP
composer install --optimize-autoloader --no-dev

# Instalar dependencias Node.js
npm install
npm run build

# Configurar permisos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 4.3 Configurar .env
```bash
# Copiar archivo de configuración
cp .env.example .env

# Editar configuración
sudo nano .env
```

```env
APP_NAME="Sistema Ventas Multi-Tenant"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://tuempresa.com

DB_CONNECTION=mysql
DB_HOST=ventas-sistema.cluster-xxxxx.us-east-1.rds.amazonaws.com
DB_PORT=3306
DB_DATABASE=ventas_sistema
DB_USERNAME=admin
DB_PASSWORD=TuPasswordSeguro123!

# Configuración de correo (SES recomendado)
MAIL_MAILER=ses
MAIL_FROM_ADDRESS=noreply@tuempresa.com
MAIL_FROM_NAME="Sistema Ventas"

# Configuración de cache (ElastiCache opcional)
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database

# Configuración de archivos (S3)
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=tu_access_key
AWS_SECRET_ACCESS_KEY=tu_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=ventas-sistema-files
```

### 4.4 Generar APP_KEY
```bash
php artisan key:generate
```

## 📋 PASO 5: CONFIGURAR NGINX

### 5.1 Crear Virtual Host
```bash
sudo nano /etc/nginx/sites-available/ventas-sistema
```

```nginx
server {
    listen 80;
    server_name tuempresa.com *.tuempresa.com;
    root /var/www/ventas-sistema/public;
    index index.php index.html;

    # Logs
    access_log /var/log/nginx/ventas-access.log;
    error_log /var/log/nginx/ventas-error.log;

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Aumentar timeouts para operaciones largas
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
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
```

### 5.2 Activar Sitio
```bash
sudo ln -s /etc/nginx/sites-available/ventas-sistema /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## 📋 PASO 6: CONFIGURAR DOMINIO Y SSL

### 6.1 Configurar Route 53
```
1. Route 53 → Hosted Zones → Create Hosted Zone
2. Domain: tuempresa.com
3. Crear registros:
   - A Record: @ → EC2_PUBLIC_IP
   - A Record: www → EC2_PUBLIC_IP
   - A Record: * → EC2_PUBLIC_IP (wildcard para subdominios)
```

### 6.2 Instalar SSL con Let's Encrypt
```bash
# Instalar Certbot
sudo apt install -y certbot python3-certbot-nginx

# Generar certificado
sudo certbot --nginx -d tuempresa.com -d www.tuempresa.com -d *.tuempresa.com

# Configurar renovación automática
sudo crontab -e
# Agregar: 0 12 * * * /usr/bin/certbot renew --quiet
```

## 📋 PASO 7: EJECUTAR INSTALACIÓN MULTI-TENANT

### 7.1 Migrar Base de Datos Principal
```bash
cd /var/www/ventas-sistema
php artisan migrate --path=database/migrations/2025_01_01_000000_create_tenants_table.php
```

### 7.2 Configurar Migraciones Tenant
```bash
php artisan tenant:setup-migrations
```

### 7.3 Crear Empresa Demo
```bash
php artisan tinker
```

```php
$tenant = App\Models\Tenant::create([
    'slug' => 'demo',
    'nombre' => 'Empresa Demo',
    'nit' => '123456789-0',
    'email' => 'admin@demo.com',
    'telefono' => '555-0123',
    'direccion' => 'Calle Demo 123',
    'database_name' => 'ventas_demo',
    'database_host' => 'ventas-sistema.cluster-xxxxx.us-east-1.rds.amazonaws.com',
    'database_port' => '3306',
    'database_username' => 'admin',
    'database_password' => 'TuPasswordSeguro123!',
    'plan' => 'premium',
    'fecha_creacion' => now(),
]);

$tenant->crearBaseDatos();
exit
```

## 📋 PASO 8: CONFIGURAR S3 Y BACKUPS

### 8.1 Crear S3 Bucket
```
1. S3 → Create Bucket
2. Nombre: "ventas-sistema-backups-RANDOM"
3. Region: us-east-1
4. Block Public Access: Enabled
5. Versioning: Enabled
```

### 8.2 Script de Backup Automático
```bash
sudo nano /home/ubuntu/backup_databases.sh
```

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/tmp/backups"
S3_BUCKET="ventas-sistema-backups-RANDOM"

mkdir -p $BACKUP_DIR

# Backup base de datos principal
mysqldump -h ventas-sistema.cluster-xxxxx.us-east-1.rds.amazonaws.com \
    -u admin -pTuPasswordSeguro123! ventas_sistema > $BACKUP_DIR/sistema_$DATE.sql

# Backup empresas (obtener lista dinámicamente)
mysql -h ventas-sistema.cluster-xxxxx.us-east-1.rds.amazonaws.com \
    -u admin -pTuPasswordSeguro123! -e "SELECT database_name FROM ventas_sistema.tenants WHERE activo=1" \
    -s -N | while read db; do
    mysqldump -h ventas-sistema.cluster-xxxxx.us-east-1.rds.amazonaws.com \
        -u admin -pTuPasswordSeguro123! $db > $BACKUP_DIR/${db}_$DATE.sql
done

# Subir a S3
aws s3 sync $BACKUP_DIR s3://$S3_BUCKET/backups/$DATE/

# Limpiar archivos locales
rm -rf $BACKUP_DIR

echo "Backup completado: $DATE"
```

### 8.3 Programar Backups
```bash
sudo chmod +x /home/ubuntu/backup_databases.sh
crontab -e
# Agregar: 0 2 * * * /home/ubuntu/backup_databases.sh
```

## 📋 PASO 9: CONFIGURAR MONITOREO

### 9.1 CloudWatch Logs
```bash
# Instalar CloudWatch Agent
wget https://s3.amazonaws.com/amazoncloudwatch-agent/ubuntu/amd64/latest/amazon-cloudwatch-agent.deb
sudo dpkg -i amazon-cloudwatch-agent.deb

# Configurar logs
sudo nano /opt/aws/amazon-cloudwatch-agent/etc/amazon-cloudwatch-agent.json
```

### 9.2 Configurar Alertas
```
1. CloudWatch → Alarms → Create Alarm
2. Métricas a monitorear:
   - CPU Utilization > 80%
   - Memory Utilization > 85%
   - Disk Space < 20%
   - HTTP 5xx errors > 10/min
```

## 📋 PASO 10: OPTIMIZACIÓN Y SEGURIDAD

### 10.1 Configurar Auto Scaling (Opcional)
```
1. EC2 → Auto Scaling Groups → Create
2. Launch Template basado en tu instancia
3. Min: 1, Max: 3, Desired: 1
4. Target Group para Load Balancer
```

### 10.2 Configurar WAF (Web Application Firewall)
```
1. WAF & Shield → Web ACLs → Create
2. Reglas recomendadas:
   - AWS Managed Rules - Core Rule Set
   - AWS Managed Rules - SQL Injection
   - Rate limiting: 2000 requests/5min per IP
```

### 10.3 Configurar ElastiCache (Opcional)
```
1. ElastiCache → Redis → Create
2. Node Type: cache.t3.micro
3. Actualizar .env:
   CACHE_DRIVER=redis
   REDIS_HOST=tu-cluster.cache.amazonaws.com
```

## 🚀 URLS FINALES

```
🏠 Principal: https://tuempresa.com
🏢 Demo: https://tuempresa.com/empresa/demo/dashboard
🔧 Admin: https://tuempresa.com/admin/tenants
📝 Registro: https://tuempresa.com/registro
```

## 💰 COSTOS ESTIMADOS AWS (USD/mes)

### Configuración Básica:
```
💻 EC2 t3.medium: ~$30/mes
🗄️ RDS db.t3.micro: ~$15/mes
🌐 Route 53: ~$0.50/mes
📦 S3 (100GB): ~$2/mes
🔒 SSL Certificate: GRATIS
📊 CloudWatch: ~$5/mes
─────────────────────────
💰 TOTAL: ~$52/mes
```

### Configuración Escalada:
```
💻 EC2 t3.large + Auto Scaling: ~$80/mes
🗄️ RDS db.t3.small Multi-AZ: ~$35/mes
⚡ ElastiCache: ~$15/mes
🛡️ WAF: ~$10/mes
📊 CloudWatch + Logs: ~$15/mes
─────────────────────────
💰 TOTAL: ~$155/mes
```

## 🎯 VENTAJAS DE AWS

### ✅ Escalabilidad:
- Auto Scaling automático
- Load Balancer distribuye carga
- RDS Multi-AZ para alta disponibilidad

### ✅ Seguridad:
- WAF protege contra ataques
- VPC aísla recursos
- IAM controla accesos

### ✅ Confiabilidad:
- 99.99% uptime SLA
- Backups automáticos
- Disaster recovery

### ✅ Global:
- CDN CloudFront mundial
- Múltiples regiones
- Baja latencia

¡Sistema multi-tenant listo para escalar globalmente! 🌍
