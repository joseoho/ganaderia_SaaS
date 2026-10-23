@extends('layouts.layout')
@section('title', 'Suscripción')

@section('content')

<h1 class="h3 mb-4">Suscripción y pagos</h1>

@php($vigente = $suscripcion?->estaVigente() ?? false)

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title mb-3">Estado</h5>

                @if($vigente)
                    <p class="mb-1">
                        <span class="badge bg-success">Activa</span>
                        hasta el <strong>{{ $suscripcion->fin->format('d/m/Y') }}</strong>
                        ({{ $suscripcion->diasRestantes() }} días).
                    </p>
                    <p class="text-muted small mb-0">
                        Plan {{ $suscripcion->plan?->nombre }} · {{ $suscripcion->plan?->precioConMoneda() }} / mes
                    </p>
                @else
                    <p class="mb-1"><span class="badge bg-danger">Vencida</span></p>
                    <p class="text-muted mb-0">
                        La ganadería está en <strong>modo lectura</strong>: sigues viendo y exportando todo lo tuyo,
                        pero no puedes registrar nada nuevo hasta que se apruebe un pago.
                    </p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title mb-3">Cómo pagar</h5>
                <ol class="mb-0 ps-3">
                    <li>Haz el pago (Pago Móvil, transferencia, Zelle o USDT) al número que te dio el operador.</li>
                    <li>Sube aquí la imagen o el PDF del comprobante.</li>
                    <li>Cuando lo aprueben, la cuenta se activa sola.</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@can('usuarios.gestionar')
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">Subir comprobante de pago</div>
        <div class="card-body">
            <form method="POST" action="{{ route('suscripcion.pagos.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Plan <span class="text-danger">*</span></label>
                        <select name="plan_id" class="form-select" required>
                            @foreach($planes as $plan)
                                <option value="{{ $plan->id }}" @if(old('plan_id') == $plan->id) selected @endif>
                                    {{ $plan->nombre }} — {{ $plan->precioConMoneda() }} ({{ $plan->dias }} días)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold">Monto <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="monto" class="form-control"
                               value="{{ old('monto') }}" required>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold">Moneda</label>
                        <select name="moneda" class="form-select">
                            <option value="USD" @if(old('moneda') === 'USD') selected @endif>USD</option>
                            <option value="VES" @if(old('moneda') === 'VES') selected @endif>Bolívares</option>
                        </select>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="form-label">Tasa (si es en Bs)</label>
                        <input type="number" step="0.0001" min="0" name="tasa" class="form-control" value="{{ old('tasa') }}">
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold">Método</label>
                        <select name="metodo" class="form-select">
                            @foreach(\App\Models\Pago::METODOS as $valor => $etiqueta)
                                <option value="{{ $valor }}" @if(old('metodo') === $valor) selected @endif>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Referencia del pago <span class="text-danger">*</span></label>
                        <input type="text" name="referencia" class="form-control" maxlength="60"
                               value="{{ old('referencia') }}" required>
                        <small class="text-muted">El número que da el banco. No puede repetirse.</small>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Fecha del pago <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_pago" class="form-control"
                               value="{{ old('fecha_pago', now()->toDateString()) }}" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Comprobante <span class="text-danger">*</span></label>
                        <input type="file" name="comprobante" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                        <small class="text-muted">JPG, PNG o PDF, hasta 5 MB.</small>
                    </div>

                    <div class="col-12 mb-3">
                        <label class="form-label">Notas (opcional)</label>
                        <input type="text" name="notas" class="form-control" maxlength="500" value="{{ old('notas') }}">
                    </div>
                </div>

                <button class="btn btn-success">Enviar comprobante</button>
            </form>
        </div>
    </div>
@endcan

<div class="card shadow-sm">
    <div class="card-header">Últimos pagos</div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Fecha del pago</th>
                    <th>Plan</th>
                    <th class="text-end">Monto</th>
                    <th>Referencia</th>
                    <th>Estado</th>
                    <th>Motivo del rechazo</th>
                </tr>
            </thead>
            <tbody>
            @forelse($pagos as $pago)
                <tr>
                    <td>{{ $pago->fecha_pago->format('d/m/Y') }}</td>
                    <td>{{ $pago->plan?->nombre }}</td>
                    <td class="text-end">
                        {{ $pago->moneda === 'USD' ? 'US$' : 'Bs' }} {{ number_format((float) $pago->monto, 2) }}
                    </td>
                    <td class="small">{{ $pago->referencia }}</td>
                    <td>
                        @if($pago->estado === \App\Models\Pago::ESTADO_APROBADO)
                            <span class="badge bg-success">Aprobado</span>
                        @elseif($pago->estado === \App\Models\Pago::ESTADO_RECHAZADO)
                            <span class="badge bg-danger">Rechazado</span>
                        @else
                            <span class="badge bg-warning text-dark">Pendiente</span>
                        @endif
                    </td>
                    <td class="small text-danger">{{ $pago->motivo_rechazo }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">Todavía no hay pagos registrados.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
