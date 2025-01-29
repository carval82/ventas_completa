<?php

namespace App\Http\Controllers;

use App\Models\Credito;
use App\Models\PagoCredito;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CreditoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Comentamos temporalmente los middleware de permisos
        // $this->middleware('permission:ver creditos')->only(['index', 'show']);
        // $this->middleware('permission:registrar pagos')->only(['registrarPago']);
    }

    public function index()
    {
        $creditos = Credito::with(['cliente', 'venta'])
            ->orderBy('fecha_vencimiento')
            ->paginate(10);

        // Calcular totales
        $totalPendiente = Credito::where('estado', 'pendiente')->sum('saldo_pendiente');
        $totalParcial = Credito::where('estado', 'parcial')->sum('saldo_pendiente');
        $totalCobrado = Credito::whereIn('estado', ['pendiente', 'parcial'])
            ->sum(DB::raw('monto_total - saldo_pendiente'));

        return view('creditos.index', compact('creditos', 'totalPendiente', 'totalParcial', 'totalCobrado'));
    }

    public function show(Credito $credito)
    {
        $credito->load(['cliente', 'venta', 'pagos' => function($query) {
            $query->orderBy('fecha_pago', 'desc');
        }]);

        // Calcular días de atraso (si es negativo, significa que aún no vence)
        $diasAtraso = now()->diffInDays($credito->fecha_vencimiento, false);
        // Solo mostrar días de atraso si ya venció
        $diasAtraso = $diasAtraso > 0 ? $diasAtraso : 0;

        return view('creditos.show', compact('credito', 'diasAtraso'));
    }

    public function registrarPago(Request $request, Credito $credito)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0|max:' . $credito->saldo_pendiente,
            'fecha_pago' => 'required|date',
            'observacion' => 'nullable|string|max:255'
        ]);

        try {
            DB::transaction(function () use ($request, $credito) {
                // Registrar el pago
                $pago = $credito->pagos()->create([
                    'monto' => $request->monto,
                    'fecha_pago' => $request->fecha_pago,
                    'observacion' => $request->observacion
                ]);

                // Actualizar saldo pendiente
                $credito->saldo_pendiente -= $request->monto;
                
                // Actualizar estado del crédito
                if ($credito->saldo_pendiente <= 0) {
                    $credito->estado = 'pagado';
                } else if ($credito->saldo_pendiente < $credito->monto_total) {
                    $credito->estado = 'parcial';
                }
                
                $credito->save();
            });

            return response()->json([
                'success' => true,
                'message' => 'Pago registrado exitosamente',
                'redirect' => route('creditos.show', $credito)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el pago: ' . $e->getMessage()
            ], 422);
        }
    }

    public function reporteCreditos(Request $request)
    {
        $query = Credito::with(['cliente', 'venta']);

        // Filtros
        if ($request->estado) {
            $query->where('estado', $request->estado);
        }

        if ($request->fecha_inicio) {
            $query->whereDate('fecha_vencimiento', '>=', $request->fecha_inicio);
        }

        if ($request->fecha_fin) {
            $query->whereDate('fecha_vencimiento', '<=', $request->fecha_fin);
        }

        $creditos = $query->get();

        // Si es una solicitud de exportación, generar PDF o Excel
        if ($request->formato === 'pdf') {
            return $this->generarPDF($creditos);
        }

        return view('creditos.reporte', compact('creditos'));
    }

    protected function generarPDF($creditos)
    {
        // Aquí implementarías la lógica para generar el PDF
        // Usando por ejemplo dompdf o cualquier otra librería
        return 'Función de PDF pendiente de implementar';
    }
} 