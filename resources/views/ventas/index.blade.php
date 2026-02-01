@extends('layouts.layout')
@section('title', 'Mis Ventas')

@section('content')
<h1>Mis Ventas</h1>

<!-- 🔍 Barra de búsqueda y filtros -->
<form method="GET" action="{{ route('ventas.index') }}" class="row g-3 mb-4">
    <div class="col-md-4">
        <input type="text" name="search" class="form-control" placeholder="Buscar por cliente o descripción..."
               value="{{ request('search') }}">
    </div>
    <div class="col-md-3">
        <input type="date" name="fecha_desde" class="form-control" value="{{ request('fecha_desde') }}">
    </div>
    <div class="col-md-3">
        <input type="date" name="fecha_hasta" class="form-control" value="{{ request('fecha_hasta') }}">
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
    </div>
    <div class="col-md-2">
        <a href="{{ route('ventas.index') }}" class="btn btn-secondary w-100">Limpiar</a> 
    </div>   
</form>

<a href="{{ route('ventas.create') }}" class="btn btn-success mb-3">Registrar Venta</a>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>Cliente</th>
            <th>Descripción</th>
            <th>Fecha</th>
            <th>Monto</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse($ventas as $venta)
        <tr>
            <td>{{ $venta->cliente }}</td>
            <td>{{ $venta->descripcion }}</td>
            <td>{{ $venta->fecha }}</td>
            <td>{{ $venta->monto_total }}</td>
            <td>
                <a href="{{ route('ventas.show', $venta->id) }}" class="btn btn-info btn-sm">Ver</a>
                <a href="{{ route('ventas.edit', $venta->id) }}" class="btn btn-warning btn-sm">Editar</a>
                <form action="{{ route('ventas.destroy', $venta->id) }}" method="POST" style="display:inline;">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger btn-sm">Eliminar</button>
                </form>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="text-center">No se encontraron ventas</td>
        </tr>
        @endforelse
    </tbody>
</table>

<!-- 📄 Paginación -->
{{ $ventas->links() }}
@endsection