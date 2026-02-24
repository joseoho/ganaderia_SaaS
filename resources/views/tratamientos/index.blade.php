@extends('layouts.layout')
@section('title', 'Tratamientos')

@section('content')
<h1 class="mb-4">Tratamientos</h1>

<form method="GET" action="{{ route('tratamientos.index') }}" class="row g-3 mb-4">
    <div class="col-md-4">
        <input type="text" name="search" class="form-control"
               placeholder="Buscar por código del animal..."
               value="{{ request('search') }}">
    </div>

    <div class="col-md-4">
        <input type="text" name="motivo" class="form-control"
               placeholder="Buscar por motivo..."
               value="{{ request('motivo') }}">
    </div>

    <div class="col-md-2 d-grid">
        <button class="btn btn-primary">Filtrar</button>
    </div>
    <div class="col-md-2 d-grid">
        <a href="{{ route('tratamientos.index') }}" class="btn btn-secondary">Limpiar</a>
    </div>
</form>

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('tratamientos.create') }}" class="btn btn-success">Registrar Tratamiento</a>
</div>

<div class="row">
    @forelse($tratamientos as $t)
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">

                <div class="card-body">

                    <h5 class="card-title">
                        Animal: {{ $t->animal->codigo_interno }}
                    </h5>

                    <p class="text-muted">{{ $t->motivo }}</p>

                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item"><strong>Medicamento:</strong> {{ $t->medicamento }}</li>
                        <li class="list-group-item"><strong>Inicio:</strong> {{ $t->fecha}}</li>
                        <li class="list-group-item"><strong>Fin:</strong> {{ $t->fecha_fin ?? 'En curso' }}</li>
                    </ul>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('tratamientos.show', $t->id) }}" class="btn btn-info btn-sm">Ver</a>
                        <a href="{{ route('tratamientos.edit', $t->id) }}" class="btn btn-warning btn-sm">Editar</a>

                        <form action="{{ route('tratamientos.destroy', $t->id) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </div>

                </div>

            </div>
        </div>
    @empty
        <div class="col-12 text-center">
            <div class="alert alert-secondary">No hay tratamientos registrados</div>
        </div>
    @endforelse
</div>

{{ $tratamientos->links() }}

@endsection