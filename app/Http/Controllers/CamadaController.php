<?php

namespace App\Http\Controllers;

use App\Models\Camada;
use App\Models\Cerda;
use App\Models\Cerdo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CamadaController extends Controller
{
    /**
     * Mostrar listado de camadas
     */
    public function index()
    {
        $camadas = Camada::with('cerda')->orderBy('fecha_parto', 'desc')->paginate(10);
        return view('cerdos.camadas.index', compact('camadas'));
    }

    /**
     * Mostrar formulario para crear nueva camada
     */
    public function create()
    {
        $cerdas = Cerda::where('estado', 'activa')->orderBy('codigo')->get();
        return view('cerdos.camadas.create', compact('cerdas'));
    }

    /**
     * Almacenar nueva camada
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cerda_id' => 'required|exists:cerdas,id',
            'fecha_parto' => 'required|date',
            'total_nacidos' => 'required|integer|min:1',
            'nacidos_vivos' => 'required|integer|min:0|lte:total_nacidos',
            'nacidos_muertos' => 'required|integer|min:0|lte:total_nacidos',
            'observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Verificar que la suma de nacidos vivos y muertos sea igual al total
        if (($request->nacidos_vivos + $request->nacidos_muertos) != $request->total_nacidos) {
            return redirect()->back()
                ->withErrors(['total_nacidos' => 'La suma de nacidos vivos y muertos debe ser igual al total de nacidos'])
                ->withInput();
        }

        Camada::create($request->all());

        return redirect()->route('camadas.index')
            ->with('success', 'Camada registrada correctamente');
    }

    /**
     * Mostrar detalle de camada
     */
    public function show(Camada $camada)
    {
        $cerdos = $camada->cerdos()->orderBy('codigo')->get();
        return view('cerdos.camadas.show', compact('camada', 'cerdos'));
    }

    /**
     * Mostrar formulario para editar camada
     */
    public function edit(Camada $camada)
    {
        $cerdas = Cerda::where('estado', 'activa')->orderBy('codigo')->get();
        return view('cerdos.camadas.edit', compact('camada', 'cerdas'));
    }

    /**
     * Actualizar camada
     */
    public function update(Request $request, Camada $camada)
    {
        $validator = Validator::make($request->all(), [
            'cerda_id' => 'required|exists:cerdas,id',
            'fecha_parto' => 'required|date',
            'total_nacidos' => 'required|integer|min:1',
            'nacidos_vivos' => 'required|integer|min:0|lte:total_nacidos',
            'nacidos_muertos' => 'required|integer|min:0|lte:total_nacidos',
            'fecha_destete' => 'nullable|date|after_or_equal:fecha_parto',
            'total_destetados' => 'nullable|integer|min:0|lte:nacidos_vivos',
            'estado' => 'required|in:activa,destetada,finalizada',
            'observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Verificar que la suma de nacidos vivos y muertos sea igual al total
        if (($request->nacidos_vivos + $request->nacidos_muertos) != $request->total_nacidos) {
            return redirect()->back()
                ->withErrors(['total_nacidos' => 'La suma de nacidos vivos y muertos debe ser igual al total de nacidos'])
                ->withInput();
        }

        $camada->update($request->all());

        return redirect()->route('camadas.index')
            ->with('success', 'Camada actualizada correctamente');
    }

    /**
     * Eliminar camada
     */
    public function destroy(Camada $camada)
    {
        // Verificar si tiene cerdos
        if ($camada->cerdos()->count() > 0) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar la camada porque tiene cerdos registrados');
        }

        $camada->delete();

        return redirect()->route('camadas.index')
            ->with('success', 'Camada eliminada correctamente');
    }

    /**
     * Mostrar formulario para destetar camada
     */
    public function destetar($id)
    {
        $camada = Camada::findOrFail($id);
        
        // Verificar que la camada no esté destetada
        if ($camada->estado != 'activa') {
            return redirect()->route('camadas.show', $camada->id)
                ->with('error', 'La camada ya ha sido destetada');
        }

        return view('cerdos.camadas.destetar', compact('camada'));
    }

    /**
     * Procesar destete de camada
     */
    public function procesarDestete(Request $request, $id)
    {
        $camada = Camada::findOrFail($id);
        
        // Verificar que la camada no esté destetada
        if ($camada->estado != 'activa') {
            return redirect()->route('camadas.show', $camada->id)
                ->with('error', 'La camada ya ha sido destetada');
        }

        $validator = Validator::make($request->all(), [
            'fecha_destete' => 'required|date|after_or_equal:' . $camada->fecha_parto,
            'total_destetados' => 'required|integer|min:0|max:' . $camada->nacidos_vivos,
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Procesar el destete automáticamente
        $camada->destetar($request->fecha_destete, $request->total_destetados);

        return redirect()->route('camadas.show', $camada->id)
            ->with('success', 'Camada destetada correctamente. Se han creado ' . $request->total_destetados . ' cerdos automáticamente.');
    }
}
