@extends('layouts.layout')
@section('title', 'Registrar Tratamiento')

@section('content')
<h1 class="mb-4">Registrar Nuevo Tratamiento</h1>

<div class="card shadow-sm">
    <div class="card-body">

        <form action="{{ route('tratamientos.store') }}" method="POST">
            @csrf

                @include('tratamientos.form')

                <div class="mt-4 d-flex justify-content-between">
                    <a href="{{ route('tratamientos.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success">Guardar Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection