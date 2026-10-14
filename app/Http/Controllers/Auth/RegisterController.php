<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Inquilino;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Alta de una ganaderia nueva (auto-servicio).
 *
 * Antes esta pantalla estaba rota y ademas filtraba informacion:
 *
 *  - `create()` usaba `$data['inquilino']`, una clave que no existia porque su
 *    regla de validacion estaba comentada, asi que registrarse fallaba.
 *  - El formulario LISTABA todas las ganaderias existentes en un desplegable,
 *    de modo que cualquiera que abriera la pagina veia la cartera de clientes.
 *
 * El modelo correcto para un SaaS es el de aqui: quien se registra CREA su
 * propia ganaderia y queda como su administrador. Se puede cerrar el registro
 * publico con REGISTRO_PUBLICO=false; en ese caso las altas se hacen con
 * `php artisan ganaderia:ganaderia`.
 */
class RegisterController extends Controller
{
    use RegistersUsers;

    /**
     * A donde se va tras registrarse. La verificacion de correo sigue siendo
     * obligatoria para entrar al panel, asi que el aviso de verificar es el
     * destino natural.
     */
    protected $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('guest');
    }

    protected function showRegistrationForm()
    {
        // A proposito NO se pasan las ganaderias existentes: es informacion de
        // los clientes y quien se registra va a crear la suya.
        return view('auth.register');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'ganaderia' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [], [
            'name' => 'nombre',
            'ganaderia' => 'nombre de la ganadería',
            'email' => 'correo',
            'password' => 'contraseña',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function create(array $data)
    {
        // Las dos piezas van juntas: si fallara la segunda, no queremos una
        // ganaderia huerfana. Y al reves tampoco: `users.inquilino_id` es NOT
        // NULL, asi que un usuario sin ganaderia no se puede guardar.
        return DB::transaction(function () use ($data) {
            $inquilino = Inquilino::create([
                'nombre' => $data['ganaderia'],
                'plan_suscripcion' => 'basico',
                'fecha_registro' => now()->toDateString(),
            ]);

            return User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'inquilino_id' => $inquilino->id,
                // Quien crea la ganaderia es su administrador.
                'rol' => User::ROL_ADMINISTRADOR,
            ]);
        });
    }
}
