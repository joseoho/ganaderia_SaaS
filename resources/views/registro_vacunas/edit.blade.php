@extends('layouts.layout')

@section('title', 'Editar Vacuna')

@section('content')
<div class="container mt-4">
    <h1 class="mb-4">Editar Vacuna</h1>

    <!-- Mostrar errores si los hay -->
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('registro_vacunas.update', $registro_vacuna->id) }}" method="POST" id="formEditarVacuna">
        @csrf
        @method('PUT')
        
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="animal_id" class="form-label">Animal *</label>
                            <select name="animal_id" id="animal_id" class="form-select @error('animal_id') is-invalid @enderror" required>
                                <option value="">Seleccione un animal</option>
                                @foreach($animales as $a)
                                    <option value="{{ $a->id }}" {{ old('animal_id', $registro_vacuna->animal_id) == $a->id ? 'selected' : '' }}>
                                        {{ $a->codigo_interno }} — {{ $a->raza }}
                                    </option>
                                @endforeach
                            </select>
                            @error('animal_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Vacuna *</label>
                            <select name="nombre" id="nombre" class="form-select @error('nombre') is-invalid @enderror" required>
                                <option value="">Seleccione una vacuna</option>
                                @php
                                    $vacunas = [
                                        'fiebre_aftosa' => 'Fiebre Aftosa',
                                        'brucelosis' => 'Brucelosis',
                                        'rabia_silvestre' => 'Rabia Silvestre',
                                        'paratuberculosis' => 'Paratuberculosis',
                                        'leptospirosis' => 'Leptospirosis',
                                        'clostridiales' => 'Clostridiales',
                                        'otra' => 'Otra'
                                    ];
                                @endphp
                                @foreach($vacunas as $key => $value)
                                    <option value="{{ $key }}" {{ old('nombre', $registro_vacuna->nombre) == $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="lote" class="form-label">Lote</label>
                            <input type="text" name="lote" id="lote" 
                                   class="form-control @error('lote') is-invalid @enderror" 
                                   value="{{ old('lote', $registro_vacuna->lote) }}">
                            @error('lote')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="proveedor" class="form-label">Proveedor</label>
                            <input type="text" name="proveedor" id="proveedor" 
                                   class="form-control @error('proveedor') is-invalid @enderror" 
                                   value="{{ old('proveedor', $registro_vacuna->proveedor) }}">
                            @error('proveedor')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="via" class="form-label">Vía de aplicación</label>
                            <select name="via" id="via" class="form-select @error('via') is-invalid @enderror">
                                <option value="">Seleccione</option>
                                <option value="subcutánea" {{ old('via', $registro_vacuna->via) == 'subcutánea' ? 'selected' : '' }}>Subcutánea</option>
                                <option value="intramuscular" {{ old('via', $registro_vacuna->via) == 'intramuscular' ? 'selected' : '' }}>Intramuscular</option>
                                <option value="oral" {{ old('via', $registro_vacuna->via) == 'oral' ? 'selected' : '' }}>Oral</option>
                            </select>
                            @error('via')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="fecha" class="form-label">Fecha *</label>
                            <input type="date" name="fecha" id="fecha" 
                                   class="form-control @error('fecha') is-invalid @enderror" 
                                   value="{{ old('fecha', $registro_vacuna->fecha ? \Carbon\Carbon::parse($registro_vacuna->fecha)->format('Y-m-d') : '') }}" 
                                   required>
                            @error('fecha')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="dosis" class="form-label">Dosis (ml)</label>
                            <input type="number" step="0.01" name="dosis" id="dosis" 
                                   class="form-control @error('dosis') is-invalid @enderror" 
                                   value="{{ old('dosis', $registro_vacuna->dosis) }}">
                            @error('dosis')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="proxima_dosis" class="form-label">Próxima dosis *</label>
                            <input type="date" name="proxima_dosis" id="proxima_dosis" 
                                   class="form-control @error('proxima_dosis') is-invalid @enderror" 
                                   value="{{ old('proxima_dosis', $registro_vacuna->proxima_dosis ? \Carbon\Carbon::parse($registro_vacuna->proxima_dosis)->format('Y-m-d') : '') }}" 
                                   required>
                            @error('proxima_dosis')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="observaciones" class="form-label">Observaciones</label>
                    <textarea name="observaciones" id="observaciones" 
                              class="form-control @error('observaciones') is-invalid @enderror" 
                              rows="3">{{ old('observaciones', $registro_vacuna->observaciones) }}</textarea>
                    @error('observaciones')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Actualizar Vacuna</button>
                <a href="{{ route('registro_vacunas.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </div>
    </form>
</div>
@endsection