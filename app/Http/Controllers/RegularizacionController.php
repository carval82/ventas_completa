<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RegularizacionController extends Controller
{
    public function index()
    {
        return view('regularizacion.index');
    }

    public function regularizar()
    {
        try {
            Producto::regularizarProductos();
            
            return redirect()->back()->with('success', 'Regularización completada exitosamente');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error en la regularización: ' . $e->getMessage());
        }
    }
}