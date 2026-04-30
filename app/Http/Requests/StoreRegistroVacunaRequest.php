<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\TieneValidacionesInquilino;
use Illuminate\Support\Facades\Auth;

class StoreRegistroVacunaRequest extends FormRequest
{
    use TieneValidacionesInquilino;

    public function authorize(): bool { return Auth::check(); }

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

    public function messages(): array
    {
        return [
            'animal_id.exists' => 'El animal seleccionado no existe o no pertenece a su finca.',
            'nombre.required' => 'El nombre de la vacuna es obligatorio.',
            'fecha_aplicacion.required' => 'La fecha de aplicación es obligatoria.',
            'proxima_dosis.after' => 'La próxima dosis debe ser posterior a la fecha de aplicación.',
        ];
    }
}