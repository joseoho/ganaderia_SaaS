@extends('layouts.layout')
@section('title', 'Editar usuario')

@section('content')

<h1 class="h3 mb-4">Editar usuario</h1>

<div class="card shadow-sm p-4">
    <form method="POST" action="{{ route('usuarios.update', $usuario) }}">
        @csrf
        @method('PUT')

        @include('usuarios._formulario', [
            'usuario' => $usuario,
            'esEdicion' => true,
            'roles' => \App\Enums\Rol::etiquetas(),
        ])

        <div class="d-flex gap-2 mt-2">
            <button class="btn btn-primary">Guardar cambios</button>
            <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

@endsection
