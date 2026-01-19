<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VentaController extends Controller
{
    public function index(Request $request)
    {
        $query = Venta::where('inquilino_id', Auth::user()->inquilino_id);

        if ($request->filled('search')) {
            $query->where('cliente', 'like', '%' . $request->search . '%')
                  ->orWhere('descripcion', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->fecha_hasta);
        }

        $ventas = $query->orderBy('fecha', 'desc')->paginate(10);

        return view('ventas.index', compact('ventas'));
    }

    public function create()
    {
        return view('ventas.create');
    }

    public function store(Request $request)
    {
        // $request->validate([
        //     'cliente' => 'required|string|max:255',
        //     'descripcion' => 'required|string',
        //     'fecha' => 'required|date',
        //     'monto_total' => 'required|numeric',
        // ]);

        Venta::create([
            'inquilino_id' => Auth::user()->inquilino_id,
            'cliente' => $request->cliente,
            'descripcion' => $request->descripcion,
            'fecha' => $request->fecha,
            'monto_total' => $request->monto,
        ]);

        return redirect()->route('ventas.index')->with('success', 'Venta registrada correctamente');
    }

    public function show(Venta $venta)
    {
        if ($venta->inquilino_id !== Auth::user()->inquilino_id) {
            abort(403, 'No autorizado');
        }
        return view('ventas.show', compact('venta'));
    }

    public function edit(Venta $venta)
    {
        if ($venta->inquilino_id !== Auth::user()->inquilino_id) {
            abort(403, 'No autorizado');
        }
        return view('ventas.edit', compact('venta'));
    }

    public function update(Request $request, Venta $venta)
    {
        if ($venta->inquilino_id !== Auth::user()->inquilino_id) {
            abort(403, 'No autorizado');
        }

        $request->validate([
            'cliente' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'fecha' => 'required|date',
            'monto' => 'required|numeric',
        ]);

        $venta->update($request->all());

        return redirect()->route('ventas.index')->with('success', 'Venta actualizada correctamente');
    }

    public function destroy(Venta $venta)
    {
        if ($venta->inquilino_id !== Auth::user()->inquilino_id) {
            abort(403, 'No autorizado');
        }

        $venta->delete();
        return redirect()->route('ventas.index')->with('success', 'Venta eliminada');
    }
}