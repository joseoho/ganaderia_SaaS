@extends('layouts.layout')

@section('content')
<div class="container">
    <div class="card shadow">
        <div class="card-header bg-warning text-dark">
            <h4 class="mb-0">Editar Animal: {{ $animal->codigo_interno }}</h4>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('animales.update', $animal->id) }}">
                @csrf
                @method('PUT')
                
                @include('animales.form')

                <div class="mt-4 d-flex justify-content-between">
                    <a href="{{ route('animales.index') }}" class="btn btn-secondary">Regresar</a>
                    <button type="submit" class="btn btn-primary">Actualizar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection