<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Animal;
use Illuminate\Support\Facades\Auth;

class AnimalEtiquetaController extends Controller
{
    public function index()
    {
        $inquilino_id = Auth::user()->inquilino_id;

        if (!$inquilino_id) {
            return redirect()->route('home')->with('error', 'No tiene un inquilino asignado.');
        }

        $animales = Animal::where('inquilino_id', $inquilino_id)->get();

        if ($animales->isEmpty()) {
            return view('etiquetas.index', ['animales' => collect([])])->with('mensaje', 'No hay animales registrados');
        }

        return view('etiquetas.index', compact('animales'));
    }

    public function create() {}

    public function store(Request $request) {}

    public function show(string $id) {}

    public function edit(string $id) {}

    public function update(Request $request, string $id) {}

    public function destroy(string $id) {}

    public function generar(Request $request)
    {
        $request->validate([
            'animales' => 'required|array'
        ]);

        $animales = Animal::whereIn('id', $request->animales)
            ->where('inquilino_id', Auth::user()->inquilino_id)
            ->get();

        return view('etiquetas.generar', compact('animales'));
    }
}