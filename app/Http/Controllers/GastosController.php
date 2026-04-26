<?php

namespace App\Http\Controllers;

use App\Models\Gastos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GastosController extends Controller
{
    public function index()
    {
        $gastos = Gastos::where('inquilino_id', Auth::user()->inquilino->id)
            ->orderBy('fecha', 'desc')
            ->paginate(10);
        return view('gastos.index', compact('gastos'));
    }

    public function create()
    {
        return view('gastos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'categoria' => 'required|string',
            'monto' => 'required|numeric|min:0',
            'fecha' => 'required|date',
        ]);

        Gastos::create([
            'inquilino_id' => Auth::user()->inquilino->id,
            'categoria' => $request->categoria,
            'monto' => $request->monto,
            'fecha' => $request->fecha,
            'descripcion' => $request->descripcion,
        ]);

        return redirect()->route('gastos.index')->with('success', 'Gasto registrado correctamente.');
    }

    public function show(Gastos $gasto)
    {
        // Seguridad: Verificar que el gasto pertenezca al inquilino
        if ($gasto->inquilino_id !== Auth::user()->inquilino_id) {
            abort(403, 'No autorizado');
        }

        return view('gastos.show', compact('gasto'));
    }

    public function edit(Gastos $gasto)
    {
        // Seguridad: Verificar que el gasto pertenezca al inquilino
        if ($gasto->inquilino_id !== Auth::user()->inquilino_id) {
            abort(403, 'No autorizado');
        }

        return view('gastos.edit', compact('gasto'));
    }

    public function update(Request $request, Gastos $gasto)
    {
        if ($gasto->inquilino_id !== Auth::user()->inquilino_id) {
            abort(403, 'No autorizado');
        }

        $request->validate([
            'categoria' => 'required|string',
            'monto' => 'required|numeric|min:0',
            'fecha' => 'required|date',
        ]);

        $gasto->update([
            'categoria' => $request->categoria,
            'monto' => $request->monto,
            'fecha' => $request->fecha,
            'descripcion' => $request->descripcion,
        ]);

        return redirect()->route('gastos.index')->with('success', 'Gasto actualizado correctamente.');
    }

    public function destroy(Gastos $gasto)
    {
        if ($gasto->inquilino_id !== Auth::user()->inquilino_id) {
            abort(403, 'No autorizado');
        }

        $gasto->delete();

        return redirect()->route('gastos.index')->with('success', 'Gasto eliminado correctamente.');
    }
}
