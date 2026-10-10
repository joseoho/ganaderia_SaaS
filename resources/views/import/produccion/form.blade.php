@extends('layouts.layout')

@section('content')

<h2 class="mb-4">Importar Producción</h2>

<div class="card shadow-sm p-4">

    <p class="text-muted">
        Sube un archivo Excel (.xlsx, .xls, .csv) con registros de producción.
    </p>

    <form action="{{ route('import.produccion.upload') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
            <label>Archivo Excel</label>
            <input type="file" name="archivo" class="form-control" required>
        </div>

        <button class="btn btn-primary">Subir y continuar</button>
    </form>

</div>

@endsection
