@extends('layouts.layout')
@section('title', 'Dashboard')

@section('content')
<h1 class="mb-4">Dashboard General</h1>

<!-- Tarjetas de resumen -->
<div class="row mb-4">

    <div class="col-md-3">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <h5 class="card-title">Animales</h5>
                <h2>{{ $totalAnimales }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <h5 class="card-title">Compras</h5>
                <h2>${{ number_format($totalCompras, 2) }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <h5 class="card-title">Ventas</h5>
                <h2>${{ number_format($totalVentas, 2) }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <h5 class="card-title">Notificaciones Pendientes</h5>
                <h2>{{ $notificacionesPendientes }}</h2>
            </div>
        </div>
    </div>

</div>

<!-- Últimas actividades -->
<div class="row">

    <div class="col-md-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                Últimas Compras
            </div>
            <ul class="list-group list-group-flush">
                @forelse($ultimasCompras as $compra)
                    <li class="list-group-item">
                        <strong>{{ $compra->proveedor }}</strong> — 
                        ${{ number_format($compra->monto_total, 2) }}
                        <span class="text-muted float-end">{{ $compra->fecha }}</span>
                    </li>
                @empty
                    <li class="list-group-item text-center text-muted">Sin compras recientes</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                Últimas Ventas
            </div>
            <ul class="list-group list-group-flush">
                @forelse($ultimasVentas as $venta)
                    <li class="list-group-item">
                        <strong>{{ $venta->cliente }}</strong> — 
                        ${{ number_format($venta->monto_total, 2) }}
                        <span class="text-muted float-end">{{ $venta->fecha }}</span>
                    </li>
                @empty
                    <li class="list-group-item text-center text-muted">Sin ventas recientes</li>
                @endforelse
            </ul>
        </div>
    </div>

</div>

@endsection