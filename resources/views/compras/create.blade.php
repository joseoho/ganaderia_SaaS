@extends('layouts.layout')
@section('title', 'Registrar Compra')

@section('content')
<div class="container">
    <div class="card shadow">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0">Registrar Nueva Compra</h4>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('compras.store') }}">
                @csrf
                
                @include('compras.form')

                <div class="mt-4 d-flex justify-content-between">
                    <a href="{{ route('compras.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success">Guardar Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection