<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\ProduccionLeche;
use App\Models\ProduccionCarne;
use App\Models\Tratamiento;
use App\Models\RegistroVacuna;
use App\Models\Reproduccion;
use App\Models\Genealogia;

class AnimalQrController extends Controller
{
    public function show($id)
    {
        $animal = Animal::findOrFail($id);

        $produccion_leche = ProduccionLeche::where('animal_id', $id)->get();
        $produccion_carne = ProduccionCarne::where('animal_id', $id)->get();
        $tratamientos = Tratamiento::where('animal_id', $id)->get();
        $vacunas = RegistroVacuna::where('animal_id', $id)->get();
        $reproduccion = Reproduccion::where('animal_id', $id)->get();
        $genealogia = Genealogia::where('animal_id', $id)->first();

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
}