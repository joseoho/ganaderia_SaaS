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

        @include('import.parciales.mapa-campos')

        <button class="btn btn-primary">Vista previa</button>
    </form>

</div>

@endsection
