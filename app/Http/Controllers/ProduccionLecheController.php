<?php

namespace App\Http\Controllers;

use App\Models\ProduccionLeche;
use App\Models\Animal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProduccionLecheController extends Controller
{
    public function index(Request $request)
    {
        $query = ProduccionLeche::where('inquilino_id', Auth::user()->inquilino_id);

        if ($request->filled('search')) {
            $query->whereHas('animal', function($q) use ($request) {
                $q->where('codigo_interno', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('turno')) {
            $query->where('turno', $request->turno);
        }

        $producciones = $query->orderBy('fecha', 'desc')->paginate(10);

        return view('produccion_leche.index', compact('producciones'));
    }

    public function create()
    {
        $animales = Animal::where('inquilino_id', Auth::user()->inquilino_id)
                          ->where('sexo', 'h')
                          ->get();

        return view('produccion_leche.create', compact('animales'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'animal_id' => 'required',
            'fecha' => 'required|date',
            'litros' => 'required|numeric|min:0',
        ]);

        $ultimo = ProduccionLeche::where('animal_id', $request->animal_id)
                                 ->orderBy('fecha', 'desc')
                                 ->first();

        $litros_anteriores = $ultimo->litros ?? null;
        $variacion = null;

        if ($litros_anteriores !== null) {
            $variacion = $request->litros - $litros_anteriores;
        }

        ProduccionLeche::create([
            'inquilino_id' => Auth::user()->inquilino_id,
            'animal_id' => $request->animal_id,
            'fecha' => $request->fecha,
            'turno' => $request->turno,
            'litros' => $request->litros,
            'litros_anteriores' => $litros_anteriores,
            'variacion' => $variacion,
            'observaciones' => $request->observaciones,
        ]);

        return redirect()->route('produccion_leche.index')->with('success', 'Registro de producción guardado');
    }

    public function show(ProduccionLeche $produccion_leche)
    {
        if ($produccion_leche->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        return view('produccion_leche.show', compact('produccion_leche'));
    }

    public function edit(ProduccionLeche $produccion_leche)
    {
        if ($produccion_leche->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        $animales = Animal::where('inquilino_id', Auth::user()->inquilino_id)
                          ->where('sexo', 'h')
                          ->get();

        return view('produccion_leche.edit', compact('produccion_leche', 'animales'));
    }

    public function update(Request $request, ProduccionLeche $produccion_leche)
    {
        if ($produccion_leche->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        $request->validate([
            'animal_id' => 'required',
            'fecha' => 'required|date',
            'litros' => 'required|numeric|min:0',
        ]);

        $produccion_leche->update($request->all());

        return redirect()->route('produccion_leche.index')->with('success', 'Registro actualizado');
    }

    public function destroy(ProduccionLeche $produccion_leche)
    {
        if ($produccion_leche->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        $produccion_leche->delete();

        return redirect()->route('produccion_leche.index')->with('success', 'Registro eliminado');
    }
}