@extends('layouts.layout')
@section('title', 'Editar Tratamiento')

@section('content')
<h1 class="mb-4">Editar Tratamiento</h1>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('tratamientos.update', $tratamiento->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label">Animal</label>
                    <select name="animal_id" class="form-select" required>
                        <option value="">Seleccione un animal</option>
                        @foreach($animales as $animal)
                            <option value="{{ $animal->id }}" 
                                {{ $tratamiento->animal_id == $animal->id ? 'selected' : '' }}>
                                {{ $animal->codigo_interno }} — {{ $animal->nombre }} ({{ $animal->raza }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Motivo del Tratamiento</label>
                    <input type="text" name="motivo" class="form-control" 
                           value="{{ $tratamiento->motivo }}" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Medicamento</label>
                    <input type="text" name="medicamento" class="form-control" 
                           value="{{ $tratamiento->medicamento }}" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Vía de Administración</label>
                    <select name="via" class="form-select">
                        <option value="">Seleccione una vía</option>
                        <option value="oral" {{ $tratamiento->via == 'oral' ? 'selected' : '' }}>Oral</option>
                        <option value="intramuscular" {{ $tratamiento->via == 'intramuscular' ? 'selected' : '' }}>Intramuscular</option>
                        <option value="subcutánea" {{ $tratamiento->via == 'subcutánea' ? 'selected' : '' }}>Subcutánea</option>
                        <option value="intravenosa" {{ $tratamiento->via == 'intravenosa' ? 'selected' : '' }}>Intravenosa</option>
                        <option value="tópica" {{ $tratamiento->via == 'tópica' ? 'selected' : '' }}>Tópica</option>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Dosis</label>
                    <input type="text" name="dosis" class="form-control" 
                           value="{{ $tratamiento->dosis }}">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Fecha de Inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control" 
                           value="{{ $tratamiento->fecha instanceof \Carbon\Carbon ? $tratamiento->fecha->format('Y-m-d') : $tratamiento->fecha }}" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Fecha de Fin</label>
                    <input type="date" name="fecha_fin" class="form-control" 
                           value="{{ $tratamiento->fecha_fin instanceof \Carbon\Carbon ? $tratamiento->fecha_fin->format('Y-m-d') : $tratamiento->fecha_fin }}">
                    <small class="text-muted">Dejar en blanco si es una dosis única o aún no finaliza</small>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="3">{{ $tratamiento->observaciones }}</textarea>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('tratamientos.index') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Actualizar Tratamiento</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection