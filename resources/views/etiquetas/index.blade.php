@extends('layouts.layout')
@section('title', 'Imprimir Etiquetas QR')

@section('content')

<h2 class="mb-4">Imprimir Etiquetas QR</h2>

<div class="card shadow-sm p-4">

    <form action="{{ route('animales.etiquetas.generar') }}" method="POST">
        @csrf

        <label class="form-label">Seleccione los animales</label>

        <div class="row">
            @foreach($animales as $animal)
                <div class="col-md-4 mb-2">
                    <label class="form-check-label">
                        <input type="checkbox" name="animales[]" value="{{ $animal->id }}" class="form-check-input">
                        {{ $animal->codigo_interno }} — {{ $animal->raza }}
                    </label>
                </div>
            @endforeach
        </div>

        <button class="btn btn-primary mt-3">Generar Etiquetas</button>

    </form>

</div>

@endsection