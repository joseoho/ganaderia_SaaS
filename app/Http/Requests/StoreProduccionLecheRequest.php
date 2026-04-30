<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\TieneValidacionesInquilino;
use Illuminate\Support\Facades\Auth;    

class StoreProduccionLecheRequest extends FormRequest
{
    use TieneValidacionesInquilino;

    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'animal_id' => [
                'required',
                $this->existsInquilino('animales'),
            ],
            'fecha'     => 'required|date',
            'turno'     => 'nullable|string|max:20',
            'litros'    => 'required|numeric|min:0',
            'observaciones' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'animal_id.exists' => 'El animal seleccionado no existe o no pertenece a su finca.',
            'litros.required' => 'Debe ingresar la cantidad de litros.',
            'litros.min' => 'Los litros no pueden ser negativos.',
        ];
    }
}