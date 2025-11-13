<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// app/Models/Empresa.php
class Empresa extends Model
{
    protected $fillable = [
        'nombre_comercial',
        'razon_social',
        'nit',
        'direccion',
        'telefono',
        'email',
        'sitio_web',
        'logo',
        'formato_impresion',
        'usar_formato_electronico',
        'generar_qr_local',
        'regimen_tributario',
        'resolucion_facturacion',
        'fecha_resolucion',
        'fecha_vencimiento_resolucion',
        'factura_electronica_habilitada',
        'alegra_email',
        'alegra_token',
        'alegra_multiples_impuestos',
        'prefijo_factura',
        'id_resolucion_alegra'
    ];

    protected $casts = [
        'fecha_resolucion' => 'date',
        'fecha_vencimiento_resolucion' => 'date',
        'factura_electronica_habilitada' => 'boolean',
        'alegra_multiples_impuestos' => 'boolean',
        'generar_qr_local' => 'boolean',
        'usar_formato_electronico' => 'boolean'
    ];

    protected $hidden = [
        'alegra_token',
    ];

    // Constantes para los regímenes tributarios
    const REGIMEN_RESPONSABLE_IVA = 'responsable_iva';
    const REGIMEN_NO_RESPONSABLE_IVA = 'no_responsable_iva';
    const REGIMEN_SIMPLE = 'regimen_simple';

    // Método helper para verificar si es responsable de IVA
    public function esResponsableIva(): bool
    {
        return $this->regimen_tributario === self::REGIMEN_RESPONSABLE_IVA;
    }

    // Método helper para verificar si puede emitir facturas electrónicas
    public function puedeEmitirFacturaElectronica(): bool
    {
        return $this->factura_electronica_habilitada && 
               $this->resolucion_facturacion && 
               $this->fecha_resolucion;
    }

    public function esRegimenSimple()
    {
        return $this->regimen_tributario === 'regimen_simple';
    }

    public function debeCalcularIVA()
    {
        return $this->esResponsableIVA() || $this->esRegimenSimple();
    }
}