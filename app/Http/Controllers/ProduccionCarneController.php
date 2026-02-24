<?php

namespace App\Http\Controllers;

use App\Models\ProduccionCarne;
use App\Models\Animal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProduccionCarneController extends Controller
{
    public function index(Request $request)
    {
        $query = ProduccionCarne::where('inquilino_id', Auth::user()->inquilino_id);

        if ($request->filled('search')) {
            $query->whereHas('animal', function($q) use ($request) {
                $q->where('codigo_interno', 'like', '%' . $request->search . '%');
            });
        }

        $producciones = $query->orderBy('fecha', 'desc')->paginate(10);

        return view('produccion_carne.index', compact('producciones'));
    }

    public function create()
    {
        $animales = Animal::where('inquilino_id', Auth::user()->inquilino_id)->get();
        return view('produccion_carne.create', compact('animales'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'animal_id' => 'required',
            'fecha' => 'required|date',
            'peso' => 'required|numeric|min:0',
        ]);

        $ultimo = ProduccionCarne::where('animal_id', $request->animal_id)
                                 ->orderBy('fecha', 'desc')
                                 ->first();

        $peso_anterior = $ultimo->peso ?? null;
        $ganancia = null;

        if ($peso_anterior) {
            $dias = max(1, now()->diffInDays($ultimo->fecha));
            $ganancia = ($request->peso - $peso_anterior) / $dias;
        }

        ProduccionCarne::create([
            'inquilino_id' => Auth::user()->inquilino_id,
            'animal_id' => $request->animal_id,
            'fecha' => $request->fecha,
            'peso' => $request->peso,
            'peso_anterior' => $peso_anterior,
            'ganancia_diaria' => $ganancia,
            'observaciones' => $request->observaciones,
        ]);

        
        // Actualizar peso actual del animal
            // $animal = Animal::find($request->animal_id);
            // $animal->peso_actual = $animal->peso_actual + $request->peso;
            // $animal->save();


        return redirect()->route('produccion.index')->with('success', 'Registro de producción guardado');
    }

    public function show(ProduccionCarne $produccion)
    {
        if ($produccion->inquilino_id !== Auth::user()->inquilino_id) abort(403);
            $mes = \Carbon\Carbon::parse($produccion->fecha)->month;
            $anio = \Carbon\Carbon::parse($produccion->fecha)->year;

            $total_mes = \App\Models\ProduccionCarne::where('animal_id', $produccion->animal_id)
                ->whereMonth('fecha', $mes)
                ->whereYear('fecha', $anio)
                ->sum('ganancia_diaria');
                 // ALERTA: pérdida de peso o GDP negativa
                $alerta_baja = null;

                if (!is_null($produccion->ganancia_diaria) && $produccion->ganancia_diaria < 0) {
                    $alerta_baja = "Atención: este animal perdió peso recientemente.";
                }

                // ALERTA OPCIONAL: ganancia muy baja
                if (!is_null($produccion->ganancia_diaria) && $produccion->ganancia_diaria >= 0 && $produccion->ganancia_diaria < 0.2) {
                    $alerta_baja = "Advertencia: la ganancia diaria de peso es muy baja (" . number_format($produccion->ganancia_diaria, 2) . " kg/día).";
                }



            return view('produccion_carne.show', compact('produccion', 'total_mes','alerta_baja'));

    }

    public function edit(ProduccionCarne $produccion)
    {
        if ($produccion->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        $animales = Animal::where('inquilino_id', Auth::user()->inquilino_id)->get();

        return view('produccion_carne.edit', compact('produccion', 'animales'));
    }

    public function update(Request $request, ProduccionCarne $produccion)
    {
        if ($produccion->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        $request->validate([
            'animal_id' => 'required',
            'fecha' => 'required|date',
            'peso' => 'required|numeric|min:0',
        ]);

        $produccion->update($request->all());

        return redirect()->route('produccion.index')->with('success', 'Registro actualizado');
    }

    public function destroy(ProduccionCarne $produccion)
    {
        if ($produccion->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        $produccion->delete();

        return redirect()->route('produccion.index')->with('success', 'Registro eliminado');
    }
}