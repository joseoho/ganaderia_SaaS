@extends('layouts.layout')
@section('title', 'Detalle de Compra')

@section('content')
<h1>Detalle de Compra</h1>

<ul class="list-group">
    <li class="list-group-item"><strong>Proveedor:</strong> {{ $compra->proveedor }}</li>
    <li class="list-group-item"><strong>Descripción:</strong> {{ $compra->descripcion }}</li>
    <li class="list-group-item"><strong>Fecha:</strong> {{ \App\Support\Formato::fecha($compra->fecha) }}</li>
    <li class="list-group-item"><strong>Monto:</strong> {{ $compra->monto_total }}</li>
</ul>

<a href="{{ route('compras.index') }}" class="btn btn-secondary mt-3">Volver</a>
@endsection