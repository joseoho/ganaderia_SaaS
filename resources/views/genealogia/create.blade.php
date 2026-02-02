@extends('layouts.layout')
@section('title', 'Registrar Genealogía')

@section('content')
<h1 class="mb-4">Registrar Genealogía</h1>

<div class="card shadow-sm">
    <div class="card-body">

        <form action="{{ route('genealogia.store') }}" method="POST">
            @csrf

                @include('genealogia.form')

                <div class="mt-4 d-flex justify-content-between">
                    <a href="{{ route('genealogia.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success">Guardar Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection