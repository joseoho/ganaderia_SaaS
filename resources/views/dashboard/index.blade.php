@extends('layouts.layout')
@section('title', 'Dashboard')

@section('content')
<h1 class="mb-4">Dashboard General</h1>

<!-- Tarjetas de resumen -->
<div class="row mb-4">

    <div class="col-md-4">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <h5 class="card-title">Animales</h5>
                <h2>{{ $totalAnimales }}</h2>
            </div>
        </div>
    </div>

    {{-- Compras y Gastos van en tarjetas separadas porque son dos cosas
         distintas, y porque el balance resta las dos: sin la de compras, el
         balance no se puede cuadrar mirando la pantalla. --}}
    <div class="col-md-4">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <h5 class="card-title">Compras</h5>
                <h2>${{ number_format($totalCompras, 2) }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <h5 class="card-title">Gastos operativos</h5>
                <h2>${{ number_format($totalGastos, 2) }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <h5 class="card-title">Ventas</h5>
                <h2>${{ number_format($totalVentas, 2) }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm text-center bg-light">
            <div class="card-body">
                <h5 class="card-title">Balance Neto</h5>
                <h2 class="{{ ($totalVentas - ($totalCompras + $totalGastos)) >= 0 ? 'text-success' : 'text-danger' }}">
                    ${{ number_format($totalVentas - ($totalCompras + $totalGastos), 2) }}
                </h2>
                <small class="text-muted">Ventas − (compras + gastos)</small>
            </div>
        </div>
    </div>

    <div class="col-md-4">
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
                Últimos Gastos
            </div>
            <ul class="list-group list-group-flush">
                @forelse($ultimosGastos as $gasto)
                    <li class="list-group-item">
                        {{-- Un gasto tiene CATEGORIA (nomina, mantenimiento); el
                             proveedor es de las compras y en esta tabla no existe:
                             aqui salia un guion vacio en cada fila. --}}
                        <strong>{{ $gasto->categoria }}</strong>
                        @if($gasto->descripcion)
                            — {{ $gasto->descripcion }}
                        @endif
                        <span class="float-end">${{ number_format((float) $gasto->monto, 2) }}</span>
                        <span class="text-muted float-end me-3">{{ \App\Support\Formato::fecha($gasto->fecha) }}</span>
                    </li>
                @empty
                    <li class="list-group-item text-center text-muted">Sin gastos recientes</li>
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
                        <span class="text-muted float-end">{{ \App\Support\Formato::fecha($venta->fecha) }}</span>
                    </li>
                @empty
                    <li class="list-group-item text-center text-muted">Sin ventas recientes</li>
                @endforelse
            </ul>
        </div>
    </div>
    <div class="col-md-4">
    <div class="card shadow-sm text-center mb-4">
        <div class="card-header bg-primary text-white">
            Producción de Leche (Mes)
        </div>
        <div class="card-body">
            <h2>{{ $totalLecheMes }} L</h2>
        </div>
    </div>
</div>

    <div class="col-md-4">
        <div class="card shadow-sm text-center mb-4">
            <div class="card-header bg-success text-white">
                Ganancia de Carne (Mes)
            </div>
            <div class="card-body">
                <h2>{{ number_format($totalCarneMes, 2) }} kg</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-warning text-dark">
            Partos próximos (30 días)
        </div>
        <ul class="list-group list-group-flush">
            @forelse($partosProximos as $p)
                @php
                    $fpp = \Carbon\Carbon::parse($p->fecha)->addDays(283)->format('Y-m-d');
                @endphp
                <li class="list-group-item">
                    <strong>{{ $p->animal?->codigo_interno }}</strong>
                    <span class="float-end">{{ $fpp }}</span>
                </li>
            @empty
                <li class="list-group-item text-center text-muted">
                    No hay partos próximos
                </li>
            @endforelse
        </ul>
    </div>
</div>

</div>

@endsection