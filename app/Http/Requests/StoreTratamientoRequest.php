<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\TieneValidacionesInquilino;
use Illuminate\Support\Facades\Auth;

class StoreTratamientoRequest extends FormRequest
{
    use TieneValidacionesInquilino;

    public function authorize(): bool { return Auth::check(); }

    public function rules(): array
    {
        return [
            'animal_id'    => ['required', $this->existsInquilino('animales')],
            'motivo'       => 'required|string|max:255',
            'medicamento'  => 'required|string|max:255',
            'via'          => 'nullable|string|max:50',
            'dosis'        => 'nullable|string|max:100',
            'fecha'        => 'required|date',
            'fecha_fin'    => 'nullable|date|after_or_equal:fecha',
            'observaciones'=> 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'animal_id.exists' => 'El animal seleccionado no existe o no pertenece a su finca.',
            'motivo.required' => 'El motivo del tratamiento es obligatorio.',
            'medicamento.required' => 'El medicamento es obligatorio.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ];
    }
}