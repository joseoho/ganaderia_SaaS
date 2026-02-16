@extends('layouts.layout')
@section('title', 'Registrar Evento Reproductivo')

@section('content')
<h1 class="mb-4">Registrar Evento Reproductivo</h1>

<div class="card shadow-sm">
    <div class="card-body">

            <form action="{{ route('reproduccion.store') }}" method="POST">
                @csrf

                @include('reproduccion.form')
                    <div class="mt-4 d-flex justify-content-between">
                    <a href="{{ route('reproduccion.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success">Guardar Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection