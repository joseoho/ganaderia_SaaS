@extends('layouts.layout')
@section('title', 'Movilizaciones')

@section('content')
<h1 class="mb-4">Movilizaciones</h1>

<form method="GET" action="{{ route('movilizaciones.index') }}" class="row g-3 mb-4">
    <div class="col-md-4">
        <input type="text" name="search" class="form-control"
               placeholder="Buscar por código del animal..."
               value="{{ request('search') }}">
    </div>

    <div class="col-md-3">
        <select name="tipo" class="form-select">
            <option value="">-- Tipo --</option>
            <option value="entrada">Entrada</option>
            <option value="salida">Salida</option>
            <option value="interna">Interna</option>
        </select>
    </div>

    <div class="col-md-2 d-grid">
        <button class="btn btn-primary">Filtrar</button>
    </div>
    <div class="col-md-2">
        <a href="{{ route('movilizaciones.index') }}" class="btn btn-secondary">
        <i class="fas fa-broom"></i> Limpiar</a> 
    </div> 
</form>

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('movilizaciones.create') }}" class="btn btn-success">Registrar Movilización</a>
</div>

<div class="row">
    @forelse($movilizaciones as $m)
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">

                    <h5 class="card-title">
                        Animal: {{ $m->animal->codigo_interno }}
                    </h5>

                    <p class="text-muted">{{ ucfirst($m->tipo) }}</p>

                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item"><strong>Origen:</strong> {{ $m->origen ?? 'N/A' }}</li>
                        <li class="list-group-item"><strong>Destino:</strong> {{ $m->destino ?? 'N/A' }}</li>
                        <li class="list-group-item"><strong>Fecha:</strong> {{ $m->fecha }}</li>
                    </ul>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('movilizaciones.show', $m->id) }}" class="btn btn-info btn-sm">Ver</a>
                        <a href="{{ route('movilizaciones.edit', $m->id) }}" class="btn btn-warning btn-sm">Editar</a>

                        <form action="{{ route('movilizaciones.destroy', $m->id) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    @empty
        <div class="col-12 text-center">
            <div class="alert alert-secondary">No hay movilizaciones registradas</div>
        </div>
    @endforelse
</div>

{{ $movilizaciones->links() }}

@endsection