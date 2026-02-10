@extends('layouts.layout')
@section('title', 'Producción de Leche')

@section('content')
<h1 class="mb-4">Producción de Leche</h1>

<!-- 🔍 Filtros -->
<form method="GET" action="{{ route('produccion_leche.index') }}" class="row g-3 mb-4">
    <div class="col-md-4">
        <input type="text" name="search" class="form-control"
               placeholder="Buscar por código del animal..."
               value="{{ request('search') }}">
    </div>

    <div class="col-md-3">
        <select name="turno" class="form-select">
            <option value="">-- Turno --</option>
            <option value="mañana" {{ request('turno')=='mañana'?'selected':'' }}>Mañana</option>
            <option value="tarde" {{ request('turno')=='tarde'?'selected':'' }}>Tarde</option>
            <option value="noche" {{ request('turno')=='noche'?'selected':'' }}>Noche</option>
        </select>
    </div>

    <div class="col-md-2 d-grid">
        <button class="btn btn-primary">Filtrar</button>
    </div>
      <div class="col-md-2">
        <a href="{{ route('produccion_leche.index') }}" class="btn btn-secondary">
        <i class="fas fa-broom"></i> Limpiar</a> 
    </div> 
</form>

<!-- ➕ Botón registrar -->
<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('produccion_leche.create') }}" class="btn btn-success">Registrar Ordeño</a>
</div>

<!-- 🧾 Cards de producción -->
<div class="row">
    @forelse($producciones as $p)
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">

                <div class="card-body">

                    <h5 class="card-title">
                        Animal: {{ $p->animal->codigo_interno }}
                    </h5>

                    <p class="text-muted">Turno: {{ ucfirst($p->turno) }}</p>

                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item">
                            <strong>Litros:</strong> {{ $p->litros }}
                        </li>

                        <li class="list-group-item">
                            <strong>Variación:</strong>
                            @if($p->variacion > 0)
                                <span class="text-success">+{{ $p->variacion }}</span>
                            @elseif($p->variacion < 0)
                                <span class="text-danger">{{ $p->variacion }}</span>
                            @else
                                {{ $p->variacion ?? 'N/A' }}
                            @endif
                        </li>

                        <li class="list-group-item">
                            <strong>Fecha:</strong> {{ $p->fecha }}
                        </li>
                    </ul>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('produccion_leche.show', $p->id) }}" class="btn btn-info btn-sm">Ver</a>
                        <a href="{{ route('produccion_leche.edit', $p->id) }}" class="btn btn-warning btn-sm">Editar</a>

                        <form action="{{ route('produccion_leche.destroy', $p->id) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </div>

                </div>

            </div>
        </div>
    @empty
        <div class="col-12 text-center">
            <div class="alert alert-secondary">No hay registros de producción</div>
        </div>
    @endforelse
</div>

<!-- 📄 Paginación -->
<div class="mt-4">
    {{ $producciones->links() }}
</div>

@endsection