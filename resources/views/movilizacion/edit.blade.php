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
                    @php
                        $tipos = ['entrada' => 'Entrada', 'salida' => 'Salida', 'interna' => 'Interna'];
                        $tipoActual = old('tipo', $movilizacione->tipo);
                        if ($tipoActual && ! array_key_exists($tipoActual, $tipos)) {
                            $tipos[$tipoActual] = ucfirst($tipoActual);
                        }
                    @endphp
                    @foreach ($tipos as $valor => $texto)
                        <option value="{{ $valor }}" {{ $tipoActual == $valor ? 'selected' : '' }}>{{ $texto }}</option>
                    @endforeach
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
                <input type="date" name="fecha" class="form-control" value="{{ \App\Support\Formato::fecha($movilizacione->fecha) }}" required>
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