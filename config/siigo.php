<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Siigo API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para la integración con Siigo
    |
    */

    'base_url' => env('SIIGO_BASE_URL', 'https://api.siigo.com/v1'),
    
    'username' => env('SIIGO_USERNAME'),
    
    'access_key' => env('SIIGO_ACCESS_KEY'),
    
    'partner_id' => env('SIIGO_PARTNER_ID'),
    
    'subscription_key' => env('SIIGO_SUBSCRIPTION_KEY'),
    
    'timeout' => env('SIIGO_TIMEOUT', 30),
    
    'retry_attempts' => env('SIIGO_RETRY_ATTEMPTS', 3),
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Documentos
    |--------------------------------------------------------------------------
    */
    
    'document_types' => [
        'invoice' => env('SIIGO_INVOICE_TYPE_ID', 1),
        'credit_note' => env('SIIGO_CREDIT_NOTE_TYPE_ID', 2),
        'debit_note' => env('SIIGO_DEBIT_NOTE_TYPE_ID', 3),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Impuestos
    |--------------------------------------------------------------------------
    */
    
    'tax_ids' => [
        'iva_19' => env('SIIGO_IVA_19_ID', 13156),
        'iva_5' => env('SIIGO_IVA_5_ID', 13158),
        'iva_0' => env('SIIGO_IVA_0_ID', 13159),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Cuentas Contables
    |--------------------------------------------------------------------------
    */
    
    'account_groups' => [
        'products' => env('SIIGO_PRODUCTS_ACCOUNT_GROUP', 361),
        'services' => env('SIIGO_SERVICES_ACCOUNT_GROUP', 362),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Precios
    |--------------------------------------------------------------------------
    */
    
    'price_lists' => [
        'default' => env('SIIGO_DEFAULT_PRICE_LIST', 1),
        'wholesale' => env('SIIGO_WHOLESALE_PRICE_LIST', 2),
    ],
];
