@extends('layouts.layout')
@section('title', 'Editar Insumo')

@section('content')
<h1 class="mb-4">Editar Insumo</h1>

<div class="card shadow-sm">
    <div class="card-body">

        <form action="{{ route('inventario.update', $inventario->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Nombre del insumo</label>
                <input type="text" name="nombre" class="form-control" value="{{ $inventario->nombre }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Categoría</label>
                <select name="categoria" class="form-select">
                    <option value="">Seleccione</option>
                    <option value="alimento" {{ $inventario->categoria=='alimento'?'selected':'' }}>Alimento</option>
                    <option value="medicina" {{ $inventario->categoria=='medicina'?'selected':'' }}>Medicina</option>
                    <option value="herramienta" {{ $inventario->categoria=='herramienta'?'selected':'' }}>Herramienta</option>
                    <option value="otros" {{ $inventario->categoria=='otros'?'selected':'' }}>Otros</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Cantidad</label>
                <input type="number" name="cantidad" class="form-control" value="{{ $inventario->cantidad }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Unidad</label>
                <input type="text" name="unidad" class="form-control" value="{{ $inventario->unidad }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Cantidad mínima</label>
                <input type="number" name="minimo" class="form-control" value="0" required>
            </div>
            

            <div class="mb-3">
                <label class="form-label">Fecha de ingreso</label>
                <input type="date" name="fecha_ingreso" class="form-control" value="{{ $inventario->fecha_ingreso }}">
            </div>

            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control">{{ $inventario->descripcion }}</textarea>
            </div>

            <button class="btn btn-primary">Actualizar</button>
        </form>

    </div>
</div>
@endsection