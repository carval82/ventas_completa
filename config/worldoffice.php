<?php

return [
    /*
    |--------------------------------------------------------------------------
    | World Office API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para la integración con World Office
    |
    */

    'base_url' => env('WORLDOFFICE_BASE_URL', 'https://api.worldoffice.com/v1'),
    
    'api_key' => env('WORLDOFFICE_API_KEY'),
    
    'company_id' => env('WORLDOFFICE_COMPANY_ID'),
    
    'environment' => env('WORLDOFFICE_ENVIRONMENT', 'sandbox'), // sandbox, production
    
    'timeout' => env('WORLDOFFICE_TIMEOUT', 30),
    
    'retry_attempts' => env('WORLDOFFICE_RETRY_ATTEMPTS', 3),
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Documentos
    |--------------------------------------------------------------------------
    */
    
    'document_types' => [
        'invoice' => 'invoice',
        'credit_note' => 'credit_note',
        'quote' => 'quote',
        'purchase_order' => 'purchase_order',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Monedas
    |--------------------------------------------------------------------------
    */
    
    'currencies' => [
        'default' => env('WORLDOFFICE_DEFAULT_CURRENCY', 'COP'),
        'supported' => ['COP', 'USD', 'EUR'],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Impuestos
    |--------------------------------------------------------------------------
    */
    
    'tax_rates' => [
        'iva_standard' => env('WORLDOFFICE_IVA_STANDARD', 19),
        'iva_reduced' => env('WORLDOFFICE_IVA_REDUCED', 5),
        'iva_exempt' => env('WORLDOFFICE_IVA_EXEMPT', 0),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Métodos de Pago
    |--------------------------------------------------------------------------
    */
    
    'payment_methods' => [
        'cash' => 'cash',
        'credit_card' => 'credit_card',
        'debit_card' => 'debit_card',
        'bank_transfer' => 'bank_transfer',
        'check' => 'check',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Términos de Pago
    |--------------------------------------------------------------------------
    */
    
    'payment_terms' => [
        'immediate' => 0,
        'net_15' => 15,
        'net_30' => 30,
        'net_60' => 60,
        'net_90' => 90,
    ],
];
