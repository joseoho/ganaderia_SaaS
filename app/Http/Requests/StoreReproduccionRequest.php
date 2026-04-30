<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\TieneValidacionesInquilino;
use Illuminate\Support\Facades\Auth;

class StoreReproduccionRequest extends FormRequest
{
    use TieneValidacionesInquilino;

    public function authorize(): bool { return Auth::check(); }

    public function rules(): array
    {
        return [
            'animal_id' => ['required', $this->existsInquilino('animales')],
            'tipo'      => 'required|string|max:50',
            'fecha'     => 'required|date',
            'toro'      => 'nullable|string|max:100',
            'resultado' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'animal_id.exists' => 'El animal seleccionado no existe o no pertenece a su finca.',
            'tipo.required' => 'El tipo de evento reproductivo es obligatorio.',
        ];
    }
}