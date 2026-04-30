<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\TieneValidacionesInquilino;
use Illuminate\Support\Facades\Auth;

class UpdateAnimalRequest extends FormRequest
{
    use TieneValidacionesInquilino;

    public function authorize(): bool
    {
        if (!Auth::check()) return false;
        
        $animal = $this->route('animal');
        return $animal && $animal->inquilino_id === Auth::user()->inquilino_id;
    }

    public function rules(): array
    {
        $animalId = $this->route('animal')->id ?? null;

        return [
            'codigo_interno'  => [
                'required',
                'string',
                'max:100',
                $this->uniqueInquilino('animales', 'codigo_interno', $animalId),
            ],
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
        ];
    }
}