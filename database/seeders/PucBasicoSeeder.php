<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PlanCuenta;

class PucBasicoSeeder extends Seeder
{
    /**
     * Crear Plan Único de Cuentas básico según normativa colombiana
     */
    public function run(): void
    {
        $cuentas = [
            // CLASE 1 - ACTIVOS
            ['codigo' => '1', 'nombre' => 'ACTIVO', 'clase' => '1', 'naturaleza' => 'debito', 'nivel' => 1, 'tipo_cuenta' => 'activo_corriente'],
            
            // GRUPO 11 - DISPONIBLE
            ['codigo' => '11', 'nombre' => 'DISPONIBLE', 'clase' => '1', 'grupo' => '11', 'naturaleza' => 'debito', 'nivel' => 2, 'tipo_cuenta' => 'activo_corriente', 'cuenta_padre_id' => 1],
            ['codigo' => '1105', 'nombre' => 'CAJA', 'clase' => '1', 'grupo' => '11', 'cuenta' => '05', 'naturaleza' => 'debito', 'nivel' => 3, 'tipo_cuenta' => 'activo_corriente', 'cuenta_padre_id' => 2],
            ['codigo' => '110505', 'nombre' => 'Caja General', 'clase' => '1', 'grupo' => '11', 'cuenta' => '05', 'subcuenta' => '05', 'naturaleza' => 'debito', 'nivel' => 4, 'tipo_cuenta' => 'activo_corriente', 'cuenta_padre_id' => 3],
            
            ['codigo' => '1110', 'nombre' => 'BANCOS', 'clase' => '1', 'grupo' => '11', 'cuenta' => '10', 'naturaleza' => 'debito', 'nivel' => 3, 'tipo_cuenta' => 'activo_corriente', 'cuenta_padre_id' => 2, 'exige_tercero' => true],
            ['codigo' => '111005', 'nombre' => 'Bancos Nacionales', 'clase' => '1', 'grupo' => '11', 'cuenta' => '10', 'subcuenta' => '05', 'naturaleza' => 'debito', 'nivel' => 4, 'tipo_cuenta' => 'activo_corriente', 'cuenta_padre_id' => 5, 'exige_tercero' => true],
            
            // GRUPO 12 - INVERSIONES
            ['codigo' => '12', 'nombre' => 'INVERSIONES', 'clase' => '1', 'grupo' => '12', 'naturaleza' => 'debito', 'nivel' => 2, 'tipo_cuenta' => 'activo_corriente', 'cuenta_padre_id' => 1],
            
            // GRUPO 13 - DEUDORES
            ['codigo' => '13', 'nombre' => 'DEUDORES', 'clase' => '1', 'grupo' => '13', 'naturaleza' => 'debito', 'nivel' => 2, 'tipo_cuenta' => 'activo_corriente', 'cuenta_padre_id' => 1],
            ['codigo' => '1305', 'nombre' => 'CLIENTES', 'clase' => '1', 'grupo' => '13', 'cuenta' => '05', 'naturaleza' => 'debito', 'nivel' => 3, 'tipo_cuenta' => 'activo_corriente', 'cuenta_padre_id' => 8, 'exige_tercero' => true],
            ['codigo' => '130505', 'nombre' => 'Clientes Nacionales', 'clase' => '1', 'grupo' => '13', 'cuenta' => '05', 'subcuenta' => '05', 'naturaleza' => 'debito', 'nivel' => 4, 'tipo_cuenta' => 'activo_corriente', 'cuenta_padre_id' => 9, 'exige_tercero' => true],
            
            ['codigo' => '1355', 'nombre' => 'ANTICIPO DE IMPUESTOS Y CONTRIBUCIONES', 'clase' => '1', 'grupo' => '13', 'cuenta' => '55', 'naturaleza' => 'debito', 'nivel' => 3, 'tipo_cuenta' => 'activo_corriente', 'cuenta_padre_id' => 8],
            ['codigo' => '135505', 'nombre' => 'Anticipo de Renta y Complementarios', 'clase' => '1', 'grupo' => '13', 'cuenta' => '55', 'subcuenta' => '05', 'naturaleza' => 'debito', 'nivel' => 4, 'tipo_cuenta' => 'activo_corriente', 'cuenta_padre_id' => 11],
            ['codigo' => '135510', 'nombre' => 'Anticipo de IVA', 'clase' => '1', 'grupo' => '13', 'cuenta' => '55', 'subcuenta' => '10', 'naturaleza' => 'debito', 'nivel' => 4, 'tipo_cuenta' => 'activo_corriente', 'cuenta_padre_id' => 11],
            
            // GRUPO 14 - INVENTARIOS
            ['codigo' => '14', 'nombre' => 'INVENTARIOS', 'clase' => '1', 'grupo' => '14', 'naturaleza' => 'debito', 'nivel' => 2, 'tipo_cuenta' => 'activo_corriente', 'cuenta_padre_id' => 1],
            ['codigo' => '1435', 'nombre' => 'MERCANCÍAS NO FABRICADAS POR LA EMPRESA', 'clase' => '1', 'grupo' => '14', 'cuenta' => '35', 'naturaleza' => 'debito', 'nivel' => 3, 'tipo_cuenta' => 'activo_corriente', 'cuenta_padre_id' => 14],
            ['codigo' => '143505', 'nombre' => 'Mercancías', 'clase' => '1', 'grupo' => '14', 'cuenta' => '35', 'subcuenta' => '05', 'naturaleza' => 'debito', 'nivel' => 4, 'tipo_cuenta' => 'activo_corriente', 'cuenta_padre_id' => 15],
            
            // CLASE 2 - PASIVOS
            ['codigo' => '2', 'nombre' => 'PASIVO', 'clase' => '2', 'naturaleza' => 'credito', 'nivel' => 1, 'tipo_cuenta' => 'pasivo_corriente'],
            
            // GRUPO 22 - PROVEEDORES
            ['codigo' => '22', 'nombre' => 'PROVEEDORES', 'clase' => '2', 'grupo' => '22', 'naturaleza' => 'credito', 'nivel' => 2, 'tipo_cuenta' => 'pasivo_corriente', 'cuenta_padre_id' => 17],
            ['codigo' => '2205', 'nombre' => 'PROVEEDORES NACIONALES', 'clase' => '2', 'grupo' => '22', 'cuenta' => '05', 'naturaleza' => 'credito', 'nivel' => 3, 'tipo_cuenta' => 'pasivo_corriente', 'cuenta_padre_id' => 18, 'exige_tercero' => true],
            ['codigo' => '220505', 'nombre' => 'Proveedores', 'clase' => '2', 'grupo' => '22', 'cuenta' => '05', 'subcuenta' => '05', 'naturaleza' => 'credito', 'nivel' => 4, 'tipo_cuenta' => 'pasivo_corriente', 'cuenta_padre_id' => 19, 'exige_tercero' => true],
            
            // GRUPO 24 - IMPUESTOS GRAVÁMENES Y TASAS
            ['codigo' => '24', 'nombre' => 'IMPUESTOS, GRAVÁMENES Y TASAS', 'clase' => '2', 'grupo' => '24', 'naturaleza' => 'credito', 'nivel' => 2, 'tipo_cuenta' => 'pasivo_corriente', 'cuenta_padre_id' => 17],
            ['codigo' => '2408', 'nombre' => 'IMPUESTO SOBRE LAS VENTAS POR PAGAR', 'clase' => '2', 'grupo' => '24', 'cuenta' => '08', 'naturaleza' => 'credito', 'nivel' => 3, 'tipo_cuenta' => 'pasivo_corriente', 'cuenta_padre_id' => 21],
            ['codigo' => '240805', 'nombre' => 'IVA Generado', 'clase' => '2', 'grupo' => '24', 'cuenta' => '08', 'subcuenta' => '05', 'naturaleza' => 'credito', 'nivel' => 4, 'tipo_cuenta' => 'pasivo_corriente', 'cuenta_padre_id' => 22],
            
            ['codigo' => '2365', 'nombre' => 'RETENCIÓN EN LA FUENTE', 'clase' => '2', 'grupo' => '23', 'cuenta' => '65', 'naturaleza' => 'credito', 'nivel' => 3, 'tipo_cuenta' => 'pasivo_corriente', 'cuenta_padre_id' => 21, 'exige_tercero' => true],
            ['codigo' => '236505', 'nombre' => 'Retención Renta', 'clase' => '2', 'grupo' => '23', 'cuenta' => '65', 'subcuenta' => '05', 'naturaleza' => 'credito', 'nivel' => 4, 'tipo_cuenta' => 'pasivo_corriente', 'cuenta_padre_id' => 24, 'exige_tercero' => true],
            
            // CLASE 3 - PATRIMONIO
            ['codigo' => '3', 'nombre' => 'PATRIMONIO', 'clase' => '3', 'naturaleza' => 'credito', 'nivel' => 1, 'tipo_cuenta' => 'patrimonio'],
            ['codigo' => '31', 'nombre' => 'CAPITAL SOCIAL', 'clase' => '3', 'grupo' => '31', 'naturaleza' => 'credito', 'nivel' => 2, 'tipo_cuenta' => 'patrimonio', 'cuenta_padre_id' => 26],
            ['codigo' => '3115', 'nombre' => 'APORTES SOCIALES', 'clase' => '3', 'grupo' => '31', 'cuenta' => '15', 'naturaleza' => 'credito', 'nivel' => 3, 'tipo_cuenta' => 'patrimonio', 'cuenta_padre_id' => 27],
            ['codigo' => '311505', 'nombre' => 'Cuotas o Partes de Interés Social', 'clase' => '3', 'grupo' => '31', 'cuenta' => '15', 'subcuenta' => '05', 'naturaleza' => 'credito', 'nivel' => 4, 'tipo_cuenta' => 'patrimonio', 'cuenta_padre_id' => 28],
            
            // CLASE 4 - INGRESOS
            ['codigo' => '4', 'nombre' => 'INGRESOS', 'clase' => '4', 'naturaleza' => 'credito', 'nivel' => 1, 'tipo_cuenta' => 'ingreso_operacional'],
            ['codigo' => '41', 'nombre' => 'OPERACIONALES', 'clase' => '4', 'grupo' => '41', 'naturaleza' => 'credito', 'nivel' => 2, 'tipo_cuenta' => 'ingreso_operacional', 'cuenta_padre_id' => 30],
            ['codigo' => '4135', 'nombre' => 'COMERCIO AL POR MAYOR Y AL POR MENOR', 'clase' => '4', 'grupo' => '41', 'cuenta' => '35', 'naturaleza' => 'credito', 'nivel' => 3, 'tipo_cuenta' => 'ingreso_operacional', 'cuenta_padre_id' => 31],
            ['codigo' => '413505', 'nombre' => 'Venta de Mercancías', 'clase' => '4', 'grupo' => '41', 'cuenta' => '35', 'subcuenta' => '05', 'naturaleza' => 'credito', 'nivel' => 4, 'tipo_cuenta' => 'ingreso_operacional', 'cuenta_padre_id' => 32],
            
            // CLASE 6 - COSTOS DE VENTAS
            ['codigo' => '6', 'nombre' => 'COSTOS DE VENTAS', 'clase' => '6', 'naturaleza' => 'debito', 'nivel' => 1, 'tipo_cuenta' => 'costo_ventas'],
            ['codigo' => '61', 'nombre' => 'COSTO DE VENTAS', 'clase' => '6', 'grupo' => '61', 'naturaleza' => 'debito', 'nivel' => 2, 'tipo_cuenta' => 'costo_ventas', 'cuenta_padre_id' => 34],
            ['codigo' => '6135', 'nombre' => 'COMERCIO AL POR MAYOR Y AL POR MENOR', 'clase' => '6', 'grupo' => '61', 'cuenta' => '35', 'naturaleza' => 'debito', 'nivel' => 3, 'tipo_cuenta' => 'costo_ventas', 'cuenta_padre_id' => 35],
            ['codigo' => '613505', 'nombre' => 'Costo de Mercancías', 'clase' => '6', 'grupo' => '61', 'cuenta' => '35', 'subcuenta' => '05', 'naturaleza' => 'debito', 'nivel' => 4, 'tipo_cuenta' => 'costo_ventas', 'cuenta_padre_id' => 36],
            
            // CLASE 5 - GASTOS
            ['codigo' => '5', 'nombre' => 'GASTOS', 'clase' => '5', 'naturaleza' => 'debito', 'nivel' => 1, 'tipo_cuenta' => 'gasto_operacional'],
            ['codigo' => '51', 'nombre' => 'OPERACIONALES DE ADMINISTRACIÓN', 'clase' => '5', 'grupo' => '51', 'naturaleza' => 'debito', 'nivel' => 2, 'tipo_cuenta' => 'gasto_operacional', 'cuenta_padre_id' => 38],
            ['codigo' => '5105', 'nombre' => 'GASTOS DE PERSONAL', 'clase' => '5', 'grupo' => '51', 'cuenta' => '05', 'naturaleza' => 'debito', 'nivel' => 3, 'tipo_cuenta' => 'gasto_operacional', 'cuenta_padre_id' => 39],
            ['codigo' => '510506', 'nombre' => 'Sueldos', 'clase' => '5', 'grupo' => '51', 'cuenta' => '05', 'subcuenta' => '06', 'naturaleza' => 'debito', 'nivel' => 4, 'tipo_cuenta' => 'gasto_operacional', 'cuenta_padre_id' => 40],
        ];

        foreach ($cuentas as $cuenta) {
            PlanCuenta::create($cuenta);
        }
    }
}
