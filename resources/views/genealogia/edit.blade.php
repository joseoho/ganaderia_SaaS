@extends('layouts.layout')
@section('title', 'Editar Genealogía')

@section('content')
<h1 class="mb-4">Editar Genealogía</h1>

<div class="card shadow-sm">
    <div class="card-body">

        <form action="{{ route('genealogia.update', $genealogium->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Animal (Hijo)</label>
                <select name="animal_id" class="form-select" required>
                    @foreach($animales as $a)
                        <option value="{{ $a->id }}" 
                            {{ $genealogium->animal_id == $a->id ? 'selected' : '' }}>
                            {{ $a->codigo_interno }} — {{ $a->raza }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Padre</label>
                <select name="padre_id" class="form-select">
                    <option value="">No registrado</option>
                    @foreach($animales as $a)
                        <option value="{{ $a->id }}" 
                            {{ $genealogium->padre_id == $a->id ? 'selected' : '' }}>
                            {{ $a->codigo_interno }} — {{ $a->raza }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Madre</label>
                <select name="madre_id" class="form-select">
                    <option value="">No registrado</option>
                    @foreach($animales as $a)
                        <option value="{{ $a->id }}" 
                            {{ $genealogium->madre_id == $a->id ? 'selected' : '' }}>
                            {{ $a->codigo_interno }} — {{ $a->raza }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="3">{{ $genealogium->observaciones }}</textarea>
            </div>

            <button class="btn btn-primary">Actualizar</button>
        </form>

    </div>
</div>
@endsection