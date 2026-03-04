<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Animal;
use Illuminate\Support\Facades\Auth;


class AnimalEtiquetaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Verificar si el usuario está autenticado
            if (!Auth::check()) {
                dd('Usuario no autenticado');
            }

            // Verificar el inquilino_id
            $inquilino_id = Auth::user()->inquilino_id;
            if (!$inquilino_id) {
                dd('Usuario no tiene inquilino_id');
            }

            // Obtener animales
            $animales = Animal::where('inquilino_id', $inquilino_id)->get();
            
            // Verificar si hay animales
            if ($animales->isEmpty()) {
                return view('etiquetas.index', ['animales' => collect([])])->with('mensaje', 'No hay animales registrados');
            }

            return view('etiquetas.index', compact('animales'));

        } catch (\Exception $e) {
            dd('Error: ' . $e->getMessage());
        }

    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

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
