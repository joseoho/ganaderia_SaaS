@extends('layouts.layout')
@section('title', 'Detalle Reproductivo')

@section('content')
<h1 class="mb-4">Detalle Reproductivo</h1>

<div class="card shadow-sm">
    <div class="card-body">

        <h5 class="card-title">
            Animal: {{ $reproduccion->animal->codigo_interno }}
        </h5>

        <ul class="list-group list-group-flush mb-3">
            <li class="list-group-item"><strong>Tipo:</strong> {{ ucfirst($reproduccion->tipo) }}</li>
            <li class="list-group-item"><strong>Fecha:</strong> {{ $reproduccion->fecha }}</li>
            <li class="list-group-item"><strong>Toro:</strong> {{ $reproduccion->toro ?? 'N/A' }}</li>
            <li class="list-group-item"><strong>Resultado:</strong> {{ $reproduccion->resultado ?? 'N/A' }}</li>
            <li class="list-group-item"><strong>Observaciones:</strong> {{ $reproduccion->observaciones ?? 'Sin observaciones' }}</li>
            
            @if($fecha_probable_parto)
                <li class="list-group-item">
                    <strong>Fecha probable de parto:</strong> 
                    <span class="text-primary">{{ $fecha_probable_parto }}</span>
                </li>
            @endif

            @if($alerta_parto)
                <li class="list-group-item">
                    <strong class="text-danger">{{ $alerta_parto }}</strong>
                </li>
            @endif


        </ul>

        <a href="{{ route('reproduccion.index') }}" class="btn btn-secondary">Volver</a>

    </div>
</div>
@endsection