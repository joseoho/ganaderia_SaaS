<?php

namespace App\Http\Controllers;

use App\Models\RegistroVacuna;
use App\Models\Animal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegistroVacunaController extends Controller
{
    public function index(Request $request)
    {
        $query = RegistroVacuna::where('inquilino_id', Auth::user()->inquilino_id);

        if ($request->filled('search')) {
            $query->whereHas('animal', function($q) use ($request) {
                $q->where('codigo_interno', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('nombre')) {
            $query->where('nombre', 'like', '%' . $request->nombre . '%');
        }

        $vacunas = $query->orderBy('fecha_aplicacion', 'desc')->paginate(10);

        return view('registro_vacunas.index', compact('vacunas'));
    }

    public function create()
    {
        $animales = Animal::where('inquilino_id', Auth::user()->inquilino_id)->get();
        return view('registro_vacunas.create', compact('animales'));
    }

    public function store(Request $request)
    {
        // $request->validate([
        //     'animal_id' => 'required',
        //     'nombre' => 'required|string|max:255',
        //     'fecha_aplicacion' => 'required|date',
        // ]);

        RegistroVacuna::create([
            'inquilino_id' => Auth::user()->inquilino_id,
            'animal_id' => $request->animal_id,
            'nombre' => $request->nombre,
            'lote' => $request->lote,
            'proveedor' => $request->proveedor,
            'via' => $request->via,
            'fecha_aplicacion' => $request->fecha,
            'dosis' => $request->dosis,
            'observaciones' => $request->observaciones,
        ]);

        return redirect()->route('registro_vacunas.index')->with('success', 'Vacuna registrada');
    }

    public function show(RegistroVacuna $vacuna)
    {
        if ($vacuna->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        return view('registro_vacunas.show', compact('vacuna'));
    }

    public function edit(RegistroVacuna $vacuna)
    {
        if ($vacuna->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        $animales = Animal::where('inquilino_id', Auth::user()->inquilino_id)->get();

        return view('registro_vacunas.edit', compact('vacuna', 'animales'));
    }

    public function update(Request $request, RegistroVacuna $vacuna)
    {
        if ($vacuna->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        // $request->validate([
        //     'animal_id' => 'required',
        //     'nombre' => 'required|string|max:255',
        //     'fecha_aplicacion' => 'required|date',
        // ]);

        $vacuna->update($request->all());

        return redirect()->route('registro_vacunas.index')->with('success', 'Vacuna actualizada');
    }

    public function destroy(RegistroVacuna $vacuna)
    {
        if ($vacuna->inquilino_id !== Auth::user()->inquilino_id) abort(403);

        $vacuna->delete();

        return redirect()->route('registro_vacunas.index')->with('success', 'Vacuna eliminada');
    }
}