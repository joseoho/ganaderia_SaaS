@extends('layouts.layout)
@section('title', 'Detalle de Vacuna')

@section('content')
<h1 class="mb-4">Detalle de Vacuna</h1>

<div class="card shadow-sm">
    <div class="card-body">

        <h5 class="card-title">
            Animal: {{ $vacuna->animal->codigo_interno }}
        </h5>

        <ul class="list-group list-group-flush mb-3">
            <li class="list-group-item"><strong>Vacuna:</strong> {{ $vacuna->nombre }}</li>
            <li class="list-group-item"><strong>Lote:</strong> {{ $vacuna->lote ?? 'N/A' }}</li>
            <li class="list-group-item"><strong>Proveedor:</strong> {{ $vacuna->proveedor ?? 'N/A' }}</li>
            <li class="list-group-item"><strong>Vía:</strong> {{ $vacuna->via ?? 'N/A' }}</li>
            <li class="list-group-item"><strong>Dosis:</strong> {{ $vacuna->dosis ?? 'N/A' }} ml</li>
            <li class="list-group-item"><strong>Fecha:</strong> {{ $vacuna->fecha_aplicacion }}</li>
            <li class="list-group-item"><strong>Observaciones:</strong> {{ $vacuna->observaciones ?? 'Sin observaciones' }}</li>
        </ul>

        <a href="{{ route('registro_vacunas.index') }}" class="btn btn-secondary">Volver</a>

    </div>
</div>
@endsection