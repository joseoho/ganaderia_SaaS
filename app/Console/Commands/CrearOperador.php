<?php

namespace App\Console\Commands;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Crea la cuenta del operador de la plataforma: la que revisa los comprobantes
 * de todas las fincas y da luz verde.
 *
 * No pertenece a ninguna ganaderia (`inquilino_id` nulo) y solo puede entrar al
 * panel de plataforma: no ve los datos de ninguna finca.
 */
class CrearOperador extends Command
{
    protected $signature = 'ganaderia:operador
        {email : Correo del operador}
        {nombre : Nombre y apellidos}
        {--password= : Contrasena; si no se indica se genera una}';

    protected $description = 'Crea la cuenta del operador que revisa los pagos de las fincas';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (User::query()->where('email', $email)->exists()) {
            $this->error('Ya existe un usuario con el correo '.$email.'.');

            return self::FAILURE;
        }

        $password = (string) ($this->option('password') ?: Str::password(16));

        $operador = new User([
            'name' => (string) $this->argument('nombre'),
            'email' => $email,
            'password' => Hash::make($password),
            'inquilino_id' => null,
            'rol' => Rol::Operador->value,
            'activo' => true,
        ]);

        // La cuenta la crea el dueño de la plataforma: se da el correo por
        // verificado, igual que en el alta de una finca.
        $operador->email_verified_at = now();
        $operador->save();

        $this->info('Operador creado: '.$operador->email.' (sin ganadería).');
        $this->line('Panel de pagos: '.url('/plataforma/pagos'));

        if ($this->option('password') === null) {
            $this->line('Contrasena generada (se muestra una sola vez): <options=bold>'.$password.'</>');
        }

        return self::SUCCESS;
    }
}
