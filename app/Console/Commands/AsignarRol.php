<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Cambia el rol de un usuario.
 *
 * El rol decide si puede borrar registros (ver SoloAdministradorPuedeBorrar).
 * El resto del trabajo del dia a dia esta abierto a cualquier rol.
 */
class AsignarRol extends Command
{
    protected $signature = 'ganaderia:rol {email} {rol : administrador|operario}';

    protected $description = 'Cambia el rol de un usuario de la ganaderia';

    public function handle(): int
    {
        $rol = strtolower((string) $this->argument('rol'));

        if (! array_key_exists($rol, User::ROLES)) {
            $this->error('Rol no valido. Opciones: '.implode(', ', array_keys(User::ROLES)));

            return self::FAILURE;
        }

        $usuario = User::withoutTenantScope()
            ->where('email', $this->argument('email'))
            ->first();

        if ($usuario === null) {
            $this->error('No existe ningun usuario con el correo '.$this->argument('email').'.');

            return self::FAILURE;
        }

        $anterior = $usuario->nombreDelRol();

        $usuario->forceFill(['rol' => $rol])->save();

        $this->info(sprintf('%s: %s -> %s', $usuario->email, $anterior, $usuario->nombreDelRol()));

        return self::SUCCESS;
    }
}
