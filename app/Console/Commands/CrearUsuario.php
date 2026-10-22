<?php

namespace App\Console\Commands;

use App\Enums\Rol;
use App\Models\Inquilino;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Crea un usuario dentro de una ganaderia que ya existe.
 *
 * Es el camino para dar de alta a un trabajador sin abrir el registro publico
 * (que crea una ganaderia nueva). Por defecto entra como operario: puede
 * registrar y editar, pero no borrar.
 */
class CrearUsuario extends Command
{
    protected $signature = 'ganaderia:usuario
        {email : Correo del usuario}
        {nombre : Nombre y apellidos}
        {--rol=usuario : administrador|usuario}
        {--inquilino= : Id de la ganaderia (si solo hay una, se usa esa)}
        {--password= : Contrasena; si no se indica se genera una}';

    protected $description = 'Crea un usuario dentro de una ganaderia existente';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $rol = strtolower((string) $this->option('rol'));

        if (! array_key_exists($rol, Rol::etiquetas())) {
            $this->error('Rol no valido. Opciones: '.implode(', ', array_keys(Rol::etiquetas())));

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error('Ya existe un usuario con el correo '.$email.'.');

            return self::FAILURE;
        }

        $inquilino = $this->resolverInquilino();

        if ($inquilino === null) {
            return self::FAILURE;
        }

        $password = (string) ($this->option('password') ?: Str::password(16));

        $usuario = new User([
            'name' => (string) $this->argument('nombre'),
            'email' => $email,
            'password' => Hash::make($password),
            'inquilino_id' => $inquilino->id,
            'rol' => $rol,
        ]);

        // La verificacion de correo queda pendiente: el usuario tendra que
        // confirmarlo con el enlace que reciba.
        $usuario->email_verified_at = null;
        $usuario->save();

        $this->info(sprintf(
            'Usuario creado: %s (%s) en la ganaderia "%s".',
            $usuario->email,
            $usuario->nombreDelRol(),
            $inquilino->nombre
        ));

        if ($this->option('password') === null) {
            $this->line('Contrasena generada (se muestra una sola vez): <options=bold>'.$password.'</>');
        }

        return self::SUCCESS;
    }

    private function resolverInquilino(): ?Inquilino
    {
        $id = $this->option('inquilino');

        if ($id !== null) {
            $inquilino = Inquilino::find($id);

            if ($inquilino === null) {
                $this->error('No existe la ganaderia con id '.$id.'.');
            }

            return $inquilino;
        }

        $todos = Inquilino::orderBy('id')->get();

        if ($todos->isEmpty()) {
            $this->error('No hay ninguna ganaderia: crea una primero.');

            return null;
        }

        if ($todos->count() > 1) {
            $this->error('Hay varias ganaderias; indica cual con --inquilino=ID. Usa `php artisan ganaderia:usuarios` para verlas.');

            return null;
        }

        return $todos->first();
    }
}
