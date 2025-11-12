<?php

namespace Tests\Unit;

use App\Services\ContabilidadService;
use App\Services\PlantillaComprobanteService;
use App\Models\Venta;
use App\Models\Compra;
use App\Models\DetalleVenta;
use App\Models\DetalleCompra;
use App\Models\Comprobante;
use App\Models\MovimientoContable;
use App\Models\PlanCuenta;
use PHPUnit\Framework\TestCase;
use Mockery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContabilidadServiceTest extends TestCase
{
    protected $contabilidadService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->contabilidadService = new ContabilidadService();
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    /**
     * Prueba la generación de número de comprobante
     */
    public function testGenerarNumeroComprobante()
    {
        // Mock de la clase DB
        $dbMock = Mockery::mock('alias:Illuminate\Support\Facades\DB');
        $dbMock->shouldReceive('table->where->max')
            ->andReturn(5);
        
        // Ejecutar el método
        $numero = $this->contabilidadService->generarNumeroComprobante('V');
        
        // Verificar que el número sea el esperado
        $this->assertEquals(6, $numero);
    }
    
    /**
     * Prueba la generación de comprobante de venta
     */
    public function testGenerarComprobanteVenta()
    {
        // Mock de la clase Venta
        $venta = Mockery::mock(Venta::class);
        $venta->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $venta->shouldReceive('getAttribute')->with('total')->andReturn(1190);
        $venta->shouldReceive('getAttribute')->with('fecha_venta')->andReturn('2025-06-05');
        $venta->shouldReceive('getAttribute')->with('numero_factura')->andReturn('V-001');
        $venta->shouldReceive('getAttribute')->with('metodo_pago')->andReturn('efectivo');
        
        // Mock de detalles de venta
        $detalle = Mockery::mock(DetalleVenta::class);
        $detalle->shouldReceive('getAttribute')->with('subtotal')->andReturn(1000);
        $detalle->shouldReceive('getAttribute')->with('valor_iva')->andReturn(190);
        $detalle->shouldReceive('getAttribute')->with('tiene_iva')->andReturn(true);
        
        $venta->detalles = collect([$detalle]);
        
        // Mock de PlantillaComprobanteService
        $plantillaService = Mockery::mock(PlantillaComprobanteService::class);
        $plantillaService->shouldReceive('obtenerPlantillaVenta')
            ->andReturn([
                [
                    'cuenta_id' => 1,
                    'descripcion' => 'Venta No. V-001',
                    'debito' => 1190,
                    'credito' => 0,
                    'referencia' => 1,
                    'referencia_tipo' => 'App\\Models\\Venta'
                ],
                [
                    'cuenta_id' => 2,
                    'descripcion' => 'Venta No. V-001',
                    'debito' => 0,
                    'credito' => 1000,
                    'referencia' => 1,
                    'referencia_tipo' => 'App\\Models\\Venta'
                ],
                [
                    'cuenta_id' => 3,
                    'descripcion' => 'IVA Venta No. V-001',
                    'debito' => 0,
                    'credito' => 190,
                    'referencia' => 1,
                    'referencia_tipo' => 'App\\Models\\Venta'
                ]
            ]);
        
        // Mock de Log
        $logMock = Mockery::mock('alias:Illuminate\Support\Facades\Log');
        $logMock->shouldReceive('info')->withAnyArgs()->andReturn(null);
        $logMock->shouldReceive('warning')->withAnyArgs()->andReturn(null);
        $logMock->shouldReceive('error')->withAnyArgs()->andReturn(null);
        
        // Mock de DB para transacciones
        $dbMock = Mockery::mock('alias:Illuminate\Support\Facades\DB');
        $dbMock->shouldReceive('beginTransaction')->andReturn(null);
        $dbMock->shouldReceive('commit')->andReturn(null);
        $dbMock->shouldReceive('rollBack')->andReturn(null);
        
        // Mock del método generarComprobante
        $this->contabilidadService = Mockery::mock(ContabilidadService::class)->makePartial();
        $this->contabilidadService->shouldReceive('generarComprobante')
            ->andReturn(new Comprobante());
        
        // Ejecutar el método
        $resultado = $this->contabilidadService->generarComprobanteVenta($venta);
        
        // Verificar que se devuelva un comprobante
        $this->assertInstanceOf(Comprobante::class, $resultado);
    }
    
    /**
     * Prueba la generación de comprobante de compra
     */
    public function testGenerarComprobanteCompra()
    {
        // Mock de la clase Compra
        $compra = Mockery::mock(Compra::class);
        $compra->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $compra->shouldReceive('getAttribute')->with('total')->andReturn(1190);
        $compra->shouldReceive('getAttribute')->with('fecha_compra')->andReturn('2025-06-05');
        $compra->shouldReceive('getAttribute')->with('numero_factura')->andReturn('C-001');
        
        // Mock de detalles de compra
        $detalle = Mockery::mock(DetalleCompra::class);
        $detalle->shouldReceive('getAttribute')->with('subtotal')->andReturn(1000);
        $detalle->shouldReceive('getAttribute')->with('valor_iva')->andReturn(190);
        $detalle->shouldReceive('getAttribute')->with('tiene_iva')->andReturn(true);
        
        $compra->detalles = collect([$detalle]);
        
        // Mock de PlantillaComprobanteService
        $plantillaService = Mockery::mock(PlantillaComprobanteService::class);
        $plantillaService->shouldReceive('obtenerPlantillaCompra')
            ->andReturn([
                [
                    'cuenta_id' => 1,
                    'descripcion' => 'Compra No. C-001',
                    'debito' => 1000,
                    'credito' => 0,
                    'referencia' => 1,
                    'referencia_tipo' => 'App\\Models\\Compra'
                ],
                [
                    'cuenta_id' => 2,
                    'descripcion' => 'IVA Compra No. C-001',
                    'debito' => 190,
                    'credito' => 0,
                    'referencia' => 1,
                    'referencia_tipo' => 'App\\Models\\Compra'
                ],
                [
                    'cuenta_id' => 3,
                    'descripcion' => 'Compra No. C-001',
                    'debito' => 0,
                    'credito' => 1190,
                    'referencia' => 1,
                    'referencia_tipo' => 'App\\Models\\Compra'
                ]
            ]);
        
        // Mock de Log
        $logMock = Mockery::mock('alias:Illuminate\Support\Facades\Log');
        $logMock->shouldReceive('info')->withAnyArgs()->andReturn(null);
        $logMock->shouldReceive('warning')->withAnyArgs()->andReturn(null);
        $logMock->shouldReceive('error')->withAnyArgs()->andReturn(null);
        
        // Mock de DB para transacciones
        $dbMock = Mockery::mock('alias:Illuminate\Support\Facades\DB');
        $dbMock->shouldReceive('beginTransaction')->andReturn(null);
        $dbMock->shouldReceive('commit')->andReturn(null);
        $dbMock->shouldReceive('rollBack')->andReturn(null);
        
        // Mock del método generarComprobante
        $this->contabilidadService = Mockery::mock(ContabilidadService::class)->makePartial();
        $this->contabilidadService->shouldReceive('generarComprobante')
            ->andReturn(new Comprobante());
        
        // Ejecutar el método
        $resultado = $this->contabilidadService->generarComprobanteCompra($compra);
        
        // Verificar que se devuelva un comprobante
        $this->assertInstanceOf(Comprobante::class, $resultado);
    }
    
    /**
     * Prueba la validación de cuadre contable
     */
    public function testValidarCuadreContable()
    {
        // Caso 1: Movimientos cuadrados
        $movimientos = [
            [
                'debito' => 1000,
                'credito' => 0
            ],
            [
                'debito' => 0,
                'credito' => 1000
            ]
        ];
        
        $this->assertTrue($this->contabilidadService->validarCuadreContable($movimientos));
        
        // Caso 2: Movimientos descuadrados
        $movimientosDescuadrados = [
            [
                'debito' => 1000,
                'credito' => 0
            ],
            [
                'debito' => 0,
                'credito' => 900
            ]
        ];
        
        $this->assertFalse($this->contabilidadService->validarCuadreContable($movimientosDescuadrados));
    }
    
    /**
     * Prueba la obtención de cuenta por concepto
     */
    public function testObtenerCuentaPorConcepto()
    {
        // Mock de la clase PlanCuenta
        $cuenta = new PlanCuenta();
        $cuenta->id = 1;
        $cuenta->codigo = '1105';
        $cuenta->nombre = 'Caja General';
        
        // Mock de la clase DB
        $dbMock = Mockery::mock('alias:Illuminate\Support\Facades\DB');
        $dbMock->shouldReceive('table->join->where->where->first')
            ->andReturn($cuenta);
        
        // Ejecutar el método
        $resultado = $this->contabilidadService->obtenerCuentaPorConcepto('caja');
        
        // Verificar que se devuelva la cuenta correcta
        $this->assertInstanceOf(PlanCuenta::class, $resultado);
        $this->assertEquals(1, $resultado->id);
        $this->assertEquals('1105', $resultado->codigo);
    }
}
