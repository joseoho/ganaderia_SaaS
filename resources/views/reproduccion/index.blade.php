@extends('layouts.layout')
@section('title', 'Reproducción')

@section('content')
<h1 class="mb-4">Reproducción</h1>

<form method="GET" action="{{ route('reproduccion.index') }}" class="row g-3 mb-4">
    <div class="col-md-4">
        <input type="text" name="search" class="form-control"
               placeholder="Buscar por código del animal..."
               value="{{ request('search') }}">
    </div>
    

    <div class="col-md-3">
        <select name="tipo" class="form-select">
            <option value="">-- Tipo --</option>
            <option value="monta">Monta</option>
            <option value="inseminación">Inseminación</option>
            <option value="diagnóstico">Diagnóstico</option>
            <option value="parto">Parto</option>
            <option value="aborto">Aborto</option>
        </select>
    </div>

    <div class="col-md-2 d-grid">
        <button class="btn btn-primary">Filtrar</button>
    </div>
     <div class="col-md-2">
            <a href="{{ route('reproduccion.index') }}" class="btn btn-secondary">
            <i class="fas fa-broom"></i> Limpiar</a>
        </div>
</form>

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('reproduccion.create') }}" class="btn btn-success">Registrar Evento</a>
</div>

<div class="row">
    @forelse($reproducciones as $r)
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">

                <div class="card-body">

                    <h5 class="card-title">
                        Animal: {{ $r->animal->codigo_interno }}
                    </h5>

                    <p class="text-muted">{{ ucfirst($r->tipo) }}</p>

                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item"><strong>Fecha:</strong> {{ $r->fecha }}</li>
                        <li class="list-group-item"><strong>Toro:</strong> {{ $r->toro ?? 'N/A' }}</li>
                        <li class="list-group-item"><strong>Resultado:</strong> {{ $r->resultado ?? 'N/A' }}</li>
                    </ul>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('reproduccion.show', $r->id) }}" class="btn btn-info btn-sm">Ver</a>
                        <a href="{{ route('reproduccion.edit', $r->id) }}" class="btn btn-warning btn-sm">Editar</a>

                        <form action="{{ route('reproduccion.destroy', $r->id) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </div>

                </div>

            </div>
        </div>
    @empty
        <div class="col-12 text-center">
            <div class="alert alert-secondary">No hay registros reproductivos</div>
        </div>
    @endforelse
</div>

{{ $reproducciones->links() }}

@endsection