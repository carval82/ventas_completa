<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Producto;
use App\Models\Cliente;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // Fechas
        $hoy = Carbon::today();
        $ayer = Carbon::yesterday();
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();
        $mesAnterior = Carbon::now()->subMonth();

        // Ventas de hoy (suma del total en dinero)
        $ventasHoy = Venta::whereDate('created_at', $hoy)->sum('total');
        $ventasAyer = Venta::whereDate('created_at', $ayer)->sum('total');
        
        // Calcular porcentaje de ventas
        $porcentajeVentasHoy = $ventasAyer > 0 
            ? (($ventasHoy - $ventasAyer) / $ventasAyer) * 100 
            : 0;

        // Ventas del mes (suma del total en dinero)
        $ventasMes = Venta::whereMonth('created_at', $hoy->month)
            ->whereYear('created_at', $hoy->year)
            ->sum('total');
            
        $ventasMesAnterior = Venta::whereMonth('created_at', $mesAnterior->month)
            ->whereYear('created_at', $mesAnterior->year)
            ->sum('total');

        // Calcular porcentaje de ventas del mes
        $porcentajeVentasMes = $ventasMesAnterior > 0 
            ? (($ventasMes - $ventasMesAnterior) / $ventasMesAnterior) * 100 
            : 0;

        // Productos
        $totalProductos = Producto::where('estado', 1)->count();
        $productosActivos = $totalProductos;
        $productosStockBajo = Producto::where('estado', 1)
            ->whereRaw('stock <= stock_minimo')
            ->orderBy('stock', 'asc')
            ->get();

        // Total Clientes
        $totalClientes = Cliente::count();

        // Ventas Recientes
        $ventasRecientes = Venta::with('cliente')
            ->latest()
            ->take(5)
            ->get();

        // Datos para el gráfico
        $ventasChart = $this->getVentasChart();
        $ventasPorMes = $ventasChart;

        // Productos con bajo stock para la tabla
        $productosBajoStock = $productosStockBajo;

        // Métricas adicionales
        $totalVentasPeriodo = Venta::whereYear('created_at', $hoy->year)->sum('total');
        $promedioVentas = Venta::whereYear('created_at', $hoy->year)
            ->selectRaw('AVG(total) as promedio')
            ->value('promedio') ?? 0;

        $crecimientoVentas = $ventasMesAnterior > 0 
            ? (($ventasMes - $ventasMesAnterior) / $ventasMesAnterior) * 100 
            : 0;

        // Atajos de teclado
        $shortcuts = $this->getKeyboardShortcuts();

        return view('home', compact(
            'ventasHoy',
            'ventasMes',
            'totalProductos',
            'productosStockBajo',
            'porcentajeVentasHoy',
            'porcentajeVentasMes',
            'productosActivos',
            'ventasChart',
            'totalVentasPeriodo',
            'promedioVentas',
            'crecimientoVentas',
            'shortcuts',
            'totalClientes',
            'ventasRecientes',
            'productosBajoStock',
            'ventasPorMes'
        ));
    }

    private function getVentasChart()
    {
        $ventas = Venta::selectRaw('MONTH(created_at) as mes, SUM(total) as total')
            ->whereYear('created_at', Carbon::now()->year)
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        $labels = [];
        $data = array_fill(0, 12, 0);

        foreach ($ventas as $venta) {
            $mes = Carbon::create()->month($venta->mes)->format('M');
            $labels[] = $mes;
            $data[$venta->mes - 1] = $venta->total;
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    private function getKeyboardShortcuts()
    {
        return [
            'Ventas' => [
                ['description' => 'Nueva Venta', 'key' => 'Ctrl + V', 'route' => 'ventas.create'],
                ['description' => 'Nueva Compra', 'key' => 'Ctrl + C', 'route' => 'compras.create'],
            ],
            'Catálogos' => [
                ['description' => 'Productos', 'key' => 'Ctrl + D', 'route' => 'productos.index'],
                ['description' => 'Clientes', 'key' => 'Ctrl + E', 'route' => 'clientes.index'],
                ['description' => 'Proveedores', 'key' => 'Ctrl + S', 'route' => 'proveedores.index'],
            ]
        ];
    }
}