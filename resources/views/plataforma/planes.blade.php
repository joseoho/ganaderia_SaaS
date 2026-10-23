<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Planes y precios · {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <span class="navbar-brand">Plataforma · Planes</span>
        <div class="d-flex gap-2">
            <a href="{{ route('plataforma.pagos') }}" class="btn btn-sm btn-outline-light">Pagos</a>
            <form method="POST" action="{{ url('/logout') }}">
                @csrf
                <button class="btn btn-sm btn-outline-light">Cerrar sesión</button>
            </form>
        </div>
    </div>
</nav>

<main class="container pb-5">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <p class="text-muted">
        Lo que cambies aquí se aplica a las <strong>próximas renovaciones</strong>: los periodos ya pagados
        conservan el precio con el que se vendieron.
    </p>

    <div class="row g-3">
        @foreach($planes as $plan)
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>{{ $plan->nombre }}</strong>
                        @if($plan->activo)
                            <span class="badge bg-success">Activo</span>
                        @else
                            <span class="badge bg-secondary">Inactivo</span>
                        @endif
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('plataforma.planes.update', $plan) }}">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <div class="col-7 mb-2">
                                    <label class="form-label small">Nombre</label>
                                    <input name="nombre" class="form-control form-control-sm" value="{{ $plan->nombre }}" required maxlength="60">
                                </div>
                                <div class="col-5 mb-2">
                                    <label class="form-label small">Precio (USD)</label>
                                    <input type="number" step="0.01" min="0" name="precio_usd" class="form-control form-control-sm"
                                           value="{{ $plan->precio_usd }}" required>
                                </div>
                                <div class="col-4 mb-2">
                                    <label class="form-label small">Días</label>
                                    <input type="number" min="1" max="365" name="dias" class="form-control form-control-sm"
                                           value="{{ $plan->dias }}" required>
                                </div>
                                <div class="col-4 mb-2">
                                    <label class="form-label small">Animales</label>
                                    <input type="number" min="1" name="limite_animales" class="form-control form-control-sm"
                                           value="{{ $plan->limite_animales }}" placeholder="sin límite">
                                </div>
                                <div class="col-4 mb-2">
                                    <label class="form-label small">Usuarios</label>
                                    <input type="number" min="1" name="limite_usuarios" class="form-control form-control-sm"
                                           value="{{ $plan->limite_usuarios }}" placeholder="sin límite">
                                </div>
                            </div>

                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="activo-{{ $plan->id }}"
                                       name="activo" value="1" @if($plan->activo) checked @endif>
                                <label class="form-check-label small" for="activo-{{ $plan->id }}">
                                    Disponible para vender
                                </label>
                            </div>

                            <button class="btn btn-primary btn-sm">Guardar cambios</button>
                        </form>
                    </div>
                    <div class="card-footer small text-muted">
                        {{ $plan->suscripciones()->count() }} contrataciones · deja los límites vacíos para "sin límite"
                    </div>
                </div>
            </div>
        @endforeach
    </div>

</main>
</body>
</html>
