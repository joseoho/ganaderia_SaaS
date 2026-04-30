<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\TieneValidacionesInquilino;
use Illuminate\Support\Facades\Auth;

class StoreGenealogiaRequest extends FormRequest
{
    use TieneValidacionesInquilino;

    public function authorize(): bool { return Auth::check(); }

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