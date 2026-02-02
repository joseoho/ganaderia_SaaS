@extends('layouts.layout')
@section('title', 'Detalle de Genealogía')

@section('content')
<h1 class="mb-4">Detalle de Genealogía</h1>

<div class="card shadow-sm">
    <div class="card-body">

        <h5 class="card-title">Animal: {{ $genealogium->animal->codigo_interno }}</h5>

        <ul class="list-group list-group-flush mb-3">
            <li class="list-group-item">
                <strong>Padre:</strong>
                {{ $genealogium->padre?->codigo_interno ?? 'No registrado' }}
            </li>

            <li class="list-group-item">
                <strong>Madre:</strong>
                {{ $genealogium->madre?->codigo_interno ?? 'No registrado' }}
            </li>

            <li class="list-group-item">
                <strong>Observaciones:</strong>
                <br>
                {{ $genealogium->observaciones ?? 'Sin observaciones' }}
            </li>
        </ul>

        <a href="{{ route('genealogia.index') }}" class="btn btn-secondary">Volver</a>

    </div>
</div>
@endsection