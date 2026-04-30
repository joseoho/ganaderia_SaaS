<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\TieneValidacionesInquilino;
use Illuminate\Support\Facades\Auth;

class StoreGastosRequest extends FormRequest
{
      use TieneValidacionesInquilino;
      
    public function authorize(): bool { return Auth::check(); }

    public function rules(): array
    {
        return [
            'categoria'   => 'required|string|max:100',
            'monto'       => 'required|numeric|min:0',
            'fecha'       => 'required|date',
            'descripcion' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'categoria.required' => 'La categoría del gasto es obligatoria.',
            'monto.required' => 'El monto es obligatorio.',
            'monto.min' => 'El monto no puede ser negativo.',
            'fecha.required' => 'La fecha es obligatoria.',
        ];
    }
}