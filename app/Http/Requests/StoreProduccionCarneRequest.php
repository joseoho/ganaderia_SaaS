<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\TieneValidacionesInquilino;

use Illuminate\Support\Facades\Auth;


class StoreProduccionCarneRequest extends FormRequest
{
    use TieneValidacionesInquilino;

    public function authorize(): bool { return Auth::check(); }

    public function rules(): array
    {
        return [
            'animal_id' => ['required', $this->existsInquilino('animales')],
            'fecha'     => 'required|date',
            'peso'      => 'required|numeric|min:0',
            'observaciones' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'animal_id.exists' => 'El animal seleccionado no existe o no pertenece a su finca.',
            'peso.required' => 'Debe ingresar el peso del animal.',
            'peso.min' => 'El peso no puede ser negativo.',
        ];
    }
}