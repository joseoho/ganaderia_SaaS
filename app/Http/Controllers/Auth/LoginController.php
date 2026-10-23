<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * A dónde va cada uno al entrar.
     *
     * El operador de la plataforma no tiene ganadería: si lo mandáramos a /home
     * acabaría en el panel de una finca que no es suya, y el filtro de tenant
     * (que falla cerrado) reventaría en la primera consulta. Va directo a pagos.
     */
    protected function redirectPath(): string
    {
        return Auth::user()?->esOperador()
            ? route('plataforma.pagos')
            : $this->redirectTo;
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Una cuenta desactivada no puede iniciar sesion.
     *
     * Se filtra en las credenciales (y no despues de entrar) para que el error
     * sea el mismo que con una contrasena equivocada: al que esta fuera no se le
     * cuenta si la cuenta existe, esta desactivada o la clave esta mal.
     *
     * @return array<string, mixed>
     */
    protected function credentials(Request $request): array
    {
        return array_merge(
            $request->only($this->username(), 'password'),
            ['activo' => 1]
        );
    }
}
