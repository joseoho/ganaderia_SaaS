<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreVentaRequest;
use App\Http\Requests\UpdateVentaRequest;

class VentaController extends Controller
{
    public function index(Request $request)
    {
        $query = Venta::where('inquilino_id', Auth::user()->inquilino_id);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('cliente', 'like', '%' . $request->search . '%')
                  ->orWhere('descripcion', 'like', '%' . $request->search . '%');
            });
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

    public function store(StoreVentaRequest $request)
    {
        Venta::create([
            'inquilino_id' => Auth::user()->inquilino_id,
            'cliente'      => $request->cliente,
            'descripcion'  => $request->descripcion,
            'fecha'        => $request->fecha,
            'monto_total'  => $request->monto_total,
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

    public function update(UpdateVentaRequest $request, Venta $venta)
    {
        $venta->update($request->validated());

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