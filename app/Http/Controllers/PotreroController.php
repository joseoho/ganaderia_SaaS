<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Potrero;
use App\Models\AnimalPotrero;
use App\Models\Rotacion;
use Illuminate\Support\Facades\Auth;

class PotreroController extends Controller
{
    public function index()
    {
        $potreros = Potrero::where('inquilino_id', Auth::user()->inquilino_id)->get();
        return view('potreros.index', compact('potreros'));
    }

    public function create()
    {
        return view('potreros.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
            'tamaño' => 'required|numeric'
        ]);

        Potrero::create([
            'inquilino_id' => Auth::user()->inquilino_id,
            'nombre'       => $request->nombre,
            'tamaño'       => $request->tamaño,
            'tipo_pasto'   => $request->tipo_pasto,
            'estado'       => $request->estado ?? 'descanso',
        ]);

        return redirect()->route('potreros.index')->with('success', 'Potrero creado');
    }

    public function show($id)
    {
        $potrero = Potrero::where('id', $id)
            ->where('inquilino_id', Auth::user()->inquilino_id)
            ->firstOrFail();

        $animalesDentro = AnimalPotrero::where('potrero_id', $id)
            ->whereNull('fecha_salida')
            ->get();

        return view('potreros.show', compact('potrero', 'animalesDentro'));
    }

    public function edit(Potrero $potrero)
    {
        return view('potreros.edit', compact('potrero'));
    }

    public function update(Request $request, Potrero $potrero)
    {
        $request->validate([
            'nombre'     => 'required',
            'tamaño'     => 'required|numeric',
            'tipo_pasto' => 'nullable|string',
            'estado'     => 'required|string',
        ]);

        $potrero->update($request->all());

        return redirect()->route('potreros.index')->with('success', 'Potrero actualizado correctamente');
    }

    public function asignarAnimales(Request $request, $id)
    {
        $potrero = Potrero::where('id', $id)
            ->where('inquilino_id', Auth::user()->inquilino_id)
            ->firstOrFail();

        foreach ($request->animales as $animal_id) {
            AnimalPotrero::create([
                'animal_id'     => $animal_id,
                'potrero_id'    => $id,
                'inquilino_id'  => Auth::user()->inquilino_id,
                'fecha_entrada' => now()->toDateString()
            ]);
        }

        $potrero->update([
            'estado'               => 'ocupado',
            'fecha_ultimo_ingreso' => now()->toDateString()
        ]);

        return back()->with('success', 'Animales asignados');
    }

    public function salidaAnimales(Request $request, $id)
    {
        $potrero = Potrero::where('id', $id)
            ->where('inquilino_id', Auth::user()->inquilino_id)
            ->firstOrFail();

        $animales = AnimalPotrero::where('potrero_id', $id)
            ->whereNull('fecha_salida')
            ->get();

        foreach ($animales as $a) {
            $a->update(['fecha_salida' => now()->toDateString()]);
        }

        $dias  = now()->diffInDays($potrero->fecha_ultimo_ingreso);
        $carga = $animales->count() / $potrero->tamaño;

        Rotacion::create([
            'potrero_id'     => $id,
            'inquilino_id'   => Auth::user()->inquilino_id,
            'fecha_entrada'  => $potrero->fecha_ultimo_ingreso,
            'fecha_salida'   => now()->toDateString(),
            'dias_ocupacion' => $dias,
            'carga_animal'   => $carga
        ]);

        $potrero->update([
            'estado'                => 'descanso',
            'fecha_ultimo_descanso' => now()->toDateString()
        ]);

        return back()->with('success', 'Salida registrada y rotación creada');
    }
}