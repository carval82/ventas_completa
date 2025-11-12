<?php

namespace Tests\Unit;

use App\Services\IvaValidationService;
use PHPUnit\Framework\TestCase;

class IvaValidationServiceTest extends TestCase
{
    protected $ivaService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ivaService = new IvaValidationService();
    }

    /**
     * Prueba la validación de porcentaje de IVA
     */
    public function testValidarPorcentajeIva()
    {
        // Casos válidos
        $this->assertTrue($this->ivaService->validarPorcentajeIva(19));
        $this->assertTrue($this->ivaService->validarPorcentajeIva(5));
        $this->assertTrue($this->ivaService->validarPorcentajeIva(0));
        
        // Casos inválidos
        $this->assertFalse($this->ivaService->validarPorcentajeIva(-1));
        $this->assertFalse($this->ivaService->validarPorcentajeIva(101));
    }

    /**
     * Prueba el cálculo de IVA
     */
    public function testCalcularIva()
    {
        // Caso 1: Valor base 100, IVA 19%
        $resultado = $this->ivaService->calcularIva(100, 19);
        $this->assertEquals(19, $resultado);
        
        // Caso 2: Valor base 100, IVA 5%
        $resultado = $this->ivaService->calcularIva(100, 5);
        $this->assertEquals(5, $resultado);
        
        // Caso 3: Valor base 100, IVA 0%
        $resultado = $this->ivaService->calcularIva(100, 0);
        $this->assertEquals(0, $resultado);
        
        // Caso 4: Valor base con decimales
        $resultado = $this->ivaService->calcularIva(123.45, 19);
        $this->assertEquals(23.46, $resultado);
    }

    /**
     * Prueba la verificación de cálculos de IVA
     */
    public function testVerificarCalculoIva()
    {
        // Caso 1: Cálculo correcto
        $this->assertTrue($this->ivaService->verificarCalculoIva(100, 19, 19));
        
        // Caso 2: Cálculo con redondeo fiscal
        $this->assertTrue($this->ivaService->verificarCalculoIva(123.45, 19, 23.46));
        
        // Caso 3: Cálculo incorrecto
        $this->assertFalse($this->ivaService->verificarCalculoIva(100, 19, 20));
        
        // Caso 4: Cálculo con margen de error aceptable
        $this->assertTrue($this->ivaService->verificarCalculoIva(123.45, 19, 23.45));
    }

    /**
     * Prueba la validación completa de IVA
     */
    public function testValidarIva()
    {
        // Caso 1: Datos válidos
        $resultado = $this->ivaService->validarIva(100, 19, 19);
        $this->assertTrue($resultado['valido']);
        $this->assertEquals(19, $resultado['valor_iva']);
        
        // Caso 2: Porcentaje inválido
        $resultado = $this->ivaService->validarIva(100, -5, 5);
        $this->assertFalse($resultado['valido']);
        $this->assertNotEmpty($resultado['error']);
        
        // Caso 3: Valor IVA incorrecto
        $resultado = $this->ivaService->validarIva(100, 19, 25);
        $this->assertFalse($resultado['valido']);
        $this->assertNotEmpty($resultado['error']);
    }
}
