<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\AnimalPotrero;
use App\Models\Potrero;
use App\Models\Rotacion;
use Illuminate\Http\Request;
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
            'tamano' => 'required|numeric',
        ]);

        Potrero::create([
            'inquilino_id' => Auth::user()->inquilino_id,
            'nombre' => $request->nombre,
            'tamano' => $request->tamano,
            'tipo_pasto' => $request->tipo_pasto,
            'estado' => $request->estado ?? 'descanso',
        ]);

        return redirect()->route('potreros.index')->with('success', 'Potrero creado');
    }

    public function show($id)
    {
        $potrero = Potrero::where('id', $id)
            ->where('inquilino_id', Auth::user()->inquilino_id)
            ->firstOrFail();

        // `with('animal')`: antes se accedia a $a->animal dentro del @foreach de
        // la vista, lo que lanzaba una consulta POR CADA animal del potrero.
        $animalesDentro = AnimalPotrero::with('animal')
            ->where('potrero_id', $id)
            ->whereNull('fecha_salida')
            ->get();

        // Estas dos consultas estaban DENTRO de la vista (potreros/show.blade.php).
        $animalesDisponibles = Animal::where('inquilino_id', Auth::user()->inquilino_id)
            ->orderBy('codigo_interno')
            ->get();

        $rotaciones = Rotacion::where('potrero_id', $id)
            ->orderByDesc('fecha_entrada')
            ->get();

        return view('potreros.show', compact('potrero', 'animalesDentro', 'animalesDisponibles', 'rotaciones'));
    }

    public function edit(Potrero $potrero)
    {
        return view('potreros.edit', compact('potrero'));
    }

    public function update(Request $request, Potrero $potrero)
    {
        $datos = $request->validate([
            'nombre' => 'required',
            'tamano' => 'required|numeric',
            'tipo_pasto' => 'nullable|string',
            'estado' => 'required|string',
        ]);

        $potrero->update($datos);

        return redirect()->route('potreros.index')->with('success', 'Potrero actualizado correctamente');
    }

    public function asignarAnimales(Request $request, $id)
    {
        $potrero = Potrero::where('id', $id)
            ->where('inquilino_id', Auth::user()->inquilino_id)
            ->firstOrFail();
        $datos = $request->validate([
            'animales' => 'required|array',
            'animales.*' => 'integer',
        ]);

        // Solo se admiten animales de la ganaderia activa. Antes se creaba la
        // asignacion con el animal_id que viniera en la peticion sin comprobar
        // de quien era, asi que cualquiera podia meter el ganado de otra
        // ganaderia en su potrero enviando su id.
        $animales = Animal::where('inquilino_id', Auth::user()->inquilino_id)
            ->whereIn('id', $datos['animales'])
            ->pluck('id');

        if ($animales->count() !== count($datos['animales'])) {
            abort(403, 'Alguno de los animales no pertenece a tu ganaderia.');
        }

        foreach ($animales as $animal_id) {
            AnimalPotrero::create([
                'animal_id' => $animal_id,
                'potrero_id' => $id,
                'inquilino_id' => Auth::user()->inquilino_id,
                'fecha_entrada' => now()->toDateString(),
            ]);
        }

        $potrero->update([
            'estado' => 'ocupado',
            'fecha_ultimo_ingreso' => now()->toDateString(),
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

        $dias = (int) abs(now()->diffInDays($potrero->fecha_ultimo_ingreso ?? now()));
        $carga = $animales->count() / $potrero->tamano;

        Rotacion::create([
            'potrero_id' => $id,
            'inquilino_id' => Auth::user()->inquilino_id,
            'fecha_entrada' => $potrero->fecha_ultimo_ingreso,
            'fecha_salida' => now()->toDateString(),
            'dias_ocupacion' => $dias,
            'carga_animal' => $carga,
        ]);

        $potrero->update([
            'estado' => 'descanso',
            'fecha_ultimo_descanso' => now()->toDateString(),
        ]);

        return back()->with('success', 'Salida registrada y rotación creada');
    }

    /**
     * Elimina un potrero.
     *
     * No existia: la ruta DELETE del resource apuntaba a un metodo inexistente,
     * asi que borrar un potrero devolvia un error 500. El binding ya aplica el
     * filtro de ganaderia, de modo que un potrero ajeno da 404.
     */
    public function destroy(Potrero $potrero)
    {
        // Un potrero con historial no se borra arrastrando sus datos: se avisa.
        $tieneHistorial = AnimalPotrero::where('potrero_id', $potrero->id)->exists()
            || Rotacion::where('potrero_id', $potrero->id)->exists();

        if ($tieneHistorial) {
            return back()->with('error', 'No se puede eliminar: el potrero tiene animales o rotaciones registradas.');
        }

        $potrero->delete();

        return redirect()->route('potreros.index')->with('success', 'Potrero eliminado');
    }
}
