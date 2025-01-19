<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Compra;
use App\Models\Comprobante;
use App\Models\MovimientoContable;
use App\Models\ConfiguracionContable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class CompraContabilidadTest extends TestCase
{
    use RefreshDatabase;

    public function test_compra_genera_comprobante_contable()
    {
        // Configurar cuentas contables necesarias
        $this->setupCuentasContables();

        // Crear una compra
        $compra = Compra::create([
            'numero_factura' => 'TEST-001',
            'fecha_compra' => now(),
            'proveedor_id' => 1,
            'user_id' => 1,
            'subtotal' => 1000,
            'iva' => 190,
            'total' => 1190
        ]);

        // Verificar que se creó el comprobante
        $comprobante = Comprobante::where('descripcion', "Compra No. {$compra->numero_factura}")->first();
        $this->assertNotNull($comprobante);

        // Verificar movimientos contables
        $movimientos = MovimientoContable::where('comprobante_id', $comprobante->id)->get();
        
        // Debe haber 3 movimientos
        $this->assertEquals(3, $movimientos->count());

        // Verificar débitos y créditos
        $totalDebitos = $movimientos->sum('debito');
        $totalCreditos = $movimientos->sum('credito');
        
        // La partida debe estar cuadrada
        $this->assertEquals($totalDebitos, $totalCreditos);
        
        // El total debe coincidir con la compra
        $this->assertEquals($compra->total, $totalDebitos);
    }

    private function setupCuentasContables()
    {
        // Crear configuraciones contables necesarias
        ConfiguracionContable::create([
            'concepto' => 'inventario',
            'cuenta_id' => 1, // IDs de ejemplo
            'descripcion' => 'Cuenta de Inventario'
        ]);

        ConfiguracionContable::create([
            'concepto' => 'iva_compras',
            'cuenta_id' => 2,
            'descripcion' => 'IVA en Compras'
        ]);

        ConfiguracionContable::create([
            'concepto' => 'proveedores_por_pagar',
            'cuenta_id' => 3,
            'descripcion' => 'Cuentas por Pagar Proveedores'
        ]);
    }
} 