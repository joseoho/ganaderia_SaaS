<?php

namespace App\Console\Commands;

use App\Models\Inquilino;
use App\Models\Plan;
use App\Models\Suscripcion;
use App\Models\User;
use App\Support\DatosDeEjemplo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Crea una ganaderia y su usuario administrador desde la consola.
 *
 * Es la via de alta cuando el registro publico esta cerrado
 * (REGISTRO_PUBLICO=false), que es el valor por defecto. Las dos piezas se
 * crean en una transaccion: `users.inquilino_id` es NOT NULL, asi que un
 * usuario sin ganaderia no se puede guardar, y una ganaderia sin administrador
 * no le sirve a nadie.
 *
 * El correo queda verificado porque la cuenta la aprovisiona el operador de la
 * plataforma y entrega las credenciales en mano; si se prefiere que la persona
 * confirme su correo, usar --sin-verificar.
 */
class CrearGanaderia extends Command
{
    protected $signature = 'ganaderia:ganaderia
        {nombre : Nombre de la ganaderia}
        {email : Correo del administrador}
        {--persona= : Nombre de la persona (por defecto, el del administrador)}
        {--password= : Contrasena; si no se indica se genera una}
        {--sin-verificar : Deja el correo pendiente de verificar}
        {--demo : Siembra datos de ejemplo (potreros, animales, produccion, finanzas)}';

    protected $description = 'Crea una ganaderia y su usuario administrador';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (User::query()->where('email', $email)->exists()) {
            $this->error('Ya existe un usuario con el correo '.$email.'.');

            return self::FAILURE;
        }

        $password = (string) ($this->option('password') ?: Str::password(16));
        $verificado = ! $this->option('sin-verificar');

        $resultado = DB::transaction(function () use ($email, $password, $verificado) {
            $inquilino = Inquilino::create([
                'nombre' => (string) $this->argument('nombre'),
                'plan_suscripcion' => 'basico',
                'fecha_registro' => now()->toDateString(),
            ]);

            $persona = (string) ($this->option('persona') ?: 'Administrador '.$inquilino->nombre);

            $usuario = new User([
                'name' => $persona,
                'email' => $email,
                'password' => Hash::make($password),
                'inquilino_id' => $inquilino->id,
                'rol' => User::ROL_ADMINISTRADOR,
            ]);

            $usuario->email_verified_at = $verificado ? now() : null;
            $usuario->save();

            // Prueba gratis de 14 dias: la venta no depende de que paguen el
            // primer dia, y el operador puede extenderla aprobando un pago.
            $plan = Plan::query()->where('activo', true)->orderBy('orden')->first();

            if ($plan !== null) {
                Suscripcion::create([
                    'inquilino_id' => $inquilino->id,
                    'plan_id' => $plan->id,
                    'inicio' => now()->toDateString(),
                    'fin' => now()->addDays(14)->toDateString(),
                    'es_prueba' => true,
                ]);
            }

            return [$inquilino, $usuario];
        });

        [$inquilino, $usuario] = $resultado;

        // Los datos de ejemplo van FUERA de la transaccion del alta: si el
        // sembrado fallara, la ganaderia y su administrador ya estan creados y
        // se puede entrar igual (y volver a sembrar desde el panel despues).
        if ($this->option('demo')) {
            DatosDeEjemplo::sembrar($inquilino, $usuario, 'DEMO');
            $inquilino->forceFill(['es_demo' => true])->save();
        }

        $this->info(sprintf(
            'Ganaderia "%s" creada (id %d) con el administrador %s.',
            $inquilino->nombre,
            $inquilino->id,
            $usuario->email
        ));

        if ($this->option('demo')) {
            $this->line('Datos de ejemplo cargados: potreros, 6 animales, producción, sanidad y finanzas.');
            $this->line('El panel lo avisará, y se pueden vaciar de un botón cuando cargues los datos reales.');
        }

        if ($this->option('password') === null) {
            $this->line('Contrasena generada (se muestra una sola vez): <options=bold>'.$password.'</>');
        }

        if (! $verificado) {
            $this->warn('El correo queda pendiente de verificar: hace falta correo configurado para que pueda entrar.');
        }

        return self::SUCCESS;
    }
}
