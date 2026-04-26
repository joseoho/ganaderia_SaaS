@extends('layouts.layout')
@section('title', 'Editar Gasto')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Gasto Operativo</h5>
                    <a href="{{ route('gastos.index') }}" class="btn btn-sm btn-outline-dark">Cancelar</a>
                </div>
                <div class="card-body">
                    <form action="{{ route('gastos.update', $gasto->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Categoría</label>
                                <select name="categoria" class="form-select @error('categoria') is-invalid @enderror" required>
                                    @php
                                        $categorias = ['Nomina', 'Servicios', 'Mantenimiento', 'Combustible', 'Alimentacion', 'Insumos', 'Otros'];
                                    @endphp
                                    @foreach($categorias as $cat)
                                        <option value="{{ $cat }}" {{ (old('categoria', $gasto->categoria) == $cat) ? 'selected' : '' }}>
                                            {{ $cat == 'Nomina' ? 'Pago de Personal (Nómina)' : ($cat == 'Alimentacion' ? 'Suplementos / Alimentación Extra' : $cat) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('categoria') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Monto ($)</label>
                                <input type="number" step="0.01" name="monto" class="form-control @error('monto') is-invalid @enderror" value="{{ old('monto', $gasto->monto) }}" required>
                                @error('monto') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fecha del Gasto</label>
                            <input type="date" name="fecha" class="form-control @error('fecha') is-invalid @enderror" value="{{ old('fecha', $gasto->fecha) }}" required>
                            @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción / Notas</label>
                            <textarea name="description" class="form-control" rows="3 text-secondary">{{ old('descripcion', $gasto->descripcion) }}</textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sync-alt me-2"></i>Actualizar Gasto
                            </button>
                            <a href="{{ route('gastos.index') }}" class="btn btn-outline-secondary">Descartar Cambios</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection