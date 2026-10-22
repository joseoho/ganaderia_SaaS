<?php

namespace App\Http\Controllers;

use App\Enums\Permiso;
use App\Support\DatosDeEjemplo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Vaciar los datos de ejemplo de una ganadería demo.
 *
 * Una finca creada con `--demo` nace con potreros, animales y movimientos para
 * poder enseñar el producto. Cuando el dueño empieza a cargar los suyos, esos
 * datos estorban (y falsean los informes), así que se borran de un botón.
 *
 * Solo se puede hacer en una ganadería marcada como demo, y solo el
 * administrador: es un borrado masivo, aunque no toca usuarios ni la finca.
 */
class DemoController extends Controller
{
    public function vaciar(): RedirectResponse
    {
        $usuario = Auth::user();
        $inquilino = $usuario?->inquilino;

        abort_unless($inquilino !== null && $inquilino->esDemo(), 404);

        abort_unless($usuario->puede(Permiso::RegistrosEliminar), 403);

        DatosDeEjemplo::limpiar($inquilino);

        $inquilino->forceFill(['es_demo' => false])->save();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Datos de ejemplo eliminados. La ganadería queda lista para cargar los datos reales.');
    }
}
