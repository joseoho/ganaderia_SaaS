@extends('layouts.layout')
@section('title', 'Detalle de Producción')

@section('content')
<h1 class="mb-4">Detalle de Producción</h1>

<div class="card shadow-sm">
    <div class="card-body">

        <h5 class="card-title">
            Animal: {{ $produccion->animal->codigo_interno }}
        </h5>

        <ul class="list-group list-group-flush mb-3">
            <li class="list-group-item"><strong>Peso:</strong> {{ $produccion->peso }} kg</li>
            <li class="list-group-item"><strong>Peso anterior:</strong> {{ $produccion->peso_anterior ?? 'N/A' }} kg</li>
            <li class="list-group-item"><strong>GDP:</strong> {{ $produccion->ganancia_diaria ? number_format($produccion->ganancia_diaria, 2) : 'N/A' }} kg/día</li>
            <li class="list-group-item"><strong>Fecha:</strong> {{ $produccion->fecha }}</li>
            <li class="list-group-item"><strong>Observaciones:</strong> {{ $produccion->observaciones ?? 'Sin observaciones' }}</li>
            <li class="list-group-item">
                    <strong>Ganancia total del mes:</strong>
                    <span class="text-success">{{ number_format($total_mes, 2) }} kg</span>
            </li>
 
        </ul>

        <a href="{{ route('produccion.index') }}" class="btn btn-secondary">Volver</a>

    </div>
</div>
@endsection