<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreCompraRequest;
use App\Http\Requests\UpdateCompraRequest;

class CompraController extends Controller
{
    public function index(Request $request)
    {
        $query = Compra::where('inquilino_id', Auth::user()->inquilino_id);

        if ($request->filled('search')) {
            $query->where('proveedor', 'like', '%' . $request->search . '%')
                  ->orWhere('descripcion', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->fecha_hasta);
        }

        $compras = $query->orderBy('fecha', 'desc')->paginate(10);

        return view('compras.index', compact('compras'));
    }

    public function create()
    {
        return view('compras.create');
    }

    public function store(Request $request)
    {
        // $request->validate([
        //     'fecha' => $request->fecha,
        //     'proveedor' => $request->proveedor,
        //     'descripcion' => $request->descripcion,
        //     'monto_total' => $request->monto,
        // ]);

        Compra::create([
            'inquilino_id' => Auth::user()->inquilino_id,
            'fecha' => $request->fecha,
            'proveedor' => $request->proveedor,
            'descripcion' => $request->descripcion,
            'monto_total' => $request->monto,
        ]);

        return redirect()->route('compras.index')->with('success', 'Compra registrada correctamente');
    }

    public function show(Compra $compra)
    {
        if ($compra->inquilino_id !== Auth::user()->inquilino_id) {
            abort(403, 'No autorizado');
        }
        return view('compras.show', compact('compra'));
    }

    public function edit(Compra $compra)
    {
        if ($compra->inquilino_id !== Auth::user()->inquilino_id) {
            abort(403, 'No autorizado');
        }
        return view('compras.edit', compact('compra'));
    }

    public function update(Request $request, Compra $compra)
    {
        if ($compra->inquilino_id !== Auth::user()->inquilino_id) {
            abort(403, 'No autorizado');
        }

        $request->validate([
            'fecha' => 'required|date',    
            'proveedor' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'monto_total' => 'required|numeric',
        ]);

        $compra->update($request->all());

        return redirect()->route('compras.index')->with('success', 'Compra actualizada correctamente');
    }

    public function destroy(Compra $compra)
    {
        if ($compra->inquilino_id !== Auth::user()->inquilino_id) {
            abort(403, 'No autorizado');
        }

        $compra->delete();
        return redirect()->route('compras.index')->with('success', 'Compra eliminada');
    }
}