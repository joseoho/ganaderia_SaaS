<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\TieneValidacionesInquilino;
use Illuminate\Support\Facades\Auth;

class UpdateProduccionLecheRequest extends FormRequest
{
    use TieneValidacionesInquilino;

    public function authorize(): bool
    {
        if (!Auth::check()) return false;
        $produccion = $this->route('produccion_leche');
        return $produccion && $produccion->inquilino_id === Auth::user()->inquilino_id;
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
}