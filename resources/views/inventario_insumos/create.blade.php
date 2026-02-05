@extends('layouts.layout')
@section('title', 'Registrar Insumo')

@section('content')
<h1 class="mb-4">Registrar Insumo</h1>

<div class="card shadow-sm">
    <div class="card-body">

        <form action="{{ route('inventario.store') }}" method="POST">
            @csrf


                @include('inventario_insumos.form')

                <div class="mt-4 d-flex justify-content-between">
                    <a href="{{ route('inventario.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success">Guardar Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection