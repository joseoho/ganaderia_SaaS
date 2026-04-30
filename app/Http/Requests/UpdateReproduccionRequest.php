<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\TieneValidacionesInquilino;
use Illuminate\Support\Facades\Auth;

class UpdateReproduccionRequest extends FormRequest
{
    use TieneValidacionesInquilino;

    public function authorize(): bool
    {
        if (!Auth::check()) return false;
        $reproduccion = $this->route('reproduccion');
        return $reproduccion && $reproduccion->inquilino_id === Auth::user()->inquilino->inquilino_id;
    }
//Auth::user()->inquilino->nombre
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
}