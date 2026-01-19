@extends('layouts.layout')

@section('content')

<h1>Mis Compras</h1>

<form method="GET" action="{{ route('compras.index') }}" class="row g-3 mb-4">
    <div class="col-md-4">
        <input type="text" name="search" class="form-control" placeholder="Buscar por proveedor o descripción..."
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
        <a href="{{ route('compras.index') }}" class="btn btn-secondary w-100">Limpiar</a> 
    </div>   
</form>

<a href="{{ route('compras.create') }}" class="btn btn-success mb-3">Registrar Compra</a>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>Proveedor</th>
            <th>Descripción</th>
            <th>Fecha</th>
            <th>Monto</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse($compras as $compra)
        <tr>
            <td>{{ $compra->proveedor }}</td>
            <td>{{ $compra->descripcion }}</td>
            <td>{{ $compra->fecha }}</td>
            <td>{{ $compra->monto_total }}</td>
            <td>
                <a href="{{ route('compras.show', $compra->id) }}" class="btn btn-info btn-sm">Ver</a>
                <a href="{{ route('compras.edit', $compra->id) }}" class="btn btn-warning btn-sm">Editar</a>
                <form action="{{ route('compras.destroy', $compra->id) }}" method="POST" class="d-inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este registro?')">Eliminar</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="5" class="text-center">No se encontraron compras</td></tr>
        @endforelse
    </tbody>
</table>

{{ $compras->links() }}
@endsection