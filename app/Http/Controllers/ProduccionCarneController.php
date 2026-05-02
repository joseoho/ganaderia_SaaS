<?php

namespace App\Http\Controllers;

use App\Models\ProduccionCarne;
use App\Models\Animal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreProduccionCarneRequest;
use App\Http\Requests\UpdateProduccionCarneRequest;
use Carbon\Carbon;

class ProduccionCarneController extends Controller
{
    public function index(Request $request)
    {
        $query = ProduccionCarne::where('inquilino_id', Auth::user()->inquilino_id);

        if ($request->filled('search')) {
            $query->whereHas('animal', function ($q) use ($request) {
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

    public function store(StoreProduccionCarneRequest $request)
    {
        $ultimo = ProduccionCarne::where('animal_id', $request->animal_id)
            ->orderBy('fecha', 'desc')
            ->first();

        $peso_anterior = $ultimo->peso ?? null;
        $ganancia = null;

        if ($peso_anterior && $ultimo) {
            $dias = max(1, Carbon::parse($request->fecha)->diffInDays(Carbon::parse($ultimo->fecha)));
            $ganancia = ($request->peso - $peso_anterior) / $dias;
        }

        ProduccionCarne::create([
            'inquilino_id'   => Auth::user()->inquilino_id,
            'animal_id'      => $request->animal_id,
            'fecha'          => $request->fecha,
            'peso'           => $request->peso,
            'peso_anterior'  => $peso_anterior,
            'ganancia_diaria'=> $ganancia,
            'observaciones'  => $request->observaciones,
        ]);

        return redirect()->route('produccion.index')->with('success', 'Registro de producción guardado');
    }

    public function show(ProduccionCarne $produccion)
    {
        if ($produccion->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        $mes  = Carbon::parse($produccion->fecha)->month;
        $anio = Carbon::parse($produccion->fecha)->year;

        $total_mes = ProduccionCarne::where('animal_id', $produccion->animal_id)
            ->whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->sum('ganancia_diaria');

        $alerta_baja = null;
        if (!is_null($produccion->ganancia_diaria) && $produccion->ganancia_diaria < 0) {
            $alerta_baja = "Atención: este animal perdió peso recientemente.";
        } elseif (!is_null($produccion->ganancia_diaria) && $produccion->ganancia_diaria >= 0 && $produccion->ganancia_diaria < 0.2) {
            $alerta_baja = "Advertencia: la ganancia diaria de peso es muy baja (" . number_format($produccion->ganancia_diaria, 2) . " kg/día).";
        }

        return view('produccion_carne.show', compact('produccion', 'total_mes', 'alerta_baja'));
    }

    public function edit(ProduccionCarne $produccion)
    {
        if ($produccion->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        $animales = Animal::where('inquilino_id', Auth::user()->inquilino_id)->get();
        return view('produccion_carne.edit', compact('produccion', 'animales'));
    }

    public function update(UpdateProduccionCarneRequest $request, ProduccionCarne $produccion)
    {
        $produccion->update($request->validated());

        // Recalcular campos derivados
        $ultimoAnterior = ProduccionCarne::where('animal_id', $produccion->animal_id)
            ->where('id', '<', $produccion->id)
            ->orderBy('fecha', 'desc')
            ->first();

        if ($ultimoAnterior) {
            $dias = max(1, Carbon::parse($produccion->fecha)->diffInDays(Carbon::parse($ultimoAnterior->fecha)));
            $produccion->peso_anterior  = $ultimoAnterior->peso;
            $produccion->ganancia_diaria = ($produccion->peso - $ultimoAnterior->peso) / $dias;
        } else {
            $produccion->peso_anterior  = null;
            $produccion->ganancia_diaria = null;
        }

        // Recalcular el siguiente registro si existe
        $siguiente = ProduccionCarne::where('animal_id', $produccion->animal_id)
            ->where('id', '>', $produccion->id)
            ->orderBy('fecha', 'asc')
            ->first();

        if ($siguiente) {
            $dias = max(1, Carbon::parse($siguiente->fecha)->diffInDays(Carbon::parse($produccion->fecha)));
            $siguiente->peso_anterior  = $produccion->peso;
            $siguiente->ganancia_diaria = ($siguiente->peso - $produccion->peso) / $dias;
            $siguiente->save();
        }

        $produccion->save();

        return redirect()->route('produccion.index')->with('success', 'Registro actualizado');
    }

    public function destroy(ProduccionCarne $produccion)
    {
        if ($produccion->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        $produccion->delete();

        return redirect()->route('produccion.index')->with('success', 'Registro eliminado');
    }
}