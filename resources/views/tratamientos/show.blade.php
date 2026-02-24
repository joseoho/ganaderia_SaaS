@extends('layouts.layout')
@section('title', 'Detalle de Tratamiento')

@section('content')
<h1 class="mb-4">Detalle de Tratamiento</h1>

<div class="card shadow-sm">
    <div class="card-body">

        <h5 class="card-title">
            Animal: {{ $tratamiento->animal->codigo_interno }}
        </h5>

        <ul class="list-group list-group-flush mb-3">
            <li class="list-group-item"><strong>Motivo:</strong> {{ $tratamiento->motivo }}</li>
            <li class="list-group-item"><strong>Medicamento:</strong> {{ $tratamiento->medicamento }}</li>
            <li class="list-group-item"><strong>Vía:</strong> {{ $tratamiento->via ?? 'N/A' }}</li>
            <li class="list-group-item"><strong>Dosis:</strong> {{ $tratamiento->dosis ?? 'N/A' }} ml</li>
            <li class="list-group-item"><strong>Inicio:</strong> {{ $tratamiento->fecha }}</li>
            <li class="list-group-item"><strong>Fin:</strong> {{ $tratamiento->fecha_fin ?? 'En curso' }}</li>

            @if($alerta_activo)
                <li class="list-group-item">
                    <strong class="text-danger">{{ $alerta_activo }}</strong>
                </li>
            @endif

            <li class="list-group-item"><strong>Observaciones:</strong> {{ $tratamiento->observaciones ?? 'Sin observaciones' }}</li>
        </ul>

        <a href="{{ route('tratamientos.index') }}" class="btn btn-secondary">Volver</a>

    </div>
</div>
@endsection