<?php

namespace App\Http\Requests;

use App\Enums\Permiso;
use App\Enums\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Alta de un usuario de la ganaderia.
 *
 * El rol solo puede ser uno de los dos del sistema: cualquier otro valor se
 * rechaza aunque venga en el formulario manipulado.
 */
class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->puede(Permiso::UsuariosGestionar) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'rol' => ['required', Rule::in(array_column(Rol::deFinca(), 'value'))],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            // El administrador que da de alta a un trabajador responde por el:
            // si lo marca, la cuenta entra sin esperar al correo.
            'verificado' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Ya existe una cuenta con ese correo.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las dos contraseñas no coinciden.',
            'rol.in' => 'Ese rol no existe.',
        ];
    }
}
