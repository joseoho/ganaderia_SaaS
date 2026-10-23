<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pagos de las fincas · {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

{{--
    Panel del operador (el que cobra). Va con su propio layout a propósito: el
    operador no pertenece a ninguna ganadería, así que no puede usar el layout
    del panel, que muestra el nombre de la finca.
--}}
<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <span class="navbar-brand">Plataforma · Pagos</span>
        <form method="POST" action="{{ url('/logout') }}">
            @csrf
            <button class="btn btn-sm btn-outline-light">Cerrar sesión</button>
        </form>
    </div>
</nav>

<main class="container pb-5">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <h1 class="h4 mb-3">Comprobantes por revisar ({{ $pendientes->count() }})</h1>

    @forelse($pendientes as $pago)
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-7">
                        <h5 class="mb-1">{{ $pago->inquilino?->nombre }}</h5>
                        <p class="mb-1 small text-muted">
                            Plan {{ $pago->plan?->nombre }} · pago del {{ $pago->fecha_pago->format('d/m/Y') }}
                            · recibido {{ $pago->created_at->format('d/m/Y H:i') }}
                        </p>
                        <p class="mb-1">
                            <strong>{{ $pago->moneda === 'USD' ? 'US$' : 'Bs' }} {{ number_format((float) $pago->monto, 2) }}</strong>
                            @if($pago->moneda !== 'USD' && $pago->tasa)
                                <span class="text-muted small">(tasa {{ number_format((float) $pago->tasa, 2) }} → US$ {{ number_format($pago->montoEnUsd(), 2) }})</span>
                            @endif
                            · {{ \App\Models\Pago::METODOS[$pago->metodo] ?? $pago->metodo }}
                            · ref. <code>{{ $pago->referencia }}</code>
                        </p>
                        @if($pago->notas)
                            <p class="mb-1 small">Nota del cliente: {{ $pago->notas }}</p>
                        @endif
                        <a href="{{ route('plataforma.comprobante', $pago) }}" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                            Ver comprobante
                        </a>
                    </div>

                    <div class="col-md-5">
                        <form method="POST" action="{{ route('plataforma.aprobar', $pago) }}" class="mb-2">
                            @csrf
                            <button class="btn btn-success w-100"
                                    onclick="return confirm('¿Aprobar el pago de {{ $pago->inquilino?->nombre }} y activar la cuenta?')">
                                Aprobar y activar
                            </button>
                        </form>

                        <form method="POST" action="{{ route('plataforma.rechazar', $pago) }}">
                            @csrf
                            <input type="text" name="motivo_rechazo" class="form-control form-control-sm mb-1"
                                   placeholder="Motivo del rechazo (lo verá el cliente)" required minlength="5" maxlength="255">
                            <button class="btn btn-outline-danger btn-sm w-100">Rechazar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-info">No hay comprobantes pendientes.</div>
    @endforelse

    <h2 class="h5 mt-5 mb-3">Fincas sin suscripción vigente</h2>
    @if($fincasSinVigencia->isEmpty())
        <p class="text-muted">Todas las fincas están al día.</p>
    @else
        <ul class="list-group mb-4">
            @foreach($fincasSinVigencia as $finca)
                <li class="list-group-item d-flex justify-content-between">
                    <span>{{ $finca->nombre }}</span>
                    <span class="text-muted small">sin periodo activo</span>
                </li>
            @endforeach
        </ul>
    @endif

    <h2 class="h5 mt-5 mb-3">Revisados hace poco</h2>
    <div class="table-responsive">
        <table class="table table-sm bg-white">
            <thead class="table-light">
                <tr><th>Finca</th><th>Estado</th><th class="text-end">Monto</th><th>Revisado</th><th>Motivo</th></tr>
            </thead>
            <tbody>
            @forelse($recientes as $pago)
                <tr>
                    <td>{{ $pago->inquilino?->nombre }}</td>
                    <td>
                        <span class="badge {{ $pago->estado === \App\Models\Pago::ESTADO_APROBADO ? 'bg-success' : 'bg-danger' }}">
                            {{ \App\Models\Pago::ESTADOS[$pago->estado] ?? $pago->estado }}
                        </span>
                    </td>
                    <td class="text-end">{{ $pago->moneda === 'USD' ? 'US$' : 'Bs' }} {{ number_format((float) $pago->monto, 2) }}</td>
                    <td class="small">{{ $pago->revisado_en?->format('d/m/Y H:i') }} · {{ $pago->revisor?->name }}</td>
                    <td class="small text-danger">{{ $pago->motivo_rechazo }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-3">Nada revisado todavía.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

</main>
</body>
</html>
