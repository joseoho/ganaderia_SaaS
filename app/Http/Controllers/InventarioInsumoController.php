<?php

namespace App\Http\Controllers;

use App\Models\InventarioInsumo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UpdateInventarioInsumoRequest;
use App\Http\Requests\StoreInventarioInsumoRequest;

class InventarioInsumoController extends Controller
{
    public function index(Request $request)
    {
        $query = InventarioInsumo::where('inquilino_id', Auth::user()->inquilino_id);

        if ($request->filled('search')) {
            $query->where('nombre', 'like', '%' . $request->search . '%')
                  ->orWhere('categoria', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        $insumos = $query->orderBy('nombre')->paginate(12);

        return view('inventario_insumos.index', compact('insumos'));
    }

    public function create()
    {
        return view('inventario_insumos.create');
    }

    public function store(StoreInventarioInsumoRequest $request)
    {

        $request->validate([
            'nombre' => 'required|string|max:255',
            'cantidad' => 'required|integer|min:0',
            'unidad' => 'required|string|max:50',
        ]);

        InventarioInsumo::create([
            'inquilino_id' => Auth::user()->inquilino_id,
            'nombre' => $request->nombre,
            'categoria' => $request->categoria,
            'cantidad' => $request->cantidad,
            'unidad' => $request->unidad,
            'minimo' => $request->minimo,
            'fecha_ingreso' => $request->fecha_ingreso,
            'descripcion' => $request->descripcion,
        ]);

        return redirect()->route('inventario.index')->with('success', 'Insumo registrado');
    }

    public function show(InventarioInsumo $inventario)
    {
        if ($inventario->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        return view('inventario_insumos.show', compact('inventario'));
    }

    public function edit(InventarioInsumo $inventario)
    {
        if ($inventario->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        return view('inventario_insumos.edit', compact('inventario'));
    }

    public function update(Request $request, InventarioInsumo $inventario)
    {

        if ($inventario->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'cantidad' => 'required|integer|min:0',
            'unidad' => 'required|string|max:50',
        ]);

        $inventario->update($request->all());

        return redirect()->route('inventario.index')->with('success', 'Insumo actualizado');
    }

    public function destroy(InventarioInsumo $inventario)
    {
        if ($inventario->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        $inventario->delete();

        return redirect()->route('inventario.index')->with('success', 'Insumo eliminado');
    }
}