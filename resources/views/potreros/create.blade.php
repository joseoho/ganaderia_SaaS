@extends('layouts.layout')
@section('title', 'Registrar Potrero')

@section('content')
<div class="container">
    <div class="card shadow">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0">Registrar Nuevo Potrero</h4>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('potreros.store') }}">
                @csrf
                
                @include('potreros.form')

                <div class="mt-4 d-flex justify-content-between">
                    <a href="{{ route('potreros.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success">Guardar Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection