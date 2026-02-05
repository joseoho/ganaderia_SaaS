@extends('layouts.layout')
@section('title', 'Producción de Carne')

@section('content')
<h1 class="mb-4">Producción de Carne</h1>

<form method="GET" action="{{ route('produccion.index') }}" class="row g-3 mb-4">
    <div class="col-md-4">
        <input type="text" name="search" class="form-control"
               placeholder="Buscar por código del animal..."
               value="{{ request('search') }}">
    </div>

    <div class="col-md-2 d-grid">
        <button class="btn btn-primary">Buscar</button>
    </div>
</form>

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('produccion.create') }}" class="btn btn-success">Registrar Pesaje</a>
</div>

<div class="row">
    @forelse($producciones as $p)
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">

                    <h5 class="card-title">
                        Animal: {{ $p->animal->codigo_interno }}
                    </h5>

                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item"><strong>Peso:</strong> {{ $p->peso }} kg</li>
                        <li class="list-group-item"><strong>GDP:</strong> {{ $p->ganancia_diaria ? number_format($p->ganancia_diaria, 2) : 'N/A' }} kg/día</li>
                        <li class="list-group-item"><strong>Fecha:</strong> {{ $p->fecha }}</li>
                    </ul>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('produccion.show', $p->id) }}" class="btn btn-info btn-sm">Ver</a>
                        <a href="{{ route('produccion.edit', $p->id) }}" class="btn btn-warning btn-sm">Editar</a>

                        <form action="{{ route('produccion.destroy', $p->id) }}" method="POST">
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

{{ $producciones->links() }}

@endsection