<?php

namespace App\Http\Controllers;

use App\Models\Movilizacion;
use App\Models\Animal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MovilizacionController extends Controller
{
    public function index(Request $request)
    {
        $query = Movilizacion::where('inquilino_id', Auth::user()->inquilino_id);

        if ($request->filled('search')) {
            $query->whereHas('animal', function($q) use ($request) {
                $q->where('codigo_interno', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        $movilizaciones = $query->orderBy('fecha', 'desc')->paginate(10);

        return view('movilizacion.index', compact('movilizaciones'));
    }

    public function create()
    {
        $animales = Animal::where('inquilino_id', Auth::user()->inquilino_id)->get();
        return view('movilizacion.create', compact('animales'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'animal_id' => 'required',
            'tipo' => 'required|string',
            'fecha' => 'required|date',
        ]);

        Movilizacion::create([
            'inquilino_id' => Auth::user()->inquilino_id,
            'animal_id' => $request->animal_id,
            'tipo' => $request->tipo,
            'origen' => $request->origen,
            'destino' => $request->destino,
            'fecha' => $request->fecha,
            'motivo' => $request->descripcion,
        ]);

        return redirect()->route('movilizaciones.index')->with('success', 'Movilización registrada');
    }

    public function show(Movilizacion $movilizacione)
    {
        if ($movilizacione->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        return view('movilizacion.show', compact('movilizacione'));
    }

    public function edit(Movilizacion $movilizacione)
    {
        if ($movilizacione->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        $animales = Animal::where('inquilino_id', Auth::user()->inquilino_id)->get();

        return view('movilizacion.edit', compact('movilizacione', 'animales'));
    }

    public function update(Request $request, Movilizacion $movilizacione)
    {
        if ($movilizacione->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        $request->validate([
            'animal_id' => 'required',
            'tipo' => 'required|string',
            'fecha' => 'required|date',
        ]);

        $movilizacione->update($request->all());

        return redirect()->route('movilizaciones.index')->with('success', 'Movilización actualizada');
    }

    public function destroy(Movilizacion $movilizacione)
    {
        if ($movilizacione->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        $movilizacione->delete();

        return redirect()->route('movilizaciones.index')->with('success', 'Movilización eliminada');
    }
}