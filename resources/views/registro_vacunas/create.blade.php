@extends('layouts.layout')
@section('title', 'Registrar Vacuna')

@section('content')
<div class="container">
    <div class="card shadow">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0">Registrar Nueva Vacuna</h4>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('registro_vacunas.store') }}">
                @csrf
                
                @include('registro_vacunas.form')

                <div class="mt-4 d-flex justify-content-between">
                    <a href="{{ route('registro_vacunas.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success">Guardar Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection