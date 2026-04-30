<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\TieneValidacionesInquilino;
use Illuminate\Support\Facades\Auth;

class UpdateGenealogiaRequest extends FormRequest
{
    use TieneValidacionesInquilino;

    public function authorize(): bool
    {
        if (!Auth::check()) return false;
        $genealogia = $this->route('genealogium');
        return $genealogia && $genealogia->inquilino_id === Auth::user()->inquilino->inquilino_id;
    }

    public function rules(): array
    {
        return [
            'animal_id'     => ['required', $this->existsInquilino('animales')],
            'padre_id'      => ['nullable', $this->existsInquilino('animales')],
            'madre_id'      => ['nullable', $this->existsInquilino('animales')],
            'observaciones' => 'nullable|string|max:500',
        ];
    }
}