@extends('layouts.layout')
@section('title', 'Mis Compras')

@section('content')
<h1 class="mb-4">Mis Compras</h1>

<!-- 🔍 Barra de búsqueda y filtros -->
<form method="GET" action="{{ route('compras.index') }}" class="row g-3 mb-4">
    <div class="col-md-4">
        <input type="text" name="search" class="form-control" placeholder="Buscar por proveedor o descripción..."
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
        <a href="{{ route('compras.index') }}" class="btn btn-secondary">
        <i class="fas fa-broom"></i> Limpiar</a> 
    </div> 
</form>

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('compras.create') }}" class="btn btn-success">Registrar Compra</a>
</div>

<!-- 🧾 Cards de compras -->
<div class="row">
    @forelse($compras as $compra)
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">{{ $compra->proveedor }}</h5>
                    <p class="card-text text-muted">{{ $compra->descripcion }}</p>

                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item"><strong>Fecha:</strong> {{ $compra->fecha }}</li>
                        <li class="list-group-item"><strong>Monto:</strong> ${{ number_format($compra->monto_total, 2) }}</li>
                    </ul>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('compras.show', $compra->id) }}" class="btn btn-info btn-sm">Ver</a>
                        <a href="{{ route('compras.edit', $compra->id) }}" class="btn btn-warning btn-sm">Editar</a>

                        <form action="{{ route('compras.destroy', $compra->id) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12 text-center">
            <div class="alert alert-secondary">No se encontraron compras</div>
        </div>
    @endforelse
</div>

<!-- 📄 Paginación -->
<div class="mt-4">
    {{ $compras->links() }}
</div>

@endsection