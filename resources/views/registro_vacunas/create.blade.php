@extends('layouts.layout')
@section('title', 'Registrar Vacuna')

@section('content')
<h1 class="mb-4">Registrar Vacuna</h1>

<div class="card shadow-sm">
    <div class="card-body">

        <form action="{{ route('registro_vacunas.store') }}" method="POST">
            @csrf

            @include('registro_vacunas.form')
            <div class="d-grid">
                <button class="btn btn-primary">Guardar Vacuna</button>
            </div>
            </form>
    </div>
</div>
@endsection