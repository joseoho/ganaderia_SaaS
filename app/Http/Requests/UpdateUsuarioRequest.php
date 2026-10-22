<?php

namespace App\Http\Requests;

use App\Enums\Permiso;
use App\Enums\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Edicion de un usuario de la ganaderia.
 *
 * La contraseña es opcional: si se deja vacia, la que tenia sigue valiendo.
 */
class UpdateUsuarioRequest extends FormRequest
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
        $usuario = $this->route('usuario');
        $id = is_object($usuario) ? $usuario->getKey() : $usuario;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($id),
            ],
            'rol' => ['required', Rule::in(array_column(Rol::cases(), 'value'))],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
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
