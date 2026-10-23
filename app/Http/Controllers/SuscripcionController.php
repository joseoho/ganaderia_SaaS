<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * La suscripción de la propia ganadería: estado y subida del comprobante.
 *
 * El flujo es el de las apps de servicios públicos: el cliente paga por fuera
 * (Pago Móvil, transferencia, Zelle, USDT), sube la imagen y espera a que el
 * operador la apruebe. Mientras tanto, si ya venció, la finca está en solo
 * lectura.
 */
class SuscripcionController extends Controller
{
    public function index(): View
    {
        $inquilino = Auth::user()->inquilino;
        abort_if($inquilino === null, 404);

        $suscripcion = $inquilino->suscripcionActual();

        return view('suscripcion.index', [
            'inquilino' => $inquilino,
            'suscripcion' => $suscripcion,
            'planes' => Plan::query()->where('activo', true)->orderBy('orden')->get(),
            'pagos' => $inquilino->pagos()->with('plan')->latest()->take(10)->get(),
        ]);
    }

    public function storePago(Request $request): RedirectResponse
    {
        $inquilino = Auth::user()->inquilino;
        abort_if($inquilino === null, 404);

        $datos = $request->validate([
            'plan_id' => ['required', 'exists:planes,id'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'moneda' => ['required', Rule::in(['USD', 'VES'])],
            // Si paga en bolívares hace falta la tasa con la que pagó.
            'tasa' => ['nullable', 'numeric', 'min:0.0001', 'required_if:moneda,VES'],
            'metodo' => ['required', Rule::in(array_keys(Pago::METODOS))],
            'referencia' => ['required', 'string', 'max:60', 'unique:pagos,referencia'],
            'fecha_pago' => ['required', 'date', 'before_or_equal:today'],
            'comprobante' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'notas' => ['nullable', 'string', 'max:500'],
        ], [
            'referencia.unique' => 'Ese número de referencia ya está registrado. Si pagaste dos veces, usa la referencia del segundo pago.',
            'comprobante.mimes' => 'Sube una imagen (JPG o PNG) o un PDF.',
            'comprobante.max' => 'El comprobante no puede pasar de 5 MB.',
            'tasa.required_if' => 'Si pagaste en bolívares, indica la tasa que usaste.',
            'fecha_pago.before_or_equal' => 'La fecha del pago no puede ser futura.',
        ]);

        // El comprobante va al disco privado: nunca a `public/`, donde podría
        // acabar indexado por un buscador.
        $ruta = $request->file('comprobante')->store('comprobantes/'.$inquilino->id, 'local');

        Pago::create([
            'inquilino_id' => $inquilino->id,
            'suscripcion_id' => $inquilino->suscripcionActual()?->id,
            'plan_id' => $datos['plan_id'],
            'monto' => $datos['monto'],
            'moneda' => $datos['moneda'],
            'tasa' => $datos['tasa'] ?? null,
            'metodo' => $datos['metodo'],
            'referencia' => $datos['referencia'],
            'comprobante' => $ruta,
            'fecha_pago' => $datos['fecha_pago'],
            'estado' => Pago::ESTADO_PENDIENTE,
            'notas' => $datos['notas'] ?? null,
        ]);

        return redirect()
            ->route('suscripcion.index')
            ->with('success', 'Comprobante recibido. Te avisamos cuando esté revisado.');
    }
}
