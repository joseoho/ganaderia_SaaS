
@extends('layouts.app')

@section('content')

<h2 class="mb-4">Mapear columnas de Producción</h2>

<div class="card shadow-sm p-4">

    <form action="{{ route('import.produccion.preview') }}" method="POST">
        @csrf

        <p class="text-muted">Selecciona qué columna corresponde a cada campo.</p>

        <div class="mb-3">
            <label>Animal ID</label>
            <select name="map[animal_id]" class="form-select">
                <option value="">-- No usar --</option>
                @foreach($headers as $h)
                    <option value="{{ $h }}">{{ $h }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Fecha</label>
            <select name="map[fecha]" class="form-select">
                <option value="">-- No usar --</option>
                @foreach($headers as $h)
                    <option value="{{ $h }}">{{ $h }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Litros (Producción de leche)</label>
            <select name="map[litros]" class="form-select">
                <option value="">-- No usar --</option>
                @foreach($headers as $h)
                    <option value="{{ $h }}">{{ $h }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Ganancia diaria (Producción de carne)</label>
            <select name="map[ganancia_diaria]" class="form-select">
                <option value="">-- No usar --</option>
                @foreach($headers as $h)
                    <option value="{{ $h }}">{{ $h }}</option>
                @endforeach
            </select>
        </div>

        <button class="btn btn-primary">Vista previa</button>
    </form>

</div>

@endsection
