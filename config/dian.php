<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DIAN Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para la integración directa con DIAN
    | para facturación electrónica
    |
    */

    'test_mode' => env('DIAN_TEST_MODE', true),

    'test_url' => env('DIAN_TEST_URL', 'https://vpfe-hab.dian.gov.co/WcfDianCustomerServices.svc'),
    
    'production_url' => env('DIAN_PRODUCTION_URL', 'https://vpfe.dian.gov.co/WcfDianCustomerServices.svc'),

    'nit_empresa' => env('DIAN_NIT_EMPRESA', ''),
    
    'username' => env('DIAN_USERNAME', ''),
    
    'password' => env('DIAN_PASSWORD', ''),

    'certificado_path' => env('DIAN_CERTIFICADO_PATH', ''),
    
    'certificado_password' => env('DIAN_CERTIFICADO_PASSWORD', ''),

    'resolucion_numero' => env('DIAN_RESOLUCION_NUMERO', ''),
    
    'resolucion_fecha' => env('DIAN_RESOLUCION_FECHA', ''),
    
    'prefijo_factura' => env('DIAN_PREFIJO_FACTURA', 'FE'),
    
    'rango_inicial' => env('DIAN_RANGO_INICIAL', 1),
    
    'rango_final' => env('DIAN_RANGO_FINAL', 1000),

    'ambiente' => env('DIAN_AMBIENTE', '2'), // 1 = Producción, 2 = Pruebas
];
