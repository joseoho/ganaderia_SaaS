@extends('layouts.layout')
@section('title', 'Editar Movilización')

@section('content')
<h1 class="mb-4">Editar Movilización</h1>

<div class="card shadow-sm">
    <div class="card-body">

        <form action="{{ route('movilizaciones.update', $movilizacione->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Animal</label>
                <select name="animal_id" class="form-select" required>
                    @foreach($animales as $a)
                        <option value="{{ $a->id }}" 
                            {{ $movilizacione->animal_id == $a->id ? 'selected' : '' }}>
                            {{ $a->codigo_interno }} — {{ $a->raza }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Tipo</label>
                <select name="tipo" class="form-select" required>
                    <option value="entrada" {{ $movilizacione->tipo=='entrada'?'selected':'' }}>Entrada</option>
                    <option value="salida" {{ $movilizacione->tipo=='salida'?'selected':'' }}>Salida</option>
                    <option value="interna" {{ $movilizacione->tipo=='interna'?'selected':'' }}>Interna</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Origen</label>
                <input type="text" name="origen" class="form-control" value="{{ $movilizacione->origen }}">
            </div>

            <div class="mb-3">
                <label class="form-label">Destino</label>
                <input type="text" name="destino" class="form-control" value="{{ $movilizacione->destino }}">
            </div>

            <div class="mb-3">
                <label class="form-label">Fecha</label>
                <input type="date" name="fecha" class="form-control" value="{{ $movilizacione->fecha }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control">{{ $movilizacione->motivo }}</textarea>
            </div>

            <button class="btn btn-primary">Actualizar</button>
        </form>

    </div>
</div>
@endsection