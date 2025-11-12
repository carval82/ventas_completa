<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Proveedor Activo de Facturación Electrónica
    |--------------------------------------------------------------------------
    |
    | Define cuál proveedor de facturación electrónica está activo.
    | Opciones: alegra, dian, siigo, worldoffice
    |
    */
    'proveedor_activo' => env('FACTURACION_PROVEEDOR', 'alegra'),

    /*
    |--------------------------------------------------------------------------
    | Configuración de Proveedores
    |--------------------------------------------------------------------------
    |
    | Configuración específica para cada proveedor de facturación
    |
    */
    'proveedores' => [
        'alegra' => [
            'activo' => env('ALEGRA_ACTIVO', true),
            'nombre' => 'Alegra',
            'descripcion' => 'Integración con Alegra para facturación electrónica',
            'config_keys' => ['alegra.usuario', 'alegra.token']
        ],
        'dian' => [
            'activo' => env('DIAN_ACTIVO', false),
            'nombre' => 'DIAN Directo',
            'descripcion' => 'Integración directa con DIAN',
            'config_keys' => ['dian.nit_empresa', 'dian.username', 'dian.password']
        ],
        'siigo' => [
            'activo' => env('SIIGO_ACTIVO', false),
            'nombre' => 'Siigo',
            'descripcion' => 'Integración con Siigo para facturación electrónica',
            'config_keys' => ['siigo.username', 'siigo.access_key']
        ],
        'worldoffice' => [
            'activo' => env('WORLDOFFICE_ACTIVO', false),
            'nombre' => 'World Office',
            'descripcion' => 'Integración con World Office',
            'config_keys' => ['worldoffice.api_key', 'worldoffice.company_id']
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Fallback
    |--------------------------------------------------------------------------
    |
    | Si el proveedor principal falla, intentar con estos proveedores
    |
    */
    'fallback_enabled' => env('FACTURACION_FALLBACK', false),
    'fallback_order' => ['alegra', 'dian'],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Logs
    |--------------------------------------------------------------------------
    */
    'log_requests' => env('FACTURACION_LOG_REQUESTS', true),
    'log_responses' => env('FACTURACION_LOG_RESPONSES', true),
];
