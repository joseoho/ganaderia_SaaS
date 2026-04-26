@extends('layouts.layout')
@section('title', 'Registrar Gasto')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Nuevo Gasto Operativo</h5>
                    <a href="{{ route('gastos.index') }}" class="btn btn-sm btn-light">Volver</a>
                </div>
                <div class="card-body">
                    <form action="{{ route('gastos.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Categoría</label>
                                <select name="categoria" class="form-select @error('categoria') is-invalid @enderror" required>
                                    <option value="">Seleccione una categoría</option>
                                    <option value="Nomina">Pago de Personal (Nómina)</option>
                                    <option value="Servicios">Servicios (Luz, Agua, Internet)</option>
                                    <option value="Mantenimiento">Mantenimiento de Cercas/Maquinaria</option>
                                    <option value="Combustible">Combustible y Transporte</option>
                                    <option value="Alimentacion">Suplementos / Alimentación Extra</option>
                                    <option value="Insumos">Insumos de Limpieza/Finca</option>
                                    <option value="Otros">Otros Gastos</option>
                                </select>
                                @error('categoria') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Monto ($)</label>
                                <input type="number" step="0.01" name="monto" class="form-control @error('monto') is-invalid @enderror" placeholder="0.00" required>
                                @error('monto') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fecha del Gasto</label>
                            <input type="date" name="fecha" class="form-control @error('fecha') is-invalid @enderror" value="{{ date('Y-m-d') }}" required>
                            @error('fecha') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción / Notas</label>
                            <textarea name="descripcion" class="form-control" rows="3" placeholder="Ej: Reparación del portón del potrero norte"></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i>Guardar Gasto
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection