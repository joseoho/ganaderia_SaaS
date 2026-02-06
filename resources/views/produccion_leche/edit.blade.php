@extends('layouts.layout')
@section('title', 'Editar Producción de Leche')

@section('content')
<h1 class="mb-4">Editar Producción de Leche</h1>

<div class="card shadow-sm">
    <div class="card-body">

        <form action="{{ route('produccion_leche.update', $produccion_leche->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Animal</label>
                <select name="animal_id" class="form-select" required>
                    @foreach($animales as $a)
                        <option value="{{ $a->id }}" {{ $produccion_leche->animal_id == $a->id ? 'selected' : '' }}>
                            {{ $a->codigo_interno }} — {{ $a->raza }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Fecha</label>
                <input type="date" name="fecha" class="form-control" value="{{ $produccion_leche->fecha }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Turno</label>
                <select name="turno" class="form-select">
                    <option value="mañana" {{ $produccion_leche->turno=='mañana'?'selected':'' }}>Mañana</option>
                    <option value="tarde" {{ $produccion_leche->turno=='tarde'?'selected':'' }}>Tarde</option>
                    <option value="noche" {{ $produccion_leche->turno=='noche'?'selected':'' }}>Noche</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Litros</label>
                <input type="number" step="0.01" name="litros" class="form-control" value="{{ $produccion_leche->litros }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control">{{ $produccion_leche->observaciones }}</textarea>
            </div>

            <button class="btn btn-primary">Actualizar</button>
        </form>

    </div>
</div>
@endsection