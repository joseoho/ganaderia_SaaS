@extends('layouts.layout')
@section('title', 'Mapear columnas del archivo')

@section('content')
<h2 class="mb-4">Mapear columnas del archivo</h2>

<div class="card shadow-sm p-4">

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(isset($headers) && is_array($headers) && count($headers) > 0)
        <div class="alert alert-info mb-4">
            <strong>Columnas detectadas en el archivo:</strong> 
            {{ implode(', ', $headers) }}
        </div>

        <form action="{{ route('import.animales.preview') }}" method="POST">
            @csrf

            <div class="row">
                {{-- Código Interno --}}
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Código Interno <span class="text-danger">*</span></label>
                    <select name="map[codigo_interno]" class="form-select" required>
                        <option value="">-- Seleccionar columna --</option>
                        @foreach($headers as $h)
                            <option value="{{ $h }}"
                                @if(isset($sugerencias['codigo_interno']) && $sugerencias['codigo_interno'] == $h) selected @endif>
                                {{ $h }}
                            </option>
                        @endforeach
                    </select>
                    @if(isset($sugerencias['codigo_interno']))
                        <small class="text-success">
                            <i class="fas fa-magic me-1"></i> Sugerencia: {{ $sugerencias['codigo_interno'] }}
                        </small>
                    @endif
                </div>

                {{-- Categoría --}}
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Categoría <span class="text-danger">*</span></label>
                    <select name="map[categoria]" class="form-select" required>
                        <option value="">-- Seleccionar columna --</option>
                        @foreach($headers as $h)
                            <option value="{{ $h }}"
                                @if(isset($sugerencias['categoria']) && $sugerencias['categoria'] == $h) selected @endif>
                                {{ $h }}
                            </option>
                        @endforeach
                    </select>
                    @if(isset($sugerencias['categoria']))
                        <small class="text-success">
                            <i class="fas fa-magic me-1"></i> Sugerencia: {{ $sugerencias['categoria'] }}
                        </small>
                    @endif
                </div>

                {{-- Raza --}}
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Raza <span class="text-danger">*</span></label>
                    <select name="map[raza]" class="form-select" required>
                        <option value="">-- Seleccionar columna --</option>
                        @foreach($headers as $h)
                            <option value="{{ $h }}"
                                @if(isset($sugerencias['raza']) && $sugerencias['raza'] == $h) selected @endif>
                                {{ $h }}
                            </option>
                        @endforeach
                    </select>
                    @if(isset($sugerencias['raza']))
                        <small class="text-success">
                            <i class="fas fa-magic me-1"></i> Sugerencia: {{ $sugerencias['raza'] }}
                        </small>
                    @endif
                </div>

                {{-- Tipo --}}
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Tipo</label>
                    <select name="map[tipo]" class="form-select">
                        <option value="">-- Seleccionar columna --</option>
                        @foreach($headers as $h)
                            <option value="{{ $h }}"
                                @if(isset($sugerencias['tipo']) && $sugerencias['tipo'] == $h) selected @endif>
                                {{ $h }}
                            </option>
                        @endforeach
                    </select>
                    @if(isset($sugerencias['tipo']))
                        <small class="text-success">
                            <i class="fas fa-magic me-1"></i> Sugerencia: {{ $sugerencias['tipo'] }}
                        </small>
                    @endif
                </div>

                {{-- Fecha de Nacimiento --}}
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Fecha de Nacimiento <span class="text-danger">*</span></label>
                    <select name="map[fecha_nacimiento]" class="form-select" required>
                        <option value="">-- Seleccionar columna --</option>
                        @foreach($headers as $h)
                            <option value="{{ $h }}"
                                @if(isset($sugerencias['fecha_nacimiento']) && $sugerencias['fecha_nacimiento'] == $h) selected @endif>
                                {{ $h }}
                            </option>
                        @endforeach
                    </select>
                    @if(isset($sugerencias['fecha_nacimiento']))
                        <small class="text-success">
                            <i class="fas fa-magic me-1"></i> Sugerencia: {{ $sugerencias['fecha_nacimiento'] }}
                        </small>
                    @endif
                </div>

                {{-- Sexo --}}
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Sexo <span class="text-danger">*</span></label>
                    <select name="map[sexo]" class="form-select" required>
                        <option value="">-- Seleccionar columna --</option>
                        @foreach($headers as $h)
                            <option value="{{ $h }}"
                                @if(isset($sugerencias['sexo']) && $sugerencias['sexo'] == $h) selected @endif>
                                {{ $h }}
                            </option>
                        @endforeach
                    </select>
                    @if(isset($sugerencias['sexo']))
                        <small class="text-success">
                            <i class="fas fa-magic me-1"></i> Sugerencia: {{ $sugerencias['sexo'] }}
                        </small>
                    @endif
                </div>

                {{-- Peso Entrada --}}
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Peso Entrada</label>
                    <select name="map[peso_entrada]" class="form-select">
                        <option value="">-- Seleccionar columna --</option>
                        @foreach($headers as $h)
                            <option value="{{ $h }}"
                                @if(isset($sugerencias['peso_entrada']) && $sugerencias['peso_entrada'] == $h) selected @endif>
                                {{ $h }}
                            </option>
                        @endforeach
                    </select>
                    @if(isset($sugerencias['peso_entrada']))
                        <small class="text-success">
                            <i class="fas fa-magic me-1"></i> Sugerencia: {{ $sugerencias['peso_entrada'] }}
                        </small>
                    @endif
                </div>

                {{-- Estado --}}
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Estado <span class="text-danger">*</span></label>
                    <select name="map[estado]" class="form-select" required>
                        <option value="">-- Seleccionar columna --</option>
                        @foreach($headers as $h)
                            <option value="{{ $h }}"
                                @if(isset($sugerencias['estado']) && $sugerencias['estado'] == $h) selected @endif>
                                {{ $h }}
                            </option>
                        @endforeach
                    </select>
                    @if(isset($sugerencias['estado']))
                        <small class="text-success">
                            <i class="fas fa-magic me-1"></i> Sugerencia: {{ $sugerencias['estado'] }}
                        </small>
                    @endif
                </div>
            </div>

            <div class="d-flex justify-content-between mt-3">
                <a href="{{ route('import.animales.form') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-eye me-1"></i> Ver vista previa
                </button>
            </div>
        </form>
    @else
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            No se encontraron encabezados en el archivo. Por favor, verifica que:
            <ul class="mb-0 mt-2">
                <li>El archivo no esté vacío</li>
                <li>La primera fila contenga los nombres de las columnas</li>
                <li>El formato sea CSV, XLSX o XLS válido</li>
            </ul>
        </div>
        <a href="{{ route('import.animales.form') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver al formulario de importación
        </a>
    @endif

</div>
@endsection