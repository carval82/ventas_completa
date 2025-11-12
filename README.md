# Sistema de Ventas e Inventario

Sistema completo para la gestión de ventas, inventario, facturación electrónica y control de cajas diarias.

## Características principales

- **Gestión de Ventas**: Registro de ventas con múltiples productos, descuentos y diferentes métodos de pago.
- **Facturación Electrónica**: Integración con Alegra para la emisión de facturas electrónicas válidas ante la DIAN.
- **Control de Inventario**: Gestión de productos, categorías, ubicaciones y movimientos de stock.
- **Cajas Diarias**: Control de apertura y cierre de cajas, registro de movimientos, gastos y reportes financieros.
- **Gestión de Clientes**: Base de datos de clientes con información detallada y sincronización con Alegra.
- **Reportes**: Informes detallados de ventas, inventario, cajas y movimientos financieros.
- **Seguridad**: Sistema de roles y permisos para controlar el acceso a diferentes funcionalidades.

## Requisitos del sistema

- PHP 8.1 o superior
- MySQL 5.7 o superior
- Composer
- Node.js y npm
- Servidor web (Apache, Nginx)

## Instalación

Para instalar el sistema, sigue los pasos detallados en el archivo [INSTALACION.md](INSTALACION.md).

Para una instalación rápida:

### En Windows
```
instalar.bat
```

### En Linux/Mac
```
chmod +x instalar.sh
./instalar.sh
```

## Configuración de Alegra

Para la integración con Alegra (facturación electrónica), debes configurar las credenciales de API en el panel de administración:

1. Inicia sesión como administrador
2. Ve a Configuración > Empresa
3. Completa los campos de API Key y API Token de Alegra

## Soporte

Para soporte técnico, contacta a:
- Email: soporte@ejemplo.com
- Teléfono: +57 123 456 7890

## Licencia

Este software es propiedad de [Tu Empresa] y su uso está restringido según los términos del acuerdo de licencia.
