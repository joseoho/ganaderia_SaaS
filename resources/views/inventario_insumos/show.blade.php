@extends('layouts.layout')
@section('title', 'Detalle del Insumo')

@section('content')
<h1 class="mb-4">Detalle del Insumo</h1>

<div class="card shadow-sm">
    <div class="card-body">

        <h4>{{ $inventario->nombre }}</h4>
        <p class="text-muted">{{ $inventario->categoria ?? 'Sin categoría' }}</p>

        <ul class="list-group list-group-flush mb-3">
            <li class="list-group-item"><strong>Cantidad:</strong> {{ $inventario->cantidad }} {{ $inventario->unidad }}</li>
            <li class="list-group-item"><strong>Fecha ingreso:</strong> {{ $inventario->fecha_ingreso ?? 'No registrada' }}</li>
            <li class="list-group-item"><strong>Descripción:</strong> {{ $inventario->descripcion ?? 'Sin descripción' }}</li>
        </ul>

        <a href="{{ route('inventario.index') }}" class="btn btn-secondary">Volver</a>

    </div>
</div>
@endsection