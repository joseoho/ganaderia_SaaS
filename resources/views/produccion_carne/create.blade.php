@extends('layouts.layout')
@section('title', 'Registrar Producción')

@section('content')
<div class="container">
    <div class="card shadow">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0">Registrar Producción de Carne</h4>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('produccion.store') }}">
                @csrf
                
                @include('produccion_carne.form')

                <div class="mt-4 d-flex justify-content-between">
                    <a href="{{ route('produccion.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success">Guardar Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection