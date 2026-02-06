@extends('layouts.layout')
@section('title', 'Registrar Producción de Leche')

@section('content')
<h1 class="mb-4">Registrar Producción de Leche</h1>

<div class="card shadow-sm">
    <div class="card-body">

        <form action="{{ route('produccion_leche.store') }}" method="POST">
            @csrf

         @include('produccion_leche.form')
          <div class="mt-4 d-flex justify-content-between">
                    <a href="{{ route('produccion_leche.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success">Guardar Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection