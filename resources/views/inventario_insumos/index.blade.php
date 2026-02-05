@extends('layouts.layout')
@section('title', 'Inventario de Insumos')

@section('content')
<h1 class="mb-4">Inventario de Insumos</h1>

<form method="GET" action="{{ route('inventario.index') }}" class="row g-3 mb-4">
    <div class="col-md-4">
        <input type="text" name="search" class="form-control"
               placeholder="Buscar por nombre o categoría..."
               value="{{ request('search') }}">
    </div>

    <div class="col-md-3">
        <select name="categoria" class="form-select">
            <option value="">-- Categoría --</option>
            <option value="alimento">Alimento</option>
            <option value="medicina">Medicina</option>
            <option value="herramienta">Herramienta</option>
            <option value="otros">Otros</option>
        </select>
    </div>

    <div class="col-md-2 d-grid">
        <button class="btn btn-primary">Filtrar</button>
    </div>
        <div class="col-md-2">
        <a href="{{ route('inventario.index') }}" class="btn btn-secondary">
        <i class="fas fa-broom"></i> Limpiar</a> 
    </div>   
</form>

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('inventario.create') }}" class="btn btn-success">Registrar Insumo</a>
</div>

<div class="row">
    @forelse($insumos as $i)
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    @if($i->estaBajo())
                        <span class="badge bg-danger mb-2">Stock bajo</span>
                    @endif
                    <h5 class="card-title">{{ $i->nombre }}</h5>
                    <p class="text-muted">{{ $i->categoria ?? 'Sin categoría' }}</p>

                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item">
                            <strong>Cantidad:</strong> 
                            {{ $i->cantidad }} {{ $i->unidad }}

                            @if($i->estaBajo())
                                <span class="badge bg-danger float-end">Bajo</span>
                            @endif
                        </li>
                        <li class="list-group-item">
                            <strong>Cantidad:</strong> {{ $i->cantidad }} {{ $i->unidad }}
                        </li>
                        <li class="list-group-item">
                            <strong>Ingreso:</strong> {{ $i->fecha_ingreso ?? 'No registrado' }}
                        </li>
                    </ul>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('inventario.show', $i->id) }}" class="btn btn-info btn-sm">Ver</a>
                        <a href="{{ route('inventario.edit', $i->id) }}" class="btn btn-warning btn-sm">Editar</a>

                        <form action="{{ route('inventario.destroy', $i->id) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    @empty
        <div class="col-12 text-center">
            <div class="alert alert-secondary">No hay insumos registrados</div>
        </div>
    @endforelse
</div>

{{ $insumos->links() }}

@endsection