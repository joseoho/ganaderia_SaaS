@extends('layouts.layout')
@section('title', 'Mapear columnas de '.$titulo)

@section('content')

<h2 class="mb-4">Mapear columnas de {{ $titulo }}</h2>

<div class="card shadow-sm p-4">

    <div class="alert alert-info mb-4">
        <strong>Columnas detectadas en el archivo:</strong>
        {{ implode(', ', $headers) }}
    </div>

    <p class="text-muted">
        Las columnas marcadas con <span class="text-danger">*</span> son obligatorias:
        una fila a la que le falte una se omite y se cuenta al final.
    </p>

    <form action="{{ route('import.produccion.preview', ['tipo' => $tipo]) }}" method="POST">
        @csrf

        <div class="row">
            @foreach($campos as $campo => $etiqueta)
                @php($esObligatorio = in_array($campo, $obligatorios, true))

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">
                        {{ $etiqueta }} @if($esObligatorio)<span class="text-danger">*</span>@endif
                    </label>

                    <select name="map[{{ $campo }}]" class="form-select" @if($esObligatorio) required @endif>
                        <option value="">-- No usar --</option>
                        @foreach($headers as $h)
                            <option value="{{ $h }}"
                                @if(isset($sugerencias[$campo]) && $sugerencias[$campo] == $h) selected @endif>
                                {{ $h }}
                            </option>
                        @endforeach
                    </select>

                    @if(isset($sugerencias[$campo]))
                        <small class="text-success">
                            <i class="fas fa-magic me-1"></i> Sugerencia: {{ $sugerencias[$campo] }}
                        </small>
                    @endif
                </div>
            @endforeach
        </div>

        <button class="btn btn-primary">Vista previa</button>
    </form>

</div>

@endsection
