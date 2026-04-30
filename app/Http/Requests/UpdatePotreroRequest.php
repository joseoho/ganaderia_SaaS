<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\TieneValidacionesInquilino;
use Illuminate\Support\Facades\Auth;

class UpdatePotreroRequest extends FormRequest
{
    use TieneValidacionesInquilino;
    public function authorize(): bool
    {
        if (!Auth::check()) return false;
        $potrero = $this->route('potrero');
        return $potrero && $potrero->inquilino_id === Auth::user()->inquilino->inquilino_id;
    }

    public function rules(): array
    {
        return [
            'nombre'    => 'required|string|max:255',
            'tamaño'    => 'required|numeric|min:0',
            'tipo_pasto'=> 'nullable|string|max:100',
            'estado'    => 'required|string|max:50',
        ];
    }
}