<?php

namespace App\Http\Controllers;

use App\Enums\Permiso;
use App\Models\Pago;
use App\Models\Plan;
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

    /** Planes y precios: lo que se cobra y cuánto cabe en cada uno. */
    public function planes(): View
    {
        return view('plataforma.planes', [
            'planes' => Plan::query()->orderBy('orden')->get(),
        ]);
    }

    public function actualizarPlan(Request $request, Plan $plan): RedirectResponse
    {
        abort_unless(Auth::user()->puede(Permiso::PlataformaEditarPlanes), 403);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:60'],
            'precio_usd' => ['required', 'numeric', 'min:0', 'max:100000'],
            'dias' => ['required', 'integer', 'min:1', 'max:365'],
            'limite_animales' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'limite_usuarios' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'activo' => ['nullable', 'boolean'],
        ], [
            'precio_usd.min' => 'El precio no puede ser negativo.',
            'dias.max' => 'Un periodo de más de 365 días no tiene sentido.',
            'limite_animales.min' => 'El límite de animales debe ser al menos 1 (o déjalo vacío para "sin límite").',
        ]);

        $plan->update([
            'nombre' => $datos['nombre'],
            'precio_usd' => $datos['precio_usd'],
            'dias' => $datos['dias'],
            'limite_animales' => $datos['limite_animales'] ?? null,
            'limite_usuarios' => $datos['limite_usuarios'] ?? null,
            'activo' => $request->boolean('activo'),
        ]);

        return back()->with('success', 'Plan actualizado: '.$plan->nombre.'. Los cambios valen para las próximas renovaciones.');
    }
}
