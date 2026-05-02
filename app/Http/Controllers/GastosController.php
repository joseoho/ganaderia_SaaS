<?php

namespace App\Http\Controllers;

use App\Models\Gastos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreGastosRequest;
use App\Http\Requests\UpdateGastosRequest;


class GastosController extends Controller
{
    public function index(Request $request)
    {
         $query = Gastos::where('inquilino_id', Auth::user()->inquilino_id);

        if ($request->filled('search')) {
            $query->where('categoria', 'like', '%' . $request->search . '%')
                  ->orWhere('descripcion', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->fecha_hasta);
        }

        $gastos = $query->orderBy('fecha', 'desc')->paginate(10);

        return view('gastos.index', compact('gastos'));

    }

    public function create()
    {
        return view('gastos.create');
    }

    public function store(StoreGastosRequest $request)
    {
        $request->validate([
            'categoria' => 'required|string',
            'monto' => 'required|numeric|min:0',
            'fecha' => 'required|date',
        ]);

        Gastos::create([
            'inquilino_id' => Auth::user()->inquilino_id,
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

    public function update(UpdateGastosRequest $request, Gastos $gasto)
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
