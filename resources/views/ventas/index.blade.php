@extends('layouts.layout')
@section('title', 'Mis Ventas')

@section('content')
<h1 class="mb-4">Mis Ventas</h1>

<!-- 🔍 Barra de búsqueda y filtros -->
<form method="GET" action="{{ route('ventas.index') }}" class="row g-3 mb-4">
    <div class="col-md-4">
        <input type="text" name="search" class="form-control" placeholder="Buscar por cliente o descripción..."
               value="{{ request('search') }}">
    </div>

    <div class="col-md-3">
        <input type="date" name="fecha_desde" class="form-control" value="{{ request('fecha_desde') }}">
    </div>

    <div class="col-md-3">
        <input type="date" name="fecha_hasta" class="form-control" value="{{ request('fecha_hasta') }}">
    </div>

    <div class="col-md-2 d-grid">
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </div>
     <div class="col-md-2">
        <a href="{{ route('ventas.index') }}" class="btn btn-secondary">
        <i class="fas fa-broom"></i> Limpiar</a> 
    </div> 
</form>

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('ventas.create') }}" class="btn btn-success">Registrar Venta</a>
</div>

<!-- 🧾 Cards de ventas -->
<div class="row">
    @forelse($ventas as $venta)
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">{{ $venta->cliente }}</h5>
                    <p class="card-text text-muted">{{ $venta->descripcion }}</p>

                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item"><strong>Fecha:</strong> {{ $venta->fecha }}</li>
                        <li class="list-group-item"><strong>Monto:</strong> ${{ number_format($venta->monto_total, 2) }}</li>
                    </ul>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('ventas.show', $venta->id) }}" class="btn btn-info btn-sm">Ver</a>
                        <a href="{{ route('ventas.edit', $venta->id) }}" class="btn btn-warning btn-sm">Editar</a>

                        <form action="{{ route('ventas.destroy', $venta->id) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12 text-center">
            <div class="alert alert-secondary">No se encontraron ventas</div>
        </div>
    @endforelse
</div>

<!-- 📄 Paginación -->
<div class="mt-4">
    {{ $ventas->links() }}
</div>

@endsection