@extends('layouts.layout')

@section('content')
<h1>Notificaciones</h1>

  <!-- Mensajes -->
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
<!-- 🔍 Barra de búsqueda y filtros -->
<form method="GET" action="{{ route('notificaciones.index') }}" class="row g-3 mb-4">
    <div class="col-md-4">
        <input type="text" name="search" class="form-control" placeholder="Buscar mensaje..."
               value="{{ request('search') }}">
    </div>
    <div class="col-md-3">
        <input type="date" name="fecha" class="form-control"
               value="{{ request('fecha') }}">
    </div>
    <div class="col-md-3">
        <select name="tipo" class="form-select">
            <option value="">-- Filtrar por tipo --</option>
            <option value="alerta" {{ request('tipo')=='alerta' ? 'selected' : '' }}>Alerta</option>
            <option value="info" {{ request('tipo')=='info' ? 'selected' : '' }}>Info</option>
            <option value="recordatorio" {{ request('tipo')=='recordatorio' ? 'selected' : '' }}>Recordatorio</option>
        </select>
    </div>
    <div class="col-md-2">
        <div class="col-12 text-end">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filtrar
            </button>
            <a href="{{ route('notificaciones.index') }}" class="btn btn-secondary">
                <i class="fas fa-broom"></i> Limpiar
            </a>
                
        </div>
    </div>
</form>

<a href="{{ route('notificaciones.create') }}" class="btn btn-success mb-3">Nueva Notificación</a>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>Mensaje</th>
            <th>Tipo</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse($notificaciones as $n)
        <tr>
            <td>{{ $n->id }}</td>
            <td>{{ $n->mensaje }}</td>
            <td>{{ $n->tipo }}</td>
            <td>{{ $n->fecha_envio }}</td>
            <td>{{ $n->estado }}</td>
            <td>
                <a href="{{ route('notificaciones.show', $n->id) }}" class="btn btn-info btn-sm">Ver</a>
                <a href="{{ route('notificaciones.edit', $n->id) }}" class="btn btn-warning btn-sm">Editar</a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="text-center">No se encontraron notificaciones</td>
        </tr>
        @endforelse
    </tbody>
</table>

<!-- 📄 Paginación -->
{{ $notificaciones->links() }}

@endsection
