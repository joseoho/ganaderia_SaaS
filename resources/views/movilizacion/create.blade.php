@extends('layouts.layout')
@section('title', 'Registrar Movilización')

@section('content')
<h1 class="mb-4">Registrar Movilización</h1>

<div class="card shadow-sm">
    <div class="card-body">

            
            <form method="POST" action="{{ route('movilizaciones.store') }}">
                @csrf
                
                @include('movilizacion.form')

                <div class="mt-4 d-flex justify-content-between">
                    <a href="{{ route('movilizaciones.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success">Guardar Registro</button>
                </div>
            </form>
            @endsection 
    </div>
</div>