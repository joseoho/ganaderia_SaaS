@extends('layouts.layout')
@section('title', 'Importar Datos del Animal')

@section('content')

<h2 class="mb-4">Importar animales desde Excel</h2>

<div class="card shadow-sm p-4">

    <p class="text-muted">
        Sube un archivo Excel (.xlsx, .xls, .csv) con tus animales.  
        En el siguiente paso podrás mapear las columnas.
    </p>

    <form action="{{ route('import.animales.upload') }}" method="POST" enctype="multipart/form-data">
        @csrf
<div class="mb-3">
            <label>Archivo Excel</label>
            <input type="file" name="archivo" class="form-control" required>
        </div>

        <button class="btn btn-primary">Subir y continuar</button>
    </form>

</div>

@endsection

