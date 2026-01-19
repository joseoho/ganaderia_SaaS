@extends('layouts.layout')
@section('title', 'Editar Compra')

@section('content')
<h1>Editar Compra</h1>

<form action="{{ route('compras.update', $compra->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label for="proveedor" class="form-label">Proveedor</label>
        <input type="text" name="proveedor" class="form-control" value="{{ $compra->proveedor }}" required>
    </div>
    <div class="mb-3">
        <label for="descripcion" class="form-label">Descripción</label>
        <textarea name="descripcion" class="form-control" required>{{ $compra->descripcion }}</textarea>
    </div>
    <div class="mb-3">
        <label for="fecha" class="form-label">Fecha</label>
        <input type="date" name="fecha" class="form-control" value="{{ $compra->fecha }}" required>
    </div>
    <div class="mb-3">
        <label for="monto" class="form-label">Monto</label>
        <input type="number" step="0.01" name="monto_total" class="form-control" value="{{ $compra->monto_total }}" required>
    </div>
    <button type="submit" class="btn btn-primary">Actualizar</button>
</form>
@endsection