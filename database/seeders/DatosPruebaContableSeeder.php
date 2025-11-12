<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PlanCuenta;
use App\Models\Comprobante;
use App\Models\MovimientoContable;
use App\Models\Tercero;
use App\Models\User;
use Carbon\Carbon;

class DatosPruebaContableSeeder extends Seeder
{
    /**
     * Crear datos de prueba para contabilidad
     */
    public function run(): void
    {
        // Crear un tercero de prueba
        $tercero = Tercero::create([
            'tipo_documento' => 'NIT',
            'numero_documento' => '900123456',
            'digito_verificacion' => '1',
            'razon_social' => 'EMPRESA DE PRUEBA S.A.S.',
            'tipo_persona' => 'juridica',
            'tipo_tercero' => 'cliente',
            'regimen_fiscal' => 'comun',
            'email' => 'prueba@empresa.com',
            'telefono' => '3001234567',
            'direccion' => 'Calle 123 # 45-67',
            'ciudad' => 'Bogotá',
            'departamento' => 'Cundinamarca',
            'created_by' => 1
        ]);

        // Obtener usuario para los comprobantes
        $usuario = User::first();
        if (!$usuario) {
            $this->command->error('No hay usuarios en el sistema. Cree un usuario primero.');
            return;
        }

        // Obtener cuentas básicas (si existen del PUC)
        $cuentaCaja = PlanCuenta::where('codigo', '110505')->first();
        $cuentaBanco = PlanCuenta::where('codigo', '111005')->first();
        $cuentaClientes = PlanCuenta::where('codigo', '130505')->first();
        $cuentaInventario = PlanCuenta::where('codigo', '143505')->first();
        $cuentaProveedores = PlanCuenta::where('codigo', '220505')->first();
        $cuentaIva = PlanCuenta::where('codigo', '240805')->first();
        $cuentaCapital = PlanCuenta::where('codigo', '311505')->first();
        $cuentaVentas = PlanCuenta::where('codigo', '413505')->first();
        $cuentaCostos = PlanCuenta::where('codigo', '613505')->first();
        $cuentaGastos = PlanCuenta::where('codigo', '510506')->first();

        // Si no existen las cuentas del PUC, crear algunas básicas
        if (!$cuentaCaja) {
            $cuentaCaja = PlanCuenta::create([
                'codigo' => '1105',
                'nombre' => 'CAJA',
                'clase' => '1',
                'naturaleza' => 'debito',
                'tipo_cuenta' => 'activo_corriente',
                'nivel' => 3
            ]);
        }

        if (!$cuentaBanco) {
            $cuentaBanco = PlanCuenta::create([
                'codigo' => '1110',
                'nombre' => 'BANCOS',
                'clase' => '1',
                'naturaleza' => 'debito',
                'tipo_cuenta' => 'activo_corriente',
                'nivel' => 3
            ]);
        }

        if (!$cuentaVentas) {
            $cuentaVentas = PlanCuenta::create([
                'codigo' => '4135',
                'nombre' => 'VENTAS',
                'clase' => '4',
                'naturaleza' => 'credito',
                'tipo_cuenta' => 'ingreso_operacional',
                'nivel' => 3
            ]);
        }

        if (!$cuentaCostos) {
            $cuentaCostos = PlanCuenta::create([
                'codigo' => '6135',
                'nombre' => 'COSTO DE VENTAS',
                'clase' => '6',
                'naturaleza' => 'debito',
                'tipo_cuenta' => 'costo_ventas',
                'nivel' => 3
            ]);
        }

        if (!$cuentaGastos) {
            $cuentaGastos = PlanCuenta::create([
                'codigo' => '5105',
                'nombre' => 'GASTOS ADMINISTRATIVOS',
                'clase' => '5',
                'naturaleza' => 'debito',
                'tipo_cuenta' => 'gasto_operacional',
                'nivel' => 3
            ]);
        }

        if (!$cuentaCapital) {
            $cuentaCapital = PlanCuenta::create([
                'codigo' => '3115',
                'nombre' => 'CAPITAL SOCIAL',
                'clase' => '3',
                'naturaleza' => 'credito',
                'tipo_cuenta' => 'patrimonio',
                'nivel' => 3
            ]);
        }

        // Crear comprobantes de prueba
        $this->crearComprobanteApertura($usuario, $cuentaCaja, $cuentaBanco, $cuentaCapital);
        $this->crearComprobanteVenta($usuario, $tercero, $cuentaCaja, $cuentaVentas, $cuentaCostos);
        $this->crearComprobanteGasto($usuario, $cuentaBanco, $cuentaGastos);

        $this->command->info('✅ Datos de prueba contable creados exitosamente');
    }

    private function crearComprobanteApertura($usuario, $cuentaCaja, $cuentaBanco, $cuentaCapital)
    {
        $comprobante = Comprobante::create([
            'numero' => 'AP-001',
            'fecha' => Carbon::now()->startOfYear(),
            'tipo' => 'apertura',
            'descripcion' => 'Asiento de apertura del ejercicio',
            'estado' => 'Aprobado',
            'total_debito' => 50000000,
            'total_credito' => 50000000,
            'created_by' => $usuario->id,
            'approved_by' => $usuario->id
        ]);

        // Movimientos del asiento de apertura
        MovimientoContable::create([
            'comprobante_id' => $comprobante->id,
            'cuenta_id' => $cuentaCaja->id,
            'fecha' => $comprobante->fecha,
            'descripcion' => 'Saldo inicial en caja',
            'debito' => 5000000,
            'credito' => 0
        ]);

        MovimientoContable::create([
            'comprobante_id' => $comprobante->id,
            'cuenta_id' => $cuentaBanco->id,
            'fecha' => $comprobante->fecha,
            'descripcion' => 'Saldo inicial en bancos',
            'debito' => 45000000,
            'credito' => 0
        ]);

        MovimientoContable::create([
            'comprobante_id' => $comprobante->id,
            'cuenta_id' => $cuentaCapital->id,
            'fecha' => $comprobante->fecha,
            'descripcion' => 'Capital social inicial',
            'debito' => 0,
            'credito' => 50000000
        ]);
    }

    private function crearComprobanteVenta($usuario, $tercero, $cuentaCaja, $cuentaVentas, $cuentaCostos)
    {
        $comprobante = Comprobante::create([
            'numero' => 'VT-001',
            'fecha' => Carbon::now(),
            'tipo' => 'comprobante_ventas',
            'descripcion' => 'Venta de mercancías',
            'estado' => 'Aprobado',
            'total_debito' => 2380000,
            'total_credito' => 2380000,
            'tercero_id' => $tercero->id,
            'created_by' => $usuario->id,
            'approved_by' => $usuario->id
        ]);

        // Movimientos de la venta
        MovimientoContable::create([
            'comprobante_id' => $comprobante->id,
            'cuenta_id' => $cuentaCaja->id,
            'tercero_id' => $tercero->id,
            'fecha' => $comprobante->fecha,
            'descripcion' => 'Venta de mercancías - Efectivo',
            'debito' => 2380000,
            'credito' => 0
        ]);

        MovimientoContable::create([
            'comprobante_id' => $comprobante->id,
            'cuenta_id' => $cuentaVentas->id,
            'tercero_id' => $tercero->id,
            'fecha' => $comprobante->fecha,
            'descripcion' => 'Venta de mercancías',
            'debito' => 0,
            'credito' => 2000000
        ]);

        MovimientoContable::create([
            'comprobante_id' => $comprobante->id,
            'cuenta_id' => $cuentaVentas->id,
            'fecha' => $comprobante->fecha,
            'descripcion' => 'IVA sobre ventas',
            'debito' => 0,
            'credito' => 380000
        ]);

        // Costo de la mercancía vendida
        MovimientoContable::create([
            'comprobante_id' => $comprobante->id,
            'cuenta_id' => $cuentaCostos->id,
            'fecha' => $comprobante->fecha,
            'descripcion' => 'Costo de mercancías vendidas',
            'debito' => 1200000,
            'credito' => 0
        ]);

        // Salida de inventario (si existe la cuenta)
        $cuentaInventario = PlanCuenta::where('codigo', 'LIKE', '14%')->first();
        if ($cuentaInventario) {
            MovimientoContable::create([
                'comprobante_id' => $comprobante->id,
                'cuenta_id' => $cuentaInventario->id,
                'fecha' => $comprobante->fecha,
                'descripcion' => 'Salida de inventario por venta',
                'debito' => 0,
                'credito' => 1200000
            ]);
        }
    }

    private function crearComprobanteGasto($usuario, $cuentaBanco, $cuentaGastos)
    {
        $comprobante = Comprobante::create([
            'numero' => 'EG-001',
            'fecha' => Carbon::now(),
            'tipo' => 'egreso',
            'descripcion' => 'Pago de gastos administrativos',
            'estado' => 'Aprobado',
            'total_debito' => 500000,
            'total_credito' => 500000,
            'created_by' => $usuario->id,
            'approved_by' => $usuario->id
        ]);

        MovimientoContable::create([
            'comprobante_id' => $comprobante->id,
            'cuenta_id' => $cuentaGastos->id,
            'fecha' => $comprobante->fecha,
            'descripcion' => 'Gastos administrativos del mes',
            'debito' => 500000,
            'credito' => 0
        ]);

        MovimientoContable::create([
            'comprobante_id' => $comprobante->id,
            'cuenta_id' => $cuentaBanco->id,
            'fecha' => $comprobante->fecha,
            'descripcion' => 'Pago por transferencia bancaria',
            'debito' => 0,
            'credito' => 500000
        ]);
    }
}
