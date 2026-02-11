@extends('layouts.layout')
@section('title', 'Detalle de Vacuna')

@section('content')
<h1 class="mb-4">Detalle de Vacuna</h1>

<div class="card shadow-sm">
    <div class="card-body">

        <h5 class="card-title">
            Animal: {{ $registro_vacuna->animal->codigo_interno }}
        </h5>

        <ul class="list-group list-group-flush mb-3">
            <li class="list-group-item"><strong>Vacuna:</strong> {{ $registro_vacuna->nombre }}</li>
            <li class="list-group-item"><strong>Lote:</strong> {{ $registro_vacuna->lote ?? 'N/A' }}</li>
            <li class="list-group-item"><strong>Proveedor:</strong> {{ $registro_vacuna->proveedor ?? 'N/A' }}</li>
            <li class="list-group-item"><strong>Vía:</strong> {{ $registro_vacuna->via ?? 'N/A' }}</li>
            <li class="list-group-item"><strong>Fecha:</strong> {{ $registro_vacuna->fecha_aplicacion }}</li>
            <li class="list-group-item"><strong>Dosis:</strong> {{ $registro_vacuna->dosis ?? 'N/A' }} ml</li>
            <li class="list-group-item"><strong>Próxima dosis:</strong> {{ $registro_vacuna->proxima_dosis }}</li>
            <li class="list-group-item"><strong>Observaciones:</strong> {{ $registro_vacuna->observaciones ?? 'Sin observaciones' }}</li>
        </ul>

        <a href="{{ route('registro_vacunas.index') }}" class="btn btn-secondary">Volver</a>

    </div>
</div>
@endsection