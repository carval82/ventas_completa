<?php

namespace Tests\Unit;

use App\Services\ContabilidadQueryService;
use App\Models\MovimientoContable;
use App\Models\PlanCuenta;
use App\Models\Comprobante;
use PHPUnit\Framework\TestCase;
use Mockery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ContabilidadQueryServiceTest extends TestCase
{
    protected $queryService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->queryService = new ContabilidadQueryService();
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    /**
     * Prueba la obtención de movimientos de cuenta
     */
    public function testObtenerMovimientosCuenta()
    {
        // Fechas de prueba
        $fechaInicio = '2025-06-01';
        $fechaFin = '2025-06-30';
        $cuentaId = 1;
        
        // Datos de prueba
        $movimientos = [
            [
                'id' => 1,
                'fecha' => '2025-06-05',
                'descripcion' => 'Venta No. V-001',
                'debito' => 1000,
                'credito' => 0,
                'saldo_acumulado' => 1000
            ],
            [
                'id' => 2,
                'fecha' => '2025-06-10',
                'descripcion' => 'Compra No. C-001',
                'debito' => 0,
                'credito' => 500,
                'saldo_acumulado' => 500
            ]
        ];
        
        // Mock de la clase DB
        $dbMock = Mockery::mock('alias:Illuminate\Support\Facades\DB');
        $dbMock->shouldReceive('select->get')
            ->andReturn(collect($movimientos));
        
        // Ejecutar el método
        $resultado = $this->queryService->obtenerMovimientosCuenta($cuentaId, $fechaInicio, $fechaFin);
        
        // Verificaciones
        $this->assertInstanceOf(Collection::class, $resultado);
        $this->assertCount(2, $resultado);
        $this->assertEquals(1000, $resultado[0]['saldo_acumulado']);
        $this->assertEquals(500, $resultado[1]['saldo_acumulado']);
    }
    
    /**
     * Prueba la obtención de saldo de cuenta
     */
    public function testObtenerSaldoCuenta()
    {
        // Datos de prueba
        $cuentaId = 1;
        $fecha = '2025-06-30';
        $saldoEsperado = 1500;
        
        // Mock de Cache
        $cacheMock = Mockery::mock('alias:Illuminate\Support\Facades\Cache');
        $cacheMock->shouldReceive('remember')
            ->andReturn($saldoEsperado);
        
        // Ejecutar el método
        $saldo = $this->queryService->obtenerSaldoCuenta($cuentaId, $fecha);
        
        // Verificar que el saldo sea el esperado
        $this->assertEquals($saldoEsperado, $saldo);
    }
    
    /**
     * Prueba la generación de balance de comprobación
     */
    public function testGenerarBalanceComprobacion()
    {
        // Fechas de prueba
        $fechaInicio = '2025-06-01';
        $fechaFin = '2025-06-30';
        
        // Datos de prueba
        $cuentas = [
            [
                'id' => 1,
                'codigo' => '1105',
                'nombre' => 'Caja General',
                'tipo' => 'Activo',
                'saldo_debito' => 2000,
                'saldo_credito' => 500,
                'saldo_final' => 1500
            ],
            [
                'id' => 2,
                'codigo' => '2205',
                'nombre' => 'Proveedores',
                'tipo' => 'Pasivo',
                'saldo_debito' => 500,
                'saldo_credito' => 2000,
                'saldo_final' => -1500
            ]
        ];
        
        // Mock de la clase DB
        $dbMock = Mockery::mock('alias:Illuminate\Support\Facades\DB');
        $dbMock->shouldReceive('select->get')
            ->andReturn(collect($cuentas));
        
        // Ejecutar el método
        $resultado = $this->queryService->generarBalanceComprobacion($fechaInicio, $fechaFin);
        
        // Verificaciones
        $this->assertInstanceOf(Collection::class, $resultado);
        $this->assertCount(2, $resultado);
        $this->assertEquals(1500, $resultado[0]['saldo_final']);
        $this->assertEquals(-1500, $resultado[1]['saldo_final']);
    }
    
    /**
     * Prueba la obtención de resumen de ventas con IVA
     */
    public function testObtenerResumenVentasConIva()
    {
        // Fechas de prueba
        $fechaInicio = '2025-06-01';
        $fechaFin = '2025-06-30';
        
        // Datos de prueba
        $resumen = [
            'total_ventas' => 10000,
            'total_iva' => 1900,
            'total_general' => 11900,
            'detalle' => [
                [
                    'fecha' => '2025-06-05',
                    'numero_factura' => 'V-001',
                    'cliente' => 'Cliente 1',
                    'subtotal' => 5000,
                    'iva' => 950,
                    'total' => 5950
                ],
                [
                    'fecha' => '2025-06-15',
                    'numero_factura' => 'V-002',
                    'cliente' => 'Cliente 2',
                    'subtotal' => 5000,
                    'iva' => 950,
                    'total' => 5950
                ]
            ]
        ];
        
        // Mock de la clase DB
        $dbMock = Mockery::mock('alias:Illuminate\Support\Facades\DB');
        $dbMock->shouldReceive('select->get')
            ->andReturn(collect($resumen['detalle']));
        
        $dbMock->shouldReceive('select->first')
            ->andReturn((object)[
                'total_ventas' => 10000,
                'total_iva' => 1900,
                'total_general' => 11900
            ]);
        
        // Ejecutar el método
        $resultado = $this->queryService->obtenerResumenVentasConIva($fechaInicio, $fechaFin);
        
        // Verificaciones
        $this->assertIsArray($resultado);
        $this->assertEquals(10000, $resultado['total_ventas']);
        $this->assertEquals(1900, $resultado['total_iva']);
        $this->assertEquals(11900, $resultado['total_general']);
        $this->assertCount(2, $resultado['detalle']);
    }
    
    /**
     * Prueba la obtención de resumen de compras con IVA
     */
    public function testObtenerResumenComprasConIva()
    {
        // Fechas de prueba
        $fechaInicio = '2025-06-01';
        $fechaFin = '2025-06-30';
        
        // Datos de prueba
        $resumen = [
            'total_compras' => 8000,
            'total_iva' => 1520,
            'total_general' => 9520,
            'detalle' => [
                [
                    'fecha' => '2025-06-10',
                    'numero_factura' => 'C-001',
                    'proveedor' => 'Proveedor 1',
                    'subtotal' => 3000,
                    'iva' => 570,
                    'total' => 3570
                ],
                [
                    'fecha' => '2025-06-20',
                    'numero_factura' => 'C-002',
                    'proveedor' => 'Proveedor 2',
                    'subtotal' => 5000,
                    'iva' => 950,
                    'total' => 5950
                ]
            ]
        ];
        
        // Mock de la clase DB
        $dbMock = Mockery::mock('alias:Illuminate\Support\Facades\DB');
        $dbMock->shouldReceive('select->get')
            ->andReturn(collect($resumen['detalle']));
        
        $dbMock->shouldReceive('select->first')
            ->andReturn((object)[
                'total_compras' => 8000,
                'total_iva' => 1520,
                'total_general' => 9520
            ]);
        
        // Ejecutar el método
        $resultado = $this->queryService->obtenerResumenComprasConIva($fechaInicio, $fechaFin);
        
        // Verificaciones
        $this->assertIsArray($resultado);
        $this->assertEquals(8000, $resultado['total_compras']);
        $this->assertEquals(1520, $resultado['total_iva']);
        $this->assertEquals(9520, $resultado['total_general']);
        $this->assertCount(2, $resultado['detalle']);
    }
    
    /**
     * Prueba la generación de reporte fiscal de IVA
     */
    public function testGenerarReporteFiscalIva()
    {
        // Fechas de prueba
        $fechaInicio = '2025-06-01';
        $fechaFin = '2025-06-30';
        
        // Mock del servicio
        $this->queryService = Mockery::mock(ContabilidadQueryService::class)->makePartial();
        
        // Mock de obtenerResumenVentasConIva
        $this->queryService->shouldReceive('obtenerResumenVentasConIva')
            ->with($fechaInicio, $fechaFin)
            ->andReturn([
                'total_ventas' => 10000,
                'total_iva' => 1900,
                'total_general' => 11900,
                'detalle' => []
            ]);
        
        // Mock de obtenerResumenComprasConIva
        $this->queryService->shouldReceive('obtenerResumenComprasConIva')
            ->with($fechaInicio, $fechaFin)
            ->andReturn([
                'total_compras' => 8000,
                'total_iva' => 1520,
                'total_general' => 9520,
                'detalle' => []
            ]);
        
        // Ejecutar el método
        $resultado = $this->queryService->generarReporteFiscalIva($fechaInicio, $fechaFin);
        
        // Verificaciones
        $this->assertIsArray($resultado);
        $this->assertEquals(1900, $resultado['iva_generado']);
        $this->assertEquals(1520, $resultado['iva_descontable']);
        $this->assertEquals(380, $resultado['saldo_a_pagar']);
        $this->assertEquals(0, $resultado['saldo_a_favor']);
    }
}
