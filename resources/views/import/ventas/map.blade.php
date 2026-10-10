@extends('layouts.app')

@section('content')

<h2 class="mb-4">Mapear columnas de Ventas</h2>

<div class="card shadow-sm p-4">

    <form action="{{ route('import.ventas.preview') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label>Cliente</label>
            <select name="map[cliente]" class="form-select">
                <option value="">-- No usar --</option>
                @foreach($headers as $h)
                    <option value="{{ $h }}">{{ $h }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Descripción</label>
            <select name="map[descripcion]" class="form-select">
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
            <label>Monto</label>
            <select name="map[monto]" class="form-select">
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
