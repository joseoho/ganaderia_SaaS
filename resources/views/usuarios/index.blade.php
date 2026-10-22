@extends('layouts.layout')
@section('title', 'Usuarios')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Usuarios de la ganadería</h1>
    <a href="{{ route('usuarios.create') }}" class="btn btn-success">
        <i class="fas fa-user-plus"></i> Nuevo usuario
    </a>
</div>

<p class="text-muted">
    Quien entra como <strong>usuario</strong> trabaja con los animales (registra producción, sanidad y
    potreros) y consulta informes, pero no ve dinero, no borra y no importa archivos.
</p>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Correo verificado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            @forelse($usuarios as $usuario)
                <tr>
                    <td>
                        {{ $usuario->name }}
                        @if((int) $usuario->id === (int) auth()->id())
                            <span class="badge bg-secondary ms-1">tú</span>
                        @endif
                    </td>
                    <td>{{ $usuario->email }}</td>
                    <td>
                        <span class="badge {{ $usuario->esAdministrador() ? 'bg-primary' : 'bg-info text-dark' }}">
                            {{ $usuario->nombreDelRol() }}
                        </span>
                    </td>
                    <td>
                        @if($usuario->estaActivo())
                            <span class="badge bg-success">Activo</span>
                        @else
                            <span class="badge bg-secondary">Desactivado</span>
                        @endif
                    </td>
                    <td>
                        @if($usuario->hasVerifiedEmail())
                            <i class="fas fa-check text-success" title="Verificado"></i>
                        @else
                            <span class="text-muted small">Pendiente</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('usuarios.edit', $usuario) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-pen"></i> Editar
                        </a>

                        @if((int) $usuario->id !== (int) auth()->id())
                            <form method="POST" action="{{ route('usuarios.estado', $usuario) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                @if($usuario->estaActivo())
                                    <button class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('¿Desactivar la cuenta de {{ $usuario->name }}? No podrá entrar, pero su histórico se conserva.')">
                                        <i class="fas fa-user-slash"></i> Desactivar
                                    </button>
                                @else
                                    <button class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-user-check"></i> Activar
                                    </button>
                                @endif
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        Todavía no hay usuarios en esta ganadería.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $usuarios->links() }}
</div>

@endsection
