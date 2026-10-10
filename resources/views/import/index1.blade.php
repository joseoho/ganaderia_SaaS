@extends('layouts.layout')
@section('title', 'Importar Datos del Animal')

@section('content')

<h2 class="mb-4">Importación de datos</h2>

<div class="row">

    <div class="col-md-4 mb-3">
        <div class="card shadow-sm p-3 text-center">
            <h5>Animales</h5>
            <p class="text-muted">Importar animales desde Excel</p>
            <a href="{{ route('import.animales.form') }}" class="btn btn-primary btn-sm">Importar</a>
        </div>
    </div>

    {{-- Aquí luego puedes agregar producción, compras, ventas, etc. --}}

</div>

@endsection
