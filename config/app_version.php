<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Información de Versión de la Aplicación
    |--------------------------------------------------------------------------
    |
    | Aquí se define la información de versión de la aplicación siguiendo
    | el estándar Semantic Versioning (SemVer): MAJOR.MINOR.PATCH
    |
    */

    'version' => '2.0.0-beta',
    'version_name' => 'Sistema Completo',
    'release_date' => '2025-09-22',
    'build' => env('APP_BUILD', date('YmdHis')),
    
    /*
    |--------------------------------------------------------------------------
    | Información Detallada
    |--------------------------------------------------------------------------
    */
    
    'major' => 2,
    'minor' => 0,
    'patch' => 0,
    'pre_release' => 'beta',
    
    /*
    |--------------------------------------------------------------------------
    | Funcionalidades de la Versión
    |--------------------------------------------------------------------------
    */
    
    'features' => [
        'Sistema de Equivalencias de Unidades',
        'Integración Completa con Alegra',
        'Facturación Electrónica DIAN',
        'Sistema Multi-Tenant',
        'Conversiones Automáticas',
        'API de Conversiones',
        'Logs Avanzados',
        'Manejo Inteligente de Impuestos',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Información del Desarrollador
    |--------------------------------------------------------------------------
    */
    
    'developer' => [
        'name' => 'Luis Carlos Correa Arrieta',
        'email' => 'carval82@gmail.com',
        'company' => 'Desarrollo Personalizado',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Compatibilidad
    |--------------------------------------------------------------------------
    */
    
    'requirements' => [
        'php' => '>=8.1',
        'laravel' => '>=10.0',
        'mysql' => '>=5.7',
        'composer' => '>=2.0',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Changelog Resumido
    |--------------------------------------------------------------------------
    */
    
    'changelog' => [
        '2.0.0-beta' => [
            'date' => '2025-09-22',
            'changes' => [
                'added' => [
                    'Sistema completo de equivalencias de unidades',
                    'Integración total con Alegra para facturación electrónica',
                    'Sistema multi-tenant con base de datos independiente',
                    'API de conversiones en tiempo real',
                    'Manejo inteligente de impuestos',
                    'Logs detallados y auditoría',
                ],
                'fixed' => [
                    'Error Alegra múltiples impuestos',
                    'Nombres de índices demasiado largos',
                    'Validación de columnas existentes en migraciones',
                ],
                'improved' => [
                    'Arquitectura modular y escalable',
                    'Documentación completa',
                    'Scripts de instalación automatizados',
                    'Validaciones robustas',
                ],
            ],
        ],
        '1.5.0' => [
            'date' => '2025-01-28',
            'changes' => [
                'added' => [
                    'Sistema de ventas base',
                    'Gestión de inventario',
                    'Facturación básica',
                    'Reportes contables',
                ],
            ],
        ],
    ],
];
