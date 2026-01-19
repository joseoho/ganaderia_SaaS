@extends('layouts.layout')
@section('title', 'Editar Venta')

@section('content')
<h1>Editar Venta</h1>

<form action="{{ route('ventas.update', $venta->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label for="cliente" class="form-label">Cliente</label>
        <input type="text" name="cliente" class="form-control" value="{{ $venta->cliente }}" required>
    </div>
    <div class="mb-3">
        <label for="descripcion" class="form-label">Descripción</label>
        <textarea name="descripcion" class="form-control" required>{{ $venta->descripcion }}</textarea>
    </div>
    <div class="mb-3">
        <label for="fecha" class="form-label">Fecha</label>
        <input type="date" name="fecha" class="form-control" value="{{ $venta->fecha }}" required>
    </div>
    <div class="mb-3">
        <label for="monto" class="form-label">Monto</label>
        <input type="number" step="0.01" name="monto" class="form-control" value="{{ $venta->monto_total }}" required>
    </div>
    <button type="submit" class="btn btn-primary">Actualizar</button>
</form>
@endsection