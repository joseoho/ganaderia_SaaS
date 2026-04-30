<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\TieneValidacionesInquilino;
use Illuminate\Support\Facades\Auth;

class UpdateRegistroVacunaRequest extends FormRequest
{
    use TieneValidacionesInquilino;

    public function authorize(): bool
    {
        if (!Auth::check()) return false;
        $vacuna = $this->route('registro_vacuna');
        return $vacuna && $vacuna->inquilino_id === Auth::user()->inquilino->inquilino_id;
    }

    public function rules(): array
    {
        return [
            'animal_id'       => ['required', $this->existsInquilino('animales')],
            'nombre'          => 'required|string|max:255',
            'lote'            => 'nullable|string|max:100',
            'proveedor'       => 'nullable|string|max:255',
            'via'             => 'nullable|string|max:50',
            'fecha_aplicacion'=> 'required|date',
            'dosis'           => 'nullable|string|max:100',
            'proxima_dosis'   => 'nullable|date|after:fecha_aplicacion',
            'observaciones'   => 'nullable|string|max:500',
        ];
    }
}