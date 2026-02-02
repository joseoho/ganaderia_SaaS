@extends('layouts.layout')
@section('title', 'Genealogía')

@section('content')
<h1 class="mb-4">Genealogía</h1>

<form method="GET" action="{{ route('genealogia.index') }}" class="row g-3 mb-4">
    <div class="col-md-4">
        <input type="text" name="search" class="form-control"
               placeholder="Buscar por código del animal..."
               value="{{ request('search') }}">
    </div>
    <div class="col-md-2 d-grid">
        <button class="btn btn-primary">Filtrar</button>
    </div>
   <div class="col-md-2">
        <a href="{{ route('genealogia.index') }}" class="btn btn-secondary">
        <i class="fas fa-broom"></i> Limpiar</a> 
    </div>  
</form>

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('genealogia.create') }}" class="btn btn-success">Registrar Genealogía</a>
</div>

<div class="row">
    @forelse($genealogias as $g)
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        Animal: {{ $g->animal->codigo_interno }}
                    </h5>

                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item">
                            <strong>Padre:</strong>
                            {{ $g->padre?->codigo_interno ?? 'No registrado' }}
                        </li>
                        <li class="list-group-item">
                            <strong>Madre:</strong>
                            {{ $g->madre?->codigo_interno ?? 'No registrado' }}
                        </li>
                    </ul>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('genealogia.show', $g->id) }}" class="btn btn-info btn-sm">Ver</a>
                        <a href="{{ route('genealogia.edit', $g->id) }}" class="btn btn-warning btn-sm">Editar</a>

                        <form action="{{ route('genealogia.destroy', $g->id) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12 text-center">
            <div class="alert alert-secondary">No hay registros de genealogía</div>
        </div>
    @endforelse
</div>

{{ $genealogias->links() }}

@endsection