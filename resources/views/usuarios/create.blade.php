@extends('layouts.layout')
@section('title', 'Nuevo usuario')

@section('content')

<h1 class="h3 mb-4">Nuevo usuario</h1>

<div class="card shadow-sm p-4">
    <form method="POST" action="{{ route('usuarios.store') }}">
        @csrf

        @include('usuarios._formulario', [
            'usuario' => $usuario,
            'esEdicion' => false,
            'roles' => \App\Enums\Rol::etiquetas(),
        ])

        <div class="d-flex gap-2 mt-2">
            <button class="btn btn-success">Crear usuario</button>
            <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

@endsection
