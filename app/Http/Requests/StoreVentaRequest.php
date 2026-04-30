<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Traits\TieneValidacionesInquilino;
class StoreVentaRequest extends FormRequest
{
        use TieneValidacionesInquilino;
    public function authorize(): bool { return Auth::check(); }

    public function rules(): array
    {
        return [
            'cliente'     => 'required|string|max:255',
            'descripcion' => 'required|string|max:500',
            'fecha'       => 'required|date',
            'monto_total' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'cliente.required' => 'El cliente es obligatorio.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'monto_total.required' => 'El monto total es obligatorio.',
            'monto_total.min' => 'El monto no puede ser negativo.',
        ];
    }
}