<?php

namespace App\Http\Requests;

use App\Traits\TieneValidacionesInquilino;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StorePotreroRequest extends FormRequest
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
            'tamano' => 'required|numeric|min:0',
            'tipo_pasto' => 'nullable|string|max:100',
            'estado' => 'nullable|string|max:50',
        ];
    }
}
