<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\TieneValidacionesInquilino;
use Illuminate\Support\Facades\Auth;

class StoreAnimalRequest extends FormRequest
{
    use TieneValidacionesInquilino;

    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'codigo_interno'  => [
                'required',
                'string',
                'max:100',
                $this->uniqueInquilino('animales', 'codigo_interno'),
            ],
            // 'inquilino_id'   => 'required|exists:inquilinos,id',
            'categoria'       => 'required|string|max:50',
            'raza'            => 'required|string|max:50',
            'tipo'            => 'nullable|string|max:50',
            'fecha_nacimiento'=> 'required|date',
            'sexo'            => 'required|string|max:1',
            'peso_entrada'    => 'nullable|numeric|min:0',
            'estado'          => 'required|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'codigo_interno.unique' => 'Este código interno ya está registrado en su finca.',
            'codigo_interno.required' => 'El código interno es obligatorio.',
            'categoria.required' => 'La categoría es obligatoria.',
            'raza.required' => 'La raza es obligatoria.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'sexo.required' => 'El sexo es obligatorio.',
            'estado.required' => 'El estado es obligatorio.',
        ];
    }
}