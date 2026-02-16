<?php

namespace App\Http\Controllers;

use App\Models\Reproduccion;
use App\Models\Animal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReproduccionController extends Controller
{
    public function index(Request $request)
    {
        $query = Reproduccion::where('inquilino_id', Auth::user()->inquilino_id);

        if ($request->filled('search')) {
            $query->whereHas('animal', function($q) use ($request) {
                $q->where('codigo_interno', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        $reproducciones = $query->orderBy('fecha', 'desc')->paginate(10);

        return view('reproduccion.index', compact('reproducciones'));
    }

    public function create()
    {
        $animales = Animal::where('inquilino_id', Auth::user()->inquilino_id)
                          ->where('sexo', 'h')
                          ->get();

        return view('reproduccion.create', compact('animales'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'animal_id' => 'required',
            'tipo' => 'required|string',
            'fecha' => 'required|date',
        ]);

        Reproduccion::create([
            'inquilino_id' => Auth::user()->inquilino_id,
            'animal_id' => $request->animal_id,
            'tipo' => $request->tipo,
            'fecha' => $request->fecha,
            'toro' => $request->toro,
            'resultado' => $request->resultado,
            'observaciones' => $request->observaciones,
        ]);

        return redirect()->route('reproduccion.index')->with('success', 'Evento reproductivo registrado');
    }

    public function show(Reproduccion $reproduccion)
    {
        if ($reproduccion->inquilino_id !== Auth::user()->inquilino_id) abort(403);
            $fecha_probable_parto = null;
            $alerta_parto = null;

    // Solo calcula FPP para monta o inseminación
    if (in_array($reproduccion->tipo, ['monta', 'inseminación'])) {

        $fecha_probable_parto = \Carbon\Carbon::parse($reproduccion->fecha)->addDays(283);

        // Calcular días restantes
        $dias_restantes = now()->diffInDays($fecha_probable_parto, false);

        // Si faltan 30 días o menos → alerta
        if ($dias_restantes <= 30 && $dias_restantes >= 0) {
            $alerta_parto = "Atención: faltan $dias_restantes días para el parto.";
        }

        // Si ya pasó la fecha
        if ($dias_restantes < 0) {
            $alerta_parto = "La fecha probable de parto ya pasó.";
        }

        // Formato para la vista
        $fecha_probable_parto = $fecha_probable_parto->format('Y-m-d');
    }

    return view('reproduccion.show', compact('reproduccion', 'fecha_probable_parto', 'alerta_parto'));
    }

    public function edit(Reproduccion $reproduccion)
    {
        if ($reproduccion->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        $animales = Animal::where('inquilino_id', Auth::user()->inquilino_id)
                          ->where('sexo', 'h')
                          ->get();

        return view('reproduccion.edit', compact('reproduccion', 'animales'));
    }

    public function update(Request $request, Reproduccion $reproduccion)
    {
        if ($reproduccion->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        $request->validate([
            'animal_id' => 'required',
            'tipo' => 'required|string',
            'fecha' => 'required|date',
        ]);

        $reproduccion->update($request->all());

        return redirect()->route('reproduccion.index')->with('success', 'Registro actualizado');
    }

    public function destroy(Reproduccion $reproduccion)
    {
        if ($reproduccion->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        $reproduccion->delete();

        return redirect()->route('reproduccion.index')->with('success', 'Registro eliminado');
    }
}