@extends('layouts.layout')
@section('title', 'Detalle del Gasto')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-eye me-2"></i>Detalle del Gasto</h5>
                    <a href="{{ route('gastos.index') }}" class="btn btn-sm btn-light">Volver al listado</a>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="text-muted d-block small fw-bold text-uppercase">Categoría</label>
                            <span class="badge bg-info text-dark fs-6">{{ $gasto->categoria }}</span>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <label class="text-muted d-block small fw-bold text-uppercase">Fecha de Registro</label>
                            <p class="fs-5">{{ \Carbon\Carbon::parse($gasto->fecha)->format('d/m/Y') }}</p>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-12 border-top pt-3 text-center">
                            <label class="text-muted d-block small fw-bold text-uppercase">Monto Total</label>
                            <h2 class="text-danger fw-bold">${{ number_format($gasto->monto, 2) }}</h2>
                        </div>
                    </div>

                    <div class="mb-4 p-3 bg-light rounded">
                        <label class="text-muted d-block small fw-bold text-uppercase mb-2">Descripción / Notas</label>
                        <p class="mb-0 text-secondary">
                            {{ $gasto->descripcion ?? 'No se proporcionó ninguna descripción para este gasto.' }}
                        </p>
                    </div>

                    <div class="d-flex justify-content-between border-top pt-3">
                        <form action="{{ route('gastos.destroy', $gasto->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este registro?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="fas fa-trash me-2"></i>Eliminar
                            </button>
                        </form>
                        
                        <div>
                            <a href="{{ route('gastos.edit', $gasto->id) }}" class="btn btn-warning">
                                <i class="fas fa-edit me-2"></i>Editar Información
                            </a>
                            <a href="{{ route('gastos.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Regresar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection