<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cerda;
use App\Models\Camada;
use App\Models\Cerdo;
use App\Models\VentaCerdo;
use Carbon\Carbon;

class CerdosDashboardController extends Controller
{
    /**
     * Muestra el dashboard del módulo de cerdos
     */
    public function index()
    {
        // Estadísticas generales
        $totalCerdas = Cerda::where('estado', 'activa')->count();
        $totalCamadas = Camada::where('estado', 'activa')->count();
        $totalCerdos = Cerdo::where('vendido', false)->count();
        
        // Ventas del mes actual
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();
        $totalVentas = VentaCerdo::whereBetween('fecha_venta', [$inicioMes, $finMes])->count();
        
        // Últimas camadas
        $ultimasCamadas = Camada::with('cerda')
            ->orderBy('fecha_parto', 'desc')
            ->take(5)
            ->get();
        
        // Últimas ventas
        $ultimasVentas = VentaCerdo::with('cerdo')
            ->orderBy('fecha_venta', 'desc')
            ->take(5)
            ->get();
        
        // Cerdos listos para venta (en estado de engorde)
        $cerdosParaVenta = Cerdo::where('estado', 'engorde')
            ->where('vendido', false)
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();
        
        return view('cerdos.index', compact(
            'totalCerdas',
            'totalCamadas',
            'totalCerdos',
            'totalVentas',
            'ultimasCamadas',
            'ultimasVentas',
            'cerdosParaVenta'
        ));
    }
}
