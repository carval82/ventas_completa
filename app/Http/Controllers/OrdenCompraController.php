<?php

namespace App\Http\Controllers;

use App\Models\OrdenCompra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdenCompraController extends Controller
{
    public function index()
    {
        $ordenes = OrdenCompra::with(['proveedor', 'usuario'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('ordenes.index', compact('ordenes'));
    }

    public function show(OrdenCompra $orden)
    {
        $orden->load(['detalles.producto', 'proveedor', 'usuario']);
        return view('ordenes.show', compact('orden'));
    }

    public function updateStatus(Request $request, OrdenCompra $orden)
    {
        $request->validate([
            'estado' => 'required|in:pendiente,aprobada,enviada,recibida,cancelada'
        ]);

        $orden->update(['estado' => $request->estado]);

        return redirect()->back()
            ->with('success', 'Estado de la orden actualizado correctamente');
    }

    public function export(OrdenCompra $orden)
    {
        $orden->load(['detalles.producto', 'proveedor']);
        
        // Aquí puedes implementar la exportación a PDF
        // Por ahora retornamos a la vista
        return redirect()->back()
            ->with('info', 'Función de exportación en desarrollo');
    }
} 