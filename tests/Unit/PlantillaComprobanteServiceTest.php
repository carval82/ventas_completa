<?php

namespace Tests\Unit;

use App\Services\PlantillaComprobanteService;
use PHPUnit\Framework\TestCase;
use Mockery;

class PlantillaComprobanteServiceTest extends TestCase
{
    protected $plantillaService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->plantillaService = new PlantillaComprobanteService();
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    /**
     * Prueba la obtención de plantilla de venta
     */
    public function testObtenerPlantillaVenta()
    {
        // Datos de prueba
        $datos = [
            'subtotal' => 1000,
            'iva' => 190,
            'total' => 1190,
            'referencia_id' => 1,
            'numero_documento' => 'V-001',
            'metodo_pago' => 'efectivo'
        ];
        
        // Mock de la función obtenerCuentaPorConcepto
        $this->plantillaService = Mockery::mock(PlantillaComprobanteService::class)->makePartial();
        $this->plantillaService->shouldReceive('obtenerCuentaPorConcepto')
            ->with('caja')
            ->andReturn((object)['id' => 1, 'codigo' => '1105', 'nombre' => 'Caja General']);
        
        $this->plantillaService->shouldReceive('obtenerCuentaPorConcepto')
            ->with('ventas')
            ->andReturn((object)['id' => 2, 'codigo' => '4135', 'nombre' => 'Ingresos por Ventas']);
            
        $this->plantillaService->shouldReceive('obtenerCuentaPorConcepto')
            ->with('iva_ventas')
            ->andReturn((object)['id' => 3, 'codigo' => '2408', 'nombre' => 'IVA Generado']);
        
        // Ejecutar el método
        $movimientos = $this->plantillaService->obtenerPlantillaVenta($datos);
        
        // Verificaciones
        $this->assertIsArray($movimientos);
        $this->assertCount(3, $movimientos); // Debe tener 3 movimientos: caja, ventas e IVA
        
        // Verificar movimiento de caja (débito)
        $this->assertEquals(1, $movimientos[0]['cuenta_id']);
        $this->assertEquals(1190, $movimientos[0]['debito']);
        $this->assertEquals(0, $movimientos[0]['credito']);
        
        // Verificar movimiento de ventas (crédito)
        $this->assertEquals(2, $movimientos[1]['cuenta_id']);
        $this->assertEquals(0, $movimientos[1]['debito']);
        $this->assertEquals(1000, $movimientos[1]['credito']);
        
        // Verificar movimiento de IVA (crédito)
        $this->assertEquals(3, $movimientos[2]['cuenta_id']);
        $this->assertEquals(0, $movimientos[2]['debito']);
        $this->assertEquals(190, $movimientos[2]['credito']);
    }
    
    /**
     * Prueba la obtención de plantilla de compra
     */
    public function testObtenerPlantillaCompra()
    {
        // Datos de prueba
        $datos = [
            'subtotal' => 1000,
            'iva' => 190,
            'total' => 1190,
            'referencia_id' => 1,
            'numero_documento' => 'C-001'
        ];
        
        // Mock de la función obtenerCuentaPorConcepto
        $this->plantillaService = Mockery::mock(PlantillaComprobanteService::class)->makePartial();
        $this->plantillaService->shouldReceive('obtenerCuentaPorConcepto')
            ->with('inventario')
            ->andReturn((object)['id' => 1, 'codigo' => '1435', 'nombre' => 'Inventario']);
        
        $this->plantillaService->shouldReceive('obtenerCuentaPorConcepto')
            ->with('iva_compras')
            ->andReturn((object)['id' => 2, 'codigo' => '1355', 'nombre' => 'IVA Descontable']);
            
        $this->plantillaService->shouldReceive('obtenerCuentaPorConcepto')
            ->with('proveedores')
            ->andReturn((object)['id' => 3, 'codigo' => '2205', 'nombre' => 'Proveedores']);
        
        // Ejecutar el método
        $movimientos = $this->plantillaService->obtenerPlantillaCompra($datos);
        
        // Verificaciones
        $this->assertIsArray($movimientos);
        $this->assertCount(3, $movimientos); // Debe tener 3 movimientos: inventario, IVA y proveedores
        
        // Verificar movimiento de inventario (débito)
        $this->assertEquals(1, $movimientos[0]['cuenta_id']);
        $this->assertEquals(1000, $movimientos[0]['debito']);
        $this->assertEquals(0, $movimientos[0]['credito']);
        
        // Verificar movimiento de IVA (débito)
        $this->assertEquals(2, $movimientos[1]['cuenta_id']);
        $this->assertEquals(190, $movimientos[1]['debito']);
        $this->assertEquals(0, $movimientos[1]['credito']);
        
        // Verificar movimiento de proveedores (crédito)
        $this->assertEquals(3, $movimientos[2]['cuenta_id']);
        $this->assertEquals(0, $movimientos[2]['debito']);
        $this->assertEquals(1190, $movimientos[2]['credito']);
    }
    
    /**
     * Prueba la validación de datos para plantillas
     */
    public function testValidarDatosPlantilla()
    {
        // Caso 1: Datos completos
        $datos = [
            'subtotal' => 1000,
            'iva' => 190,
            'total' => 1190,
            'referencia_id' => 1,
            'numero_documento' => 'V-001'
        ];
        
        $this->assertTrue($this->plantillaService->validarDatosPlantilla($datos));
        
        // Caso 2: Datos incompletos
        $datosIncompletos = [
            'subtotal' => 1000,
            'iva' => 190
            // Falta total, referencia_id y numero_documento
        ];
        
        $this->expectException(\Exception::class);
        $this->plantillaService->validarDatosPlantilla($datosIncompletos);
    }
}
