<?php

namespace App\Http\Controllers;

use App\Enums\Permiso;
use App\Models\Pago;
use App\Models\Suscripcion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Panel del operador de la plataforma: revisar comprobantes y dar luz verde.
 *
 * Es la otra cara del cobro: aquí se ve lo que suben las fincas, se abre el
 * comprobante y se aprueba (extiende la suscripción) o se rechaza con un motivo
 * que el cliente leerá en su pantalla.
 *
 * Todo queda con quién lo revisó y cuándo: si mañana hay una discusión por un
 * pago, está escrito.
 */
class PlataformaController extends Controller
{
    public function pagos(): View
    {
        return view('plataforma.pagos', [
            'pendientes' => Pago::query()->with(['inquilino', 'plan'])
                ->where('estado', Pago::ESTADO_PENDIENTE)
                ->orderBy('created_at')
                ->get(),
            'recientes' => Pago::query()->with(['inquilino', 'plan', 'revisor'])
                ->where('estado', '!=', Pago::ESTADO_PENDIENTE)
                ->latest('revisado_en')
                ->take(15)
                ->get(),
            'fincasSinVigencia' => \App\Models\Inquilino::query()
                ->whereDoesntHave('suscripciones', fn ($q) => $q->whereDate('fin', '>=', now()->toDateString()))
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    /** El comprobante se sirve autenticado: no hay URL pública para él. */
    public function comprobante(Pago $pago): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($pago->comprobante), 404);

        return Storage::disk('local')->response($pago->comprobante);
    }

    public function aprobar(Pago $pago): RedirectResponse
    {
        abort_unless(Auth::user()->puede(Permiso::PlataformaGestionarPagos), 403);

        if (! $pago->estaPendiente()) {
            return back()->with('warning', 'Ese pago ya estaba revisado.');
        }

        DB::transaction(function () use ($pago): void {
            $plan = $pago->plan;
            $actual = $pago->inquilino->suscripcionActual();

            // Se suma al final de lo que ya tenía pagado; si venció, se cuenta
            // desde hoy (no se regala el tiempo en que no hubo servicio).
            $desde = ($actual !== null && $actual->fin->isFuture()) ? $actual->fin->copy() : now();

            $nueva = Suscripcion::create([
                'inquilino_id' => $pago->inquilino_id,
                'plan_id' => $plan->id,
                'inicio' => $desde->toDateString(),
                'fin' => $desde->copy()->addDays($plan->dias)->toDateString(),
                'es_prueba' => false,
            ]);

            $pago->forceFill([
                'estado' => Pago::ESTADO_APROBADO,
                'suscripcion_id' => $nueva->id,
                'revisado_por' => Auth::id(),
                'revisado_en' => now(),
                'motivo_rechazo' => null,
            ])->save();
        });

        return back()->with('success', 'Pago aprobado: la ganadería quedó activa hasta el '.$pago->fresh()->inquilino->suscripcionActual()?->fin->format('d/m/Y').'.');
    }

    public function rechazar(Request $request, Pago $pago): RedirectResponse
    {
        abort_unless(Auth::user()->puede(Permiso::PlataformaGestionarPagos), 403);

        $datos = $request->validate([
            'motivo_rechazo' => ['required', 'string', 'min:5', 'max:255'],
        ], [
            'motivo_rechazo.required' => 'Escribe el motivo: el cliente lo va a leer.',
            'motivo_rechazo.min' => 'El motivo es demasiado corto para que sirva de algo.',
        ]);

        if (! $pago->estaPendiente()) {
            return back()->with('warning', 'Ese pago ya estaba revisado.');
        }

        $pago->forceFill([
            'estado' => Pago::ESTADO_RECHAZADO,
            'motivo_rechazo' => $datos['motivo_rechazo'],
            'revisado_por' => Auth::id(),
            'revisado_en' => now(),
        ])->save();

        return back()->with('success', 'Pago rechazado. El cliente verá el motivo en su pantalla.');
    }
}
