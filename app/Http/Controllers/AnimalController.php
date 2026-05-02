<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreAnimalRequest;       
use App\Http\Requests\UpdateAnimalRequest;

class AnimalController extends Controller
{
    public function index(Request $request)
{
    // $query = Animal::query();
    $query = Animal::where('inquilino_id', Auth::user()->inquilino_id); // 👈 filtro por usuario logueado
    // 🔍 Búsqueda por código interno o raza
    if ($request->filled('search')) {
        $query->where(function($q) use ($request) {
            $q->where('codigo_interno', 'like', '%' . $request->search . '%')
              ->orWhere('raza', 'like', '%' . $request->search . '%');
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

        return view('animales.show', compact('animal'));

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
        // $animal->delete();
        // return redirect()->route('animales.index')->with('success', 'Animal eliminado correctamente');
        if ($animal->inquilino_id !== Auth::user()->inquilino_id) {
            abort(403, 'No autorizado');
        }

        $animal->delete();
        return redirect()->route('animales.index')->with('success', 'Animal eliminado');

    }

    public function qr(Animal $animal)
{
    if ($animal->inquilino_id !== Auth::user()->inquilino_id) abort(403);

    $url = route('animal.qr.show', $animal->id);

    return view('animales.qr', compact('animal', 'url'));
}

}
