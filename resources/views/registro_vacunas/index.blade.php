@extends('layouts.layout')
@section('title', 'Registro de Vacunas')

@section('content')
<h1 class="mb-4">Registro de Vacunas</h1>

<form method="GET" action="{{ route('registro_vacunas.index') }}" class="row g-3 mb-4">
    <div class="col-md-4">
        <input type="text" name="search" class="form-control"
               placeholder="Buscar por código del animal..."
               value="{{ request('search') }}">
    </div>

    <div class="col-md-4">
        <input type="text" name="nombre" class="form-control"
               placeholder="Buscar por nombre de vacuna..."
               value="{{ request('nombre') }}">
    </div>

    <div class="col-md-2 d-grid">
        <button class="btn btn-primary">Filtrar</button>
    </div>
        <div class="col-md-2">
            <a href="{{ route('registro_vacunas.index') }}" class="btn btn-secondary">
            <i class="fas fa-broom"></i> Limpiar</a>
        </div>
</form>

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('registro_vacunas.create') }}" class="btn btn-success">Registrar Vacuna</a>
</div>

<div class="row">
    @forelse($vacunas as $v)
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">

                <div class="card-body">

                    <h5 class="card-title">
                        Animal: {{ $v->animal->codigo_interno }}
                    </h5>

                    <p class="text-muted">{{ $v->nombre }}</p>

                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item"><strong>Lote:</strong> {{ $v->lote ?? 'N/A' }}</li>
                        <li class="list-group-item"><strong>Fecha:</strong> {{ $v->fecha_aplicacion }}</li>
                        <li class="list-group-item"><strong>Vía:</strong> {{ $v->via ?? 'N/A' }}</li>
                    </ul>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('registro_vacunas.show', $v->id) }}" class="btn btn-info btn-sm">Ver</a>
                        <a href="{{ route('registro_vacunas.edit', $v->id) }}" class="btn btn-warning btn-sm">Editar</a>

                        <form action="{{ route('registro_vacunas.destroy', $v->id) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </div>

                </div>

            </div>
        </div>
    @empty
        <div class="col-12 text-center">
            <div class="alert alert-secondary">No hay vacunas registradas</div>
        </div>
    @endforelse
</div>

{{ $vacunas->links() }}

@endsection