<?php

return [
    'base_url' => 'https://api.alegra.com/api/v1',
    'user' => env('ALEGRA_USER'),
    'token' => env('ALEGRA_TOKEN'),
    'fe_template_id' => env('ALEGRA_FE_TEMPLATE_ID', 1),
    
    // Métodos de pago disponibles en Alegra
    'payment_methods' => [
        'efectivo' => [
            'id' => 10,  // ID de Efectivo en Alegra
            'name' => 'Efectivo',
            'account_id' => 1
        ],
        'transferencia' => [
            'id' => 1,  // ID de Transferencia en Alegra
            'name' => 'Transferencia Bancaria',
            'account_id' => 1
        ],
        'tarjeta_credito' => [
            'id' => 3,  // ID de Tarjeta de Crédito en Alegra
            'name' => 'Tarjeta de Crédito',
            'account_id' => 1
        ],
        'tarjeta_debito' => [
            'id' => 4,  // ID de Tarjeta de Débito en Alegra
            'name' => 'Tarjeta de Débito',
            'account_id' => 1
        ],
    ],
    
    // Cuentas disponibles en Alegra
    'accounts' => [
        1 => 'Cuenta Principal',
        2 => 'Caja Menor',
    ],
    
    // Plantillas de numeración (templates)
    'number_templates' => [
        19 => 'Factura Electrónica de Venta (FEVP)'
    ],
    
    // Unidades de medida disponibles
    'measurement_units' => [
        'unit' => 'Unidad',
        'lb' => 'Libra',
        'kg' => 'Kilogramo',
        'g' => 'Gramo',
        'l' => 'Litro',
        'ml' => 'Mililitro',
        'm' => 'Metro',
        'cm' => 'Centímetro',
        'mm' => 'Milímetro',
        'box' => 'Caja',
        'service' => 'Servicio'
    ]
];
