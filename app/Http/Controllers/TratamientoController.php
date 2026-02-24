<?php

namespace App\Http\Controllers;

use App\Models\Tratamiento;
use App\Models\Animal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TratamientoController extends Controller
{
    public function index(Request $request)
    {
        $query = Tratamiento::where('inquilino_id', Auth::user()->inquilino_id);

        if ($request->filled('search')) {
            $query->whereHas('animal', function($q) use ($request) {
                $q->where('codigo_interno', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('motivo')) {
            $query->where('motivo', 'like', '%' . $request->motivo . '%');
        }

        $tratamientos = $query->orderBy('fecha', 'desc')->paginate(10);

        return view('tratamientos.index', compact('tratamientos'));
    }

    public function create()
    {
        $animales = Animal::where('inquilino_id', Auth::user()->inquilino_id)->get();
        return view('tratamientos.create', compact('animales'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'animal_id' => 'required',
            'motivo' => 'required|string|max:255',
            'medicamento' => 'required|string|max:255',
            'fecha' => 'required|date',
        ]);

        Tratamiento::create([
            'inquilino_id' => Auth::user()->inquilino_id,
            'animal_id' => $request->animal_id,
            'motivo' => $request->motivo,
            'medicamento' => $request->medicamento,
            'via' => $request->via,
            'dosis' => $request->dosis,
            'fecha' => $request->fecha,
            'fecha_fin' => $request->fecha_fin,
            'observaciones' => $request->observaciones,
        ]);

        return redirect()->route('tratamientos.index')->with('success', 'Tratamiento registrado');
    }

    public function show(Tratamiento $tratamiento)
    {
        if ($tratamiento->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        // ALERTA: tratamiento activo
        $alerta_activo = null;

        if (is_null($tratamiento->fecha_fin) || $tratamiento->fecha_fin >= now()->toDateString()) {
            $alerta_activo = "Este tratamiento aún está activo.";
        }

        return view('tratamientos.show', compact('tratamiento', 'alerta_activo'));
    }

    public function edit(Tratamiento $tratamiento)
    {
        if ($tratamiento->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        $animales = Animal::where('inquilino_id', Auth::user()->inquilino_id)->get();

        return view('tratamientos.edit', compact('tratamiento', 'animales'));
    }

    public function update(Request $request, Tratamiento $tratamiento)
    {
        if ($tratamiento->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        $request->validate([
            'animal_id' => 'required',
            'motivo' => 'required|string|max:255',
            'medicamento' => 'required|string|max:255',
            'fecha_inicio' => 'required|date',
        ]);

        $tratamiento->update($request->all());

        return redirect()->route('tratamientos.index')->with('success', 'Tratamiento actualizado');
    }

    public function destroy(Tratamiento $tratamiento)
    {
        if ($tratamiento->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        $tratamiento->delete();

        return redirect()->route('tratamientos.index')->with('success', 'Tratamiento eliminado');
    }
}