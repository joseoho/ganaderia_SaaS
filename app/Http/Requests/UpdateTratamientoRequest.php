<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\TieneValidacionesInquilino;
use Illuminate\Support\Facades\Auth;

class UpdateTratamientoRequest extends FormRequest
{
    use TieneValidacionesInquilino;

    public function authorize(): bool
    {
        if (!Auth::check()) return false;
        $tratamiento = $this->route('tratamiento');
        return $tratamiento && $tratamiento->inquilino_id === Auth::user()->inquilino->inquilino_id;
    }

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
}