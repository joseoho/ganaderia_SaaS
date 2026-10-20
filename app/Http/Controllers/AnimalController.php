<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnimalRequest;
use App\Http\Requests\UpdateAnimalRequest;
use App\Models\Animal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnimalController extends Controller
{
    public function index(Request $request)
    {
        // $query = Animal::query();
        $query = Animal::where('inquilino_id', Auth::user()->inquilino_id); // 👈 filtro por usuario logueado
        // 🔍 Búsqueda por código interno o raza
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('codigo_interno', 'like', '%'.$request->search.'%')
                    ->orWhere('raza', 'like', '%'.$request->search.'%');
            });
        }

        // 📂 Filtro por categoría
        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        // 🐄 Filtro por sexo
        if ($request->filled('sexo')) {
            $query->where('sexo', $request->sexo);
        }

        // 📅 Filtro por rango de fechas de nacimiento
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_nacimiento', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_nacimiento', '<=', $request->fecha_hasta);
        }

        // ⚡ Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Resultados paginados
        $animales = $query->orderBy('fecha_nacimiento', 'desc')->paginate(10);

        return view('animales.index', compact('animales'));
    }

    public function create()
    {
        return view('animales.create');
    }

    public function store(StoreAnimalRequest $request)
    {

        $data = $request->validated();
        // Agregar el inquilino_id a los datos
        $data['inquilino_id'] = Auth::user()->inquilino_id; // 👈 multi-tenant automático
        // Crear el nuevo registro de Animal con los datos
        Animal::create($data);

        // Animal::create([
        //     'inquilino_id' => Auth::user()->inquilino_id, // 👈 multi-tenant automático
        //     'codigo_interno' => $request->codigo_interno,
        //     'categoria' => $request->categoria,
        //     'raza' => $request->raza,
        //     'tipo' => $request->tipo,
        //     'fecha_nacimiento' => $request->fecha_nacimiento,
        //     'sexo' => $request->sexo,
        //     'peso_entrada' => $request->peso_actual,
        //     'estado' => $request->estado,
        // ]);

        return redirect()->route('animales.index')
            ->with('success', 'Animal registrado correctamente');
    }

    public function show(Animal $animal)
    {
        // 👇 aseguramos que solo pueda ver animales de su inquilino
        if ($animal->inquilino_id !== Auth::user()->inquilino_id) {
            abort(403, 'No autorizado');
        }

        // El potrero ACTUAL es la asignacion ABIERTA (sin fecha de salida).
        //
        // Antes lo resolvia la propia vista con
        // `$animal->animalPotreros->last()->potrero`, y tenia dos problemas: si
        // el animal no habia estado nunca en ningun potrero, `last()` es null y
        // la ficha entera daba error 500 ("Attempt to read property potrero on
        // null"); y si habia estado, cogia la ultima asignacion aunque ya
        // estuviera cerrada, mostrando un potrero del que el animal ya se habia
        // ido.
        $potreroActual = $animal->animalPotreros()
            ->whereNull('fecha_salida')
            ->with('potrero')
            ->latest('fecha_entrada')
            ->first()
            ?->potrero;

        return view('animales.show', compact('animal', 'potreroActual'));
    }

    public function edit(Animal $animal)
    {
        return view('animales.edit', compact('animal'));
    }

    public function update(UpdateAnimalRequest $request, Animal $animal)
    {
        // $request->validate([
        //     'codigo_interno' => 'required|string',
        //     'categoria' => 'required|string',
        //     'raza' => 'required|string',
        //     'tipo' => 'required|string',
        //     'fecha_nacimiento' => 'required|date',
        //     'sexo' => 'required|string',
        //     'peso_entrada' => 'nullable|numeric',
        //     'estado' => 'required|string',
        // ]);

        // $animal->update($request->all());

        // return redirect()->route('animales.index')->with('success', 'Animal actualizado correctamente');
        $animal->update($request->validated());

        return redirect()->route('animales.index')->with('success', 'Animal actualizado correctamente');
    }

    public function destroy(Animal $animal)
    {
        if ($animal->inquilino_id !== Auth::user()->inquilino_id) {
            abort(403, 'No autorizado');
        }

        // Un animal con historial NO se borra.
        //
        // Las claves foraneas de produccion, tratamientos y demas apuntan al
        // animal con ON DELETE SET NULL: borrarlo dejaba esas fichas sin animal,
        // es decir, datos huerfanos que ya no se pueden atribuir a nadie. Mismo
        // criterio que en potreros: se avisa en vez de arrastrar el historial.
        $historial = [
            'producción de leche' => $animal->produccionLeche()->exists(),
            'producción de carne' => $animal->produccionCarne()->exists(),
            'tratamientos' => $animal->tratamientos()->exists(),
            'vacunas' => $animal->registrosVacunas()->exists(),
            'reproducciones' => $animal->reproducciones()->exists(),
            'movilizaciones' => $animal->movilizaciones()->exists(),
            'pasos por potreros' => $animal->animalPotreros()->exists(),
            'genealogía' => $animal->genealogia()->exists(),
        ];

        $conHistorial = array_keys(array_filter($historial));

        if ($conHistorial !== []) {
            return back()->with(
                'error',
                'No se puede eliminar: el animal tiene historial ('.implode(', ', $conHistorial).'). '
                .'Bórralo primero por módulos si de verdad quieres deshacerte de esos datos.'
            );
        }

        $animal->delete();

        return redirect()->route('animales.index')->with('success', 'Animal eliminado');
    }

    public function qr(Animal $animal)
    {
        if ($animal->inquilino_id !== Auth::user()->inquilino_id) {
            abort(403);
        }

        $url = route('animal.qr.show', $animal->id);

        return view('animales.qr', compact('animal', 'url'));
    }
}
