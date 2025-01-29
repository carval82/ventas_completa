<?php

namespace App\Http\Controllers;

use App\Models\Turno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TurnoController extends Controller
{
    public function index()
    {
        $turnos = Turno::with('usuario')
            ->orderBy('fecha', 'desc')
            ->paginate(10);

        return view('turnos.index', compact('turnos'));
    }

    public function create()
    {
        return view('turnos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date',
            'hora_inicio' => 'required',
            'hora_fin' => 'required|after:hora_inicio',
            'monto_inicial' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string'
        ]);

        $turno = Turno::create([
            'fecha' => $request->fecha,
            'hora_inicio' => $request->hora_inicio,
            'hora_fin' => $request->hora_fin,
            'monto_inicial' => $request->monto_inicial,
            'monto_final' => $request->monto_inicial,
            'observaciones' => $request->observaciones,
            'estado' => 'abierto',
            'user_id' => Auth::id()
        ]);

        return redirect()->route('turnos.show', $turno)
            ->with('success', 'Turno creado exitosamente');
    }

    public function show(Turno $turno)
    {
        $turno->load(['usuario', 'ventas']);
        return view('turnos.show', compact('turno'));
    }

    public function edit(Turno $turno)
    {
        return view('turnos.edit', compact('turno'));
    }

    public function update(Request $request, Turno $turno)
    {
        $request->validate([
            'fecha' => 'required|date',
            'hora_inicio' => 'required',
            'hora_fin' => 'required|after:hora_inicio',
            'monto_inicial' => 'required|numeric|min:0',
            'monto_final' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string'
        ]);

        $turno->update($request->all());

        return redirect()->route('turnos.show', $turno)
            ->with('success', 'Turno actualizado exitosamente');
    }

    public function destroy(Turno $turno)
    {
        if ($turno->estado === 'cerrado') {
            return back()->with('error', 'No se puede eliminar un turno cerrado');
        }

        $turno->delete();

        return redirect()->route('turnos.index')
            ->with('success', 'Turno eliminado exitosamente');
    }

    public function cerrar(Turno $turno)
    {
        $turno->update([
            'estado' => 'cerrado',
            'hora_fin' => now(),
            'monto_final' => $turno->calcularMontoFinal()
        ]);

        return redirect()->route('turnos.show', $turno)
            ->with('success', 'Turno cerrado exitosamente');
    }
} 