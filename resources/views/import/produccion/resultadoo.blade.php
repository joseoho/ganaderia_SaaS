
@extends('layouts.layout')
@section('title', 'Resultado de la importación')

@section('content')

<h2 class="mb-4">Resultado de la importación</h2>

<div class="card shadow-sm p-4">

    <p><strong>Registros importados:</strong> {{ $importados }}</p>

    @if(count($errores))
        <hr>
        <h5>Errores detectados</h5>
        <ul>
            @foreach($errores as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    @else
        <p class="text-success">No se detectaron errores.</p>
    @endif

    <a href="{{ route('import.index') }}" class="btn btn-primary mt-3">Volver al módulo de importación</a>

</div>

@endsection
