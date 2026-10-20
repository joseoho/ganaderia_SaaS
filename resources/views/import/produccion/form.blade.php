@extends('layouts.layout')
@section('title', 'Importar '.$titulo)

@section('content')

<h2 class="mb-4">Importar {{ $titulo }}</h2>

<div class="card shadow-sm p-4">

    <p class="text-muted">
        Sube un archivo Excel (.xlsx, .xls, .csv) con registros de {{ mb_strtolower($titulo) }}.
        La primera fila deben ser los nombres de las columnas: en
        <code>docs/plantillas/plantilla_{{ $tipo }}.csv</code> tienes un ejemplo
        que puedes abrir en Excel y rellenar con tus datos.
    </p>

    <form action="{{ route('import.produccion.upload', ['tipo' => $tipo]) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
            <label>Archivo Excel</label>
            <input type="file" name="archivo" class="form-control" required>
        </div>

        <button class="btn btn-primary">Subir y continuar</button>
    </form>

</div>

@endsection
