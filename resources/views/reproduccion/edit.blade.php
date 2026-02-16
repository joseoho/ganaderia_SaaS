@extends('layouts.layout')
@section('title', 'Editar Evento Reproductivo')

@section('content')
<h1 class="mb-4">Editar Evento Reproductivo</h1>

<div class="card shadow-sm">
    <div class="card-body">

        <form action="{{ route('reproduccion.update', $reproduccion->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Animal</label>
                <select name="animal_id" class="form-select" required>
                    <option value="">Seleccione</option>
                    @foreach($animales as $a)
                        <option value="{{ $a->id }}"  ? 'selected' : '' >>
                            {{ $a->codigo_interno }} — {{ $a->raza }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Tipo</label>
                <select name="tipo" class="form-select" required>
                    <option value="monta" {{ $reproduccion->tipo == 'monta' ? 'selected' : '' }}>Monta</option>
                    <option value="inseminación" {{ $reproduccion->tipo == 'inseminación' ? 'selected' : '' }}>Inseminación</option>
                    <option value="diagnóstico" {{ $reproduccion->tipo == 'diagnóstico' ? 'selected' : '' }}>Diagnóstico</option>
                    <option value="parto" {{ $reproduccion->tipo == 'parto' ? 'selected' : '' }}>Parto</option>
                    <option value="aborto" {{ $reproduccion->tipo == 'aborto' ? 'selected' : '' }}>Aborto</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Fecha</label>
                <input type="date" name="fecha" class="form-control" value="{{ $reproduccion->fecha }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Toro / Pajilla</label>
                <input type="text" name="toro" class="form-control" value="{{ $reproduccion->toro }}">
            </div>

            <div class="mb-3">
                <label class="form-label">Resultado</label>
                <select name="resultado" class="form-select">
                    <option value="">Seleccione</option>
                    <option value="preñada" {{ $reproduccion->resultado == 'preñada' ? 'selected' : '' }}>Preñada</option>
                    <option value="vacía" {{ $reproduccion->resultado == 'vacía' ? 'selected' : '' }}>Vacía</option>
                    <option value="exitoso" {{ $reproduccion->resultado == 'exitoso' ? 'selected' : '' }}>Exitoso</option>
                    <option value="fallido" {{ $reproduccion->resultado == 'fallido' ? 'selected' : '' }}>Fallido</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control">{{ $reproduccion->observaciones }}</textarea>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('reproduccion.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Actualizar</button>
            </div>
        </form>

    </div>
</div>
@endsection