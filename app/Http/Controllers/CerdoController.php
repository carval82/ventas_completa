<?php

namespace App\Http\Controllers;

use App\Models\Cerdo;
use App\Models\Camada;
use App\Models\Cliente;
use App\Models\ControlPeso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CerdoController extends Controller
{
    /**
     * Mostrar listado de cerdos
     */
    public function index(Request $request)
    {
        $query = Cerdo::with(['camada', 'camada.cerda']);
        
        // Filtros
        if ($request->has('tipo') && $request->tipo) {
            $query->where('tipo', $request->tipo);
        }
        
        if ($request->has('estado') && $request->estado) {
            $query->where('estado', $request->estado);
        }
        
        if ($request->has('camada_id') && $request->camada_id) {
            $query->where('camada_id', $request->camada_id);
        }

        $cerdos = $query->orderBy('codigo')->paginate(15);
        
        return view('cerdos.cerdos.index', compact('cerdos'));
    }

    /**
     * Mostrar formulario para crear nuevo cerdo
     */
    public function create()
    {
        $camadas = Camada::where('estado', 'destetada')->orderBy('fecha_parto', 'desc')->get();
        return view('cerdos.cerdos.create', compact('camadas'));
    }

    /**
     * Almacenar nuevo cerdo
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'codigo' => 'required|unique:cerdos,codigo',
            'camada_id' => 'required|exists:camadas,id',
            'sexo' => 'required|in:macho,hembra',
            'tipo' => 'required|in:engorde,ceba,reemplazo',
            'peso_destete' => 'nullable|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $cerdo = Cerdo::create($request->all());

        // Si se proporciona peso de destete, registrarlo como primer peso
        if ($request->peso_destete) {
            $camada = Camada::find($request->camada_id);
            $cerdo->registrarPeso(
                $request->peso_destete,
                $camada->fecha_destete ?? now(),
                'destete',
                'Peso inicial al destete'
            );
        }

        return redirect()->route('cerdos.index')
            ->with('success', 'Cerdo registrado correctamente');
    }

    /**
     * Mostrar detalle de cerdo
     */
    public function show(Cerdo $cerdo)
    {
        $pesos = $cerdo->controlPesos()->orderBy('fecha_pesaje', 'desc')->get();
        return view('cerdos.cerdos.show', compact('cerdo', 'pesos'));
    }

    /**
     * Mostrar formulario para editar cerdo
     */
    public function edit(Cerdo $cerdo)
    {
        $camadas = Camada::where('estado', 'destetada')->orderBy('fecha_parto', 'desc')->get();
        return view('cerdos.cerdos.edit', compact('cerdo', 'camadas'));
    }

    /**
     * Actualizar cerdo
     */
    public function update(Request $request, Cerdo $cerdo)
    {
        $validator = Validator::make($request->all(), [
            'codigo' => 'required|unique:cerdos,codigo,' . $cerdo->id,
            'camada_id' => 'required|exists:camadas,id',
            'sexo' => 'required|in:macho,hembra',
            'tipo' => 'required|in:engorde,ceba,reemplazo',
            'estado' => 'required|in:destete,crecimiento,engorde,vendido',
            'observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $cerdo->update($request->all());

        return redirect()->route('cerdos.index')
            ->with('success', 'Cerdo actualizado correctamente');
    }

    /**
     * Eliminar cerdo
     */
    public function destroy(Cerdo $cerdo)
    {
        // Verificar si el cerdo ha sido vendido
        if ($cerdo->vendido) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar el cerdo porque ya ha sido vendido');
        }

        $cerdo->delete();

        return redirect()->route('cerdos.index')
            ->with('success', 'Cerdo eliminado correctamente');
    }

    /**
     * Mostrar formulario para registrar peso
     */
    public function registrarPeso($id)
    {
        $cerdo = Cerdo::findOrFail($id);
        
        // Verificar que el cerdo no esté vendido
        if ($cerdo->vendido) {
            return redirect()->route('cerdos.show', $cerdo->id)
                ->with('error', 'No se puede registrar peso a un cerdo vendido');
        }

        return view('cerdos.cerdos.registrar-peso', compact('cerdo'));
    }

    /**
     * Procesar registro de peso
     */
    public function procesarRegistroPeso(Request $request, $id)
    {
        $cerdo = Cerdo::findOrFail($id);
        
        // Verificar que el cerdo no esté vendido
        if ($cerdo->vendido) {
            return redirect()->route('cerdos.show', $cerdo->id)
                ->with('error', 'No se puede registrar peso a un cerdo vendido');
        }

        $validator = Validator::make($request->all(), [
            'fecha_pesaje' => 'required|date',
            'peso' => 'required|numeric|min:0',
            'etapa' => 'required|in:destete,crecimiento,engorde',
            'observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Registrar el peso
        $cerdo->registrarPeso(
            $request->peso,
            $request->fecha_pesaje,
            $request->etapa,
            $request->observaciones
        );

        return redirect()->route('cerdos.show', $cerdo->id)
            ->with('success', 'Peso registrado correctamente');
    }

    /**
     * Mostrar formulario para vender cerdo
     */
    public function vender($id)
    {
        $cerdo = Cerdo::findOrFail($id);
        
        // Verificar que el cerdo no esté vendido
        if ($cerdo->vendido) {
            return redirect()->route('cerdos.show', $cerdo->id)
                ->with('error', 'El cerdo ya ha sido vendido');
        }

        $clientes = Cliente::orderBy('nombre')->get();
        return view('cerdos.cerdos.vender', compact('cerdo', 'clientes'));
    }

    /**
     * Procesar venta de cerdo
     */
    public function procesarVenta(Request $request, $id)
    {
        $cerdo = Cerdo::findOrFail($id);
        
        // Verificar que el cerdo no esté vendido
        if ($cerdo->vendido) {
            return redirect()->route('cerdos.show', $cerdo->id)
                ->with('error', 'El cerdo ya ha sido vendido');
        }

        $validator = Validator::make($request->all(), [
            'cliente_id' => 'nullable|exists:clientes,id',
            'fecha_venta' => 'required|date',
            'tipo_venta' => 'required|in:pie,kilo',
            'peso_venta' => 'required|numeric|min:0',
            'precio_unitario' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Procesar la venta
        $cerdo->vender(
            $request->cliente_id,
            $request->fecha_venta,
            $request->tipo_venta,
            $request->peso_venta,
            $request->precio_unitario,
            $request->observaciones
        );

        return redirect()->route('cerdos.show', $cerdo->id)
            ->with('success', 'Cerdo vendido correctamente');
    }

    /**
     * Cambiar tipo de cerdo (engorde, ceba, reemplazo)
     */
    public function cambiarTipo(Request $request, $id)
    {
        $cerdo = Cerdo::findOrFail($id);
        
        // Verificar que el cerdo no esté vendido
        if ($cerdo->vendido) {
            return redirect()->route('cerdos.show', $cerdo->id)
                ->with('error', 'No se puede cambiar el tipo a un cerdo vendido');
        }

        $validator = Validator::make($request->all(), [
            'tipo' => 'required|in:engorde,ceba,reemplazo',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $cerdo->cambiarTipo($request->tipo);

        return redirect()->route('cerdos.show', $cerdo->id)
            ->with('success', 'Tipo de cerdo actualizado correctamente');
    }
}
