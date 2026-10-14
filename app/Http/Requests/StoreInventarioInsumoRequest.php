<?php

namespace App\Http\Requests;

use App\Traits\TieneValidacionesInquilino;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreInventarioInsumoRequest extends FormRequest
{
    use TieneValidacionesInquilino;

    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'categoria' => 'nullable|string|max:100',
            'cantidad' => 'required|numeric|min:0',
            'unidad' => 'required|string|max:50',
            'minimo' => 'nullable|integer|min:0',
            'ubicacion' => 'nullable|string|max:255',
            'fecha_ingreso' => 'required|date',
            'descripcion' => 'nullable|string|max:500',
        ];
    }
}
