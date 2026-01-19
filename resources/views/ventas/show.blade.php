@extends('layouts.layout')
@section('title', 'Detalle de Venta')

@section('content')
<h1>Detalle de Venta</h1>

<ul class="list-group">
    <li class="list-group-item"><strong>Cliente:</strong> {{ $venta->cliente }}</li>
    <li class="list-group-item"><strong>Descripción:</strong> {{ $venta->descripcion }}</li>
    <li class="list-group-item"><strong>Fecha:</strong> {{ $venta->fecha }}</li>
    <li class="list-group-item"><strong>Monto:</strong> {{ $venta->monto_total }}</li>
</ul>

<a href="{{ route('ventas.index') }}" class="btn btn-secondary mt-3">Volver</a>
@endsection