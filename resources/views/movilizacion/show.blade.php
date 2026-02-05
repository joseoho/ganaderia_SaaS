@extends('layouts.layout')
@section('title', 'Detalle de Movilización')

@section('content')
<h1 class="mb-4">Detalle de Movilización</h1>

<div class="card shadow-sm">
    <div class="card-body">

        <h5 class="card-title">
            Animal: {{ $movilizacione->animal->codigo_interno }}
        </h5>

        <ul class="list-group list-group-flush mb-3">
            {{-- <li class="list-group-item"><strong>Tipo:</strong> {{ ucfirst($movilizacione->tipo) }}</li> --}}
            <li class="list-group-item"><strong>Origen:</strong> {{ $movilizacione->origen ?? 'N/A' }}</li>
            <li class="list-group-item"><strong>Destino:</strong> {{ $movilizacione->destino ?? 'N/A' }}</li>
            <li class="list-group-item"><strong>Fecha:</strong> {{ $movilizacione->fecha }}</li>
            <li class="list-group-item"><strong>Descripción:</strong> {{ $movilizacione->motivo ?? 'Sin descripción' }}</li>
        </ul>

        <a href="{{ route('movilizaciones.index') }}" class="btn btn-secondary">Volver</a>

    </div>
</div>
@endsection