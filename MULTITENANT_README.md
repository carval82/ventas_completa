# 🏢 Sistema Multi-Tenant - Documentación

## 📋 Descripción

Sistema de ventas multi-tenant que permite alojar múltiples empresas independientes en un solo servidor, cada una con su propia base de datos y configuración.

## 🏗️ Arquitectura

### **Base de Datos por Tenant**
```
🏢 Empresa A → DB: ventas_empresa_a
🏢 Empresa B → DB: ventas_empresa_b  
🏢 Empresa C → DB: ventas_empresa_c
```

### **Ventajas:**
- ✅ **Aislamiento total** de datos entre empresas
- ✅ **Seguridad máxima** - imposible acceso cruzado
- ✅ **Escalabilidad** independiente por empresa
- ✅ **Backups independientes**
- ✅ **Personalización** específica por empresa

## 🚀 Instalación

### **1. Ejecutar Script de Instalación**
```bash
install_multitenant.bat
```

### **2. Configurar Rutas (Manual)**
Reemplazar contenido de `routes/web.php` con `routes/web_multitenant.php`

### **3. Crear Super Admin**
```bash
php artisan tinker
```
```php
use App\Models\User;
$user = User::create([
    'name' => 'Super Admin',
    'email' => 'superadmin@tudominio.com',
    'password' => bcrypt('tu_password_seguro')
]);
$user->assignRole('super-admin');
```

## 🌐 URLs del Sistema

### **Empresa Demo (Creada Automáticamente)**
```
https://tudominio.com/empresa/demo/dashboard
Email: admin@demo.com
Password: admin123
```

### **Panel de Administración**
```
https://tudominio.com/admin/tenants
```

### **Registro de Nuevas Empresas**
```
https://tudominio.com/registro/
```

## 🔧 Gestión de Empresas

### **Crear Nueva Empresa (API)**
```bash
POST /admin/tenants/crear
```
```json
{
    "nombre": "Mi Empresa S.A.S",
    "nit": "123456789-0",
    "email": "admin@miempresa.com",
    "telefono": "555-0123",
    "direccion": "Calle Principal 123",
    "plan": "premium"
}
```

### **Planes Disponibles**
- **Básico**: 3 usuarios, 500 productos, 200 ventas/mes
- **Premium**: 10 usuarios, 2000 productos, 1000 ventas/mes  
- **Enterprise**: 50 usuarios, 10000 productos, 5000 ventas/mes

## 📊 Estructura de Archivos

```
app/
├── Models/
│   └── Tenant.php                    # Modelo principal de empresas
├── Http/
│   ├── Controllers/
│   │   └── TenantController.php      # Gestión de empresas
│   └── Middleware/
│       └── TenantMiddleware.php      # Identificación de tenant
└── Console/Commands/
    └── SetupTenantMigrations.php     # Setup de migraciones

database/
├── migrations/
│   ├── 2025_01_01_000000_create_tenants_table.php
│   └── tenant/                       # Migraciones por empresa
│       ├── [migraciones copiadas]
│       └── 2025_01_01_000001_tenant_specific_configurations.php

routes/
├── web.php                          # Rutas principales
├── web_multitenant.php             # Plantilla multi-tenant
└── tenant.php                      # Rutas específicas de empresa

resources/views/
└── admin/
    └── tenants/
        └── index.blade.php         # Panel de administración
```

## 🔄 Flujo de Funcionamiento

### **1. Identificación de Tenant**
El middleware `TenantMiddleware` identifica la empresa por:
- **URL Path**: `/empresa/{slug}/dashboard`
- **Subdominio**: `empresa.tudominio.com` (opcional)
- **Header**: `X-Tenant: empresa-slug`
- **Sesión**: Para desarrollo/testing

### **2. Configuración de Base de Datos**
```php
// Automático en TenantMiddleware
$tenant->configurarConexion();
Config::set('database.default', 'tenant');
```

### **3. Ejecución de Rutas**
Todas las rutas en `routes/tenant.php` se ejecutan con la conexión del tenant activo.

## 🛠️ Comandos Útiles

### **Setup Inicial**
```bash
# Configurar migraciones para tenants
php artisan tenant:setup-migrations

# Migrar tabla principal de tenants
php artisan migrate --path=database/migrations/2025_01_01_000000_create_tenants_table.php
```

### **Gestión de Tenants**
```bash
# Crear tenant programáticamente
php artisan tinker
$tenant = App\Models\Tenant::create([...]);
$tenant->crearBaseDatos();

# Ver estadísticas de tenant
$tenant = App\Models\Tenant::where('slug', 'demo')->first();
$stats = $tenant->getEstadisticas();
```

## 🔒 Seguridad

### **Aislamiento de Datos**
- Cada empresa tiene su propia base de datos
- Imposible acceso cruzado entre empresas
- Middleware valida permisos por tenant

### **Autenticación**
- Usuarios pertenecen a un tenant específico
- Sesiones aisladas por empresa
- Roles y permisos por tenant

## 📈 Escalabilidad

### **Horizontal**
- Cada empresa puede moverse a servidor dedicado
- Balanceador de carga por tenant
- Bases de datos distribuidas

### **Vertical**
- Límites configurables por plan
- Monitoreo de uso por empresa
- Alertas de límites

## 🚨 Troubleshooting

### **Error: "Empresa no encontrada"**
- Verificar que el slug existe en tabla `tenants`
- Verificar que la empresa está activa
- Verificar configuración de rutas

### **Error: "Base de datos no encontrada"**
- Verificar que la base de datos del tenant existe
- Verificar credenciales de conexión
- Ejecutar migraciones del tenant

### **Error: "Permisos insuficientes"**
- Verificar que el usuario MySQL tiene permisos
- Verificar configuración en `.env`
- Verificar que el tenant no está expirado

## 📞 Soporte

Para soporte técnico o consultas sobre el sistema multi-tenant:

1. Revisar logs en `storage/logs/laravel.log`
2. Verificar configuración de base de datos
3. Consultar documentación de Laravel para multi-tenancy
4. Contactar al equipo de desarrollo

## 🔄 Actualizaciones

### **Agregar Nueva Migración a Todos los Tenants**
1. Crear migración en `database/migrations/tenant/`
2. Ejecutar en cada tenant:
```php
$tenants = App\Models\Tenant::where('activo', true)->get();
foreach ($tenants as $tenant) {
    $tenant->configurarConexion();
    Artisan::call('migrate', ['--database' => 'tenant']);
}
```

### **Actualizar Funcionalidad**
1. Modificar archivos en `routes/tenant.php`
2. Actualizar controladores
3. Las vistas se comparten entre todos los tenants

---

**¡Sistema Multi-Tenant listo para producción!** 🚀
