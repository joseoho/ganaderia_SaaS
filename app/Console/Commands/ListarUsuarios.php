<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Lista los usuarios con su ganaderia y su rol.
 *
 * Existe porque la aplicacion no tiene pantalla de gestion de equipo: al
 * eliminar el controlador de usuarios (codigo muerto) la via real de asignar
 * roles es la consola.
 */
class ListarUsuarios extends Command
{
    protected $signature = 'ganaderia:usuarios {--inquilino= : Id de la ganaderia}';

    protected $description = 'Lista los usuarios, su ganaderia y su rol';

    public function handle(): int
    {
        $consulta = User::withoutTenantScope()->with('inquilino');

        if ($this->option('inquilino') !== null) {
            $consulta->where('inquilino_id', $this->option('inquilino'));
        }

        $usuarios = $consulta->orderBy('inquilino_id')->orderBy('id')->get();

        if ($usuarios->isEmpty()) {
            $this->warn('No hay usuarios.');

            return self::SUCCESS;
        }

        $this->table(
            ['Id', 'Ganaderia', 'Nombre', 'Correo', 'Rol', 'Verificado'],
            $usuarios->map(fn (User $u) => [
                $u->id,
                $u->inquilino->nombre,
                $u->name,
                $u->email,
                $u->nombreDelRol(),
                $u->email_verified_at !== null ? 'si' : 'no',
            ])->all()
        );

        return self::SUCCESS;
    }
}
