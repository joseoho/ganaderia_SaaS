@extends('layouts.layout')

@section('content')

<h2 class="mb-4">Resultado de la Importación</h2>

<div class="card shadow-sm p-4">

    <h4>Registros importados: {{ $importados }}</h4>

    <a href="{{ route('import.index') }}" class="btn btn-primary mt-3">Volver al módulo de importación</a>

</div>

@endsection
