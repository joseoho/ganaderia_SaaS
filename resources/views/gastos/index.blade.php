@extends('layouts.layout')
@section('title', 'Listado de Gastos')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-wallet text-primary"></i> Control de Gastos Operativos</h2>
        <a href="{{ route('gastos.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Registrar Gasto
        </a>
    </div>

    <div class="card shadow-sm pt-3 px-3">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Fecha</th>
                    <th>Categoría</th>
                    <th>Descripción</th>
                    <th class="text-end">Monto</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($gastos as $g)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($g->fecha)->format('d/m/Y') }}</td>
                    <td><span class="badge bg-info text-dark">{{ $g->categoria }}</span></td>
                    <td>{{ $g->descripcion ?? 'Sin descripción' }}</td>
                    <td class="text-end fw-bold text-danger">${{ number_format($g->monto, 2) }}</td> 
                    <td class="text-center">
                        <a href="{{ route('gastos.show', $g->id) }}" class="btn btn-sm btn-info" title="Ver Detalle">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('gastos.edit', $g->id) }}" class="btn btn-sm btn-warning" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('gastos.destroy', $g->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Estás seguro de que deseas eliminar este gasto?');">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted">No se han registrado gastos aún.</td>
                </tr>
                @endforelse
            </tbody>
            @if($gastos->count() > 0)
            <tfoot>
                <tr class="table-dark">
                    <td colspan="3" class="text-end text-uppercase">Total Gastos:</td>
                    <td class="text-end">${{ number_format($gastos->sum('monto'), 2) }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
        <div class="d-flex justify-content-center">
            {{ $gastos->links() }}
        </div>
    </div>
</div>
@endsection