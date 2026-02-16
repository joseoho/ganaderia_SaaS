@extends('layouts.layout')
@section('title', 'Detalle de Producción de Leche')

@section('content')
<h1 class="mb-4">Detalle de Producción de Leche</h1>

<div class="card shadow-sm">
    <div class="card-body">

        <h5 class="card-title">
            Animal: {{ $produccion_leche->animal->codigo_interno }}
        </h5>

        <ul class="list-group list-group-flush mb-3">
            <li class="list-group-item"><strong>Litros:</strong> {{ $produccion_leche->litros }}</li>
            <li class="list-group-item"><strong>Litros anteriores:</strong> {{ $produccion_leche->litros_anteriores ?? 'N/A' }}</li>
            <li class="list-group-item"><strong>Variación:</strong> {{ $produccion_leche->variacion ?? 'N/A' }}</li>
            <li class="list-group-item"><strong>Turno:</strong> {{ ucfirst($produccion_leche->turno) }}</li>
            <li class="list-group-item"><strong>Fecha:</strong> {{ $produccion_leche->fecha }}</li>
            <li class="list-group-item"><strong>Observaciones:</strong> {{ $produccion_leche->observaciones ?? 'Sin observaciones' }}</li>
            <li class="list-group-item">
            <strong>Total producido en el mes:</strong>
            <span class="text-primary">{{ $total_mes }} litros</span>
            </li>

        </ul>

        <a href="{{ route('produccion_leche.index') }}" class="btn btn-secondary">Volver</a>

    </div>
</div>
@endsection