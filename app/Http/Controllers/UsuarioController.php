<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Models\User;
use App\Support\LimitesDePlan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Gestion de la gente de la ganaderia: alta, cambio de rol y desactivacion.
 *
 * Tres reglas que existen para que nadie se quede fuera de su propia ganaderia:
 *
 *  1. Nadie se cambia el rol a si mismo.
 *  2. La ganaderia no puede quedarse sin administrador (ni al degradar ni al
 *     desactivar al ultimo).
 *  3. Nadie desactiva su propia cuenta.
 *
 * Y una de aislamiento: todo se busca DENTRO de la ganaderia del que actua, asi
 * que un administrador de A no puede tocar las cuentas de B ni sabiendo el id.
 */
class UsuarioController extends Controller
{
    /**
     * @return Builder<User>
     */
    private function deMiGanaderia(): Builder
    {
        return User::query()->where('inquilino_id', Auth::user()->inquilino_id);
    }

    /**
     * Un usuario de otra ganaderia responde 404, no 403: al que pregunta no se
     * le confirma que esa cuenta exista.
     */
    private function asegurarQueEsDeMiGanaderia(User $usuario): void
    {
        abort_unless(
            (int) $usuario->inquilino_id === (int) Auth::user()->inquilino_id,
            404
        );
    }

    /**
     * ¿Es el ultimo administrador que le queda a la ganaderia?
     *
     * Si solo queda uno, no se le puede quitar el rol ni desactivar su cuenta,
     * porque nadie podria volver a entrar a gestionar nada.
     */
    private function esElUltimoAdministrador(User $usuario, bool $soloActivos = false): bool
    {
        $consulta = User::query()
            ->where('inquilino_id', $usuario->inquilino_id)
            ->where('rol', Rol::Administrador->value)
            ->whereKeyNot($usuario->getKey());

        if ($soloActivos) {
            $consulta->where('activo', true);
        }

        return ! $consulta->exists();
    }

    public function index(): View
    {
        $usuarios = $this->deMiGanaderia()->orderBy('name')->paginate(15);

        return view('usuarios.index', compact('usuarios'));
    }

    public function create(): View
    {
        return view('usuarios.create', [
            'usuario' => new User(['rol' => Rol::Usuario->value]),
        ]);
    }

    public function store(StoreUsuarioRequest $request): RedirectResponse
    {
        // Tope del plan: no se pueden dar de alta mas cuentas de las contratadas.
        if (! LimitesDePlan::puedeCrearUsuarios(Auth::user()->inquilino)) {
            return back()->withInput()->with('warning', LimitesDePlan::mensajeUsuarios(Auth::user()->inquilino));
        }

        $datos = $request->validated();

        $usuario = new User([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => $datos['password'],
            'rol' => $datos['rol'],
            'inquilino_id' => Auth::user()->inquilino_id,
            'activo' => true,
        ]);

        // El administrador que da de alta responde por esa persona: si lo marca,
        // la cuenta entra sin esperar a que abra el correo.
        if ($request->boolean('verificado')) {
            $usuario->email_verified_at = now();
        }

        $usuario->save();

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario creado: '.$usuario->email.'. Comparte la contraseña con él.');
    }

    public function edit(User $usuario): View
    {
        $this->asegurarQueEsDeMiGanaderia($usuario);

        return view('usuarios.edit', compact('usuario'));
    }

    public function update(UpdateUsuarioRequest $request, User $usuario): RedirectResponse
    {
        $this->asegurarQueEsDeMiGanaderia($usuario);

        $datos = $request->validated();
        $nuevoRol = Rol::desde($datos['rol']);
        $esUnoMismo = (int) $usuario->getKey() === (int) Auth::id();

        if ($esUnoMismo && $nuevoRol !== $usuario->rolActual()) {
            return back()->withInput()->withErrors([
                'rol' => 'No puedes cambiarte el rol a ti mismo: pídeselo a otro administrador.',
            ]);
        }

        if ($usuario->rolActual() === Rol::Administrador
            && $nuevoRol !== Rol::Administrador
            && $this->esElUltimoAdministrador($usuario)) {
            return back()->withInput()->withErrors([
                'rol' => 'Esta es la única cuenta de administrador de la ganadería: no puede dejar de serlo.',
            ]);
        }

        $usuario->name = $datos['name'];
        $usuario->email = $datos['email'];
        $usuario->rol = $nuevoRol->value;

        // Cambiar el correo invalida la verificación: el correo nuevo no es el
        // que se confirmó. Si no cambia, se respeta lo que ya había.
        if ($usuario->isDirty('email')) {
            $usuario->email_verified_at = null;
        }

        if (! empty($datos['password'])) {
            $usuario->password = $datos['password'];
        }

        $usuario->save();

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario actualizado: '.$usuario->email.'.');
    }

    /**
     * Activa o desactiva una cuenta.
     *
     * Se desactiva en vez de borrar: el histórico de esa persona (ventas,
     * pesajes, vacunas) tiene que seguir teniendo nombre.
     */
    public function cambiarEstado(Request $request, User $usuario): RedirectResponse
    {
        $this->asegurarQueEsDeMiGanaderia($usuario);

        if ((int) $usuario->getKey() === (int) Auth::id()) {
            return back()->with('warning', 'No puedes desactivar tu propia cuenta.');
        }

        if ($usuario->estaActivo()
            && $usuario->rolActual() === Rol::Administrador
            && $this->esElUltimoAdministrador($usuario, soloActivos: true)) {
            return back()->with('warning', 'No puedes desactivar al único administrador activo de la ganadería.');
        }

        $usuario->activo = ! $usuario->estaActivo();
        $usuario->save();

        return back()->with(
            'success',
            $usuario->estaActivo()
                ? 'Cuenta activada: '.$usuario->email.'.'
                : 'Cuenta desactivada: '.$usuario->email.'. Ya no puede entrar.'
        );
    }
}
