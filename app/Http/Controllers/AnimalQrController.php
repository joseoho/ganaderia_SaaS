<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\ProduccionLeche;
use App\Models\ProduccionCarne;
use App\Models\Tratamiento;
use App\Models\RegistroVacuna;
use App\Models\Reproduccion;
use App\Models\Genealogia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AnimalQrController extends Controller
{
    public function show($id)
    {
        $animal = Animal::findOrFail($id);

        $produccion_leche = ProduccionLeche::where('animal_id', $id)->get();
        $produccion_carne = ProduccionCarne::where('animal_id', $id)->get();
        $tratamientos     = Tratamiento::where('animal_id', $id)->get();
        $vacunas          = RegistroVacuna::where('animal_id', $id)->get();
        $reproduccion     = Reproduccion::where('animal_id', $id)->get();
        $genealogia       = Genealogia::where('animal_id', $id)->first();

        return view('animales.qr_show', compact(
            'animal',
            'produccion_leche',
            'produccion_carne',
            'tratamientos',
            'vacunas',
            'reproduccion',
            'genealogia'
        ));
    }

    public function editarTodo($id)
    {
        $animal = Animal::with([
            'produccionLeche',
            'produccionCarne',
            'tratamientos',
            'registrosVacunas',
            'reproducciones',
            'genealogia'
        ])->findOrFail($id);

        if (Auth::check() && Auth::user()->inquilino_id !== $animal->inquilino_id) {
            abort(403);
        }

        return view('animales.qr_edita_todo', compact('animal'));
    }

    public function actualizarTodo(Request $request, $id)
    {
        $animal = Animal::with([
            'produccionLeche',
            'produccionCarne',
            'tratamientos',
            'registrosVacunas',
            'reproducciones',
            'genealogia'
        ])->findOrFail($id);

        if (Auth::check() && Auth::user()->inquilino_id !== $animal->inquilino_id) {
            abort(403);
        }

        // 1) Actualizar datos del animal
        $animal->update([
            'codigo_interno'   => $request->codigo_interno,
            'raza'             => $request->raza,
            'sexo'             => $request->sexo,
            'peso_entrada'     => $request->peso_actual,
            'fecha_nacimiento' => $request->fecha_nacimiento,
            'estado'           => $request->estado,
        ]);

        // 2) Genealogía (padre / madre) – corregido: usar padre_id y madre_id
        if ($request->filled('padre_id') || $request->filled('madre_id')) {
            $genealogia = $animal->genealogia ?: new Genealogia(['animal_id' => $animal->id]);

            $genealogia->padre_id = $request->padre_id;
            $genealogia->madre_id = $request->madre_id;
            $genealogia->save();
        }

        // 3) Última producción de leche
        if ($request->filled('ultima_leche')) {
            $produccionLeche = $animal->produccionLeche->last() ?: new ProduccionLeche([
                'animal_id'    => $animal->id,
                'fecha'        => now()->toDateString(),
                'inquilino_id' => Auth::user()->inquilino_id,
            ]);

            $produccionLeche->litros = $request->ultima_leche;
            $produccionLeche->save();
        }

        // 4) Última producción de carne – corregido: asignar peso, no ganancia
        if ($request->filled('ultimo_peso')) {
            $produccionCarne = $animal->produccionCarne->last() ?: new ProduccionCarne([
                'animal_id'    => $animal->id,
                'fecha'        => now()->toDateString(),
                'inquilino_id' => Auth::user()->inquilino_id,
            ]);

            $produccionCarne->peso = $request->ultimo_peso;
            $produccionCarne->save();
        }

        // 5) Último tratamiento
        if ($request->filled('ultimo_tratamiento')) {
            $tratamiento = $animal->tratamientos->last() ?: new Tratamiento([
                'animal_id'   => $animal->id,
                'fecha'       => now()->toDateString(),
                'inquilino_id'=> Auth::user()->inquilino_id,
            ]);

            $tratamiento->motivo = $request->ultimo_tratamiento;
            $tratamiento->save();
        }

        // 6) Última vacuna – corregido: usar fecha_aplicacion
        if ($request->filled('ultima_vacuna')) {
            $vacuna = $animal->registrosVacunas->last() ?: new RegistroVacuna([
                'animal_id'       => $animal->id,
                'fecha_aplicacion'=> now()->toDateString(),
                'inquilino_id'    => Auth::user()->inquilino_id,
            ]);

            $vacuna->nombre = $request->ultima_vacuna;
            $vacuna->save();
        }

        return redirect()
            ->route('animal.qr.show', $animal->id)
            ->with('success', 'Datos del animal actualizados correctamente');
    }
}