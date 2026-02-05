@extends('layouts.layout')
@section('title', 'Editar Producción')

@section('content')
<h1 class="mb-4">Editar Producción</h1>

<div class="card shadow-sm">
    <div class="card-body">

        <form action="{{ route('produccion.update', $produccion->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Animal</label>
                <select name="animal_id" class="form-select" required>
                    @foreach($animales as $a)
                        <option value="{{ $a->id }}" {{ $produccion->animal_id == $a->id ? 'selected' : '' }}>
                            {{ $a->codigo_interno }} — {{ $a->raza }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Fecha</label>
                <input type="date" name="fecha" class="form-control" value="{{ $produccion->fecha }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Peso (kg)</label>
                <input type="number" step="0.01" name="peso" class="form-control" value="{{ $produccion->peso }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control">{{ $produccion->observaciones }}</textarea>
            </div>

            <button class="btn btn-primary">Actualizar</button>
        </form>

    </div>
</div>
@endsection