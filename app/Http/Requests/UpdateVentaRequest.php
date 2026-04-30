<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Traits\TieneValidacionesInquilino;

class UpdateVentaRequest extends FormRequest
{
     use TieneValidacionesInquilino;
    public function authorize(): bool
    {
        if (!Auth::check()) return false;
        $venta = $this->route('venta');
        return $venta && $venta->inquilino_id === Auth::user()->inquilino->inquilino_id;
    }

    public function rules(): array
    {
        return [
            'cliente'     => 'required|string|max:255',
            'descripcion' => 'required|string|max:500',
            'fecha'       => 'required|date',
            'monto_total' => 'required|numeric|min:0',
        ];
    }
}