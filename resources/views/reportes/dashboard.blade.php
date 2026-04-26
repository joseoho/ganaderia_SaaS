@extends('layouts.layout')

@section('content')

<div class="container">

    <h2 class="mb-4">Dashboard de Reportes</h2>

    {{-- TARJETAS SUPERIORES --}}
    <div class="row">

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm p-3 text-center">
                <i class="fas fa-shopping-cart fa-2x text-primary"></i>
                <h4 class="mt-2">{{ $totalCompras }}</h4>
                <p class="text-muted">Compras registradas</p>
                <a href="{{ route('reportes.compras') }}" class="btn btn-sm btn-primary">Ver reporte</a>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm p-3 text-center">
                <i class="fas fa-cash-register fa-2x text-success"></i>
                <h4 class="mt-2">{{ $totalVentas }}</h4>
                <p class="text-muted">Ventas registradas</p>
                <a href="{{ route('reportes.ventas') }}" class="btn btn-sm btn-success">Ver reporte</a>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm p-3 text-center">
                <i class="fas fa-drumstick-bite fa-2x text-danger"></i>
                <h4 class="mt-2">{{ number_format($totalCarne, 2) }} kg</h4>
                <p class="text-muted">Producción de carne</p>
                <a href="{{ route('reportes.carne') }}" class="btn btn-sm btn-danger">Ver reporte</a>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm p-3 text-center">
                <i class="fas fa-tint fa-2x text-info"></i>
                <h4 class="mt-2">{{ number_format($totalLeche, 2) }} L</h4>
                <p class="text-muted">Producción de leche</p>
                <a href="{{ route('reportes.leche') }}" class="btn btn-sm btn-info">Ver reporte</a>
            </div>
        </div>

    </div>

    {{-- TARJETA DE POTREROS --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm p-3 text-center">
                <i class="fas fa-tree fa-2x text-primary"></i>
                <h4 class="mt-2">{{ $totalPotreros }}</h4>
                <p class="text-muted">Potreros</p>
                <a href="{{ route('reportes.potreros') }}" class="btn btn-sm btn-primary">Ver reporte</a>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card shadow-sm p-3 text-center">
                {{-- <i class="fas fa-cash-register fa-2x text-success"></i>
                <h4 class="mt-2">📄</h4> 
                <p class="text-muted">📄 Reporte General</p> --}}
                <i class="fas fa-file-alt fa-2x text-success"></i>
                <h4 class="mt-2">Reporte General</h4>
                <p class="text-muted">Estado global</p>
                <a href="{{ route('reporte.general') }}" class="btn btn-sm btn-success">Ver reporte</a>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm p-3 text-center">
                {{-- <i class="fas fa-drumstick-bite fa-2x text-danger"></i>
                <h4 class="mt-2">📅</h4>
                <p class="text-muted"> 📅 Reporte Mensual</p> --}}
                <i class="fas fa-calendar-alt fa-2x text-danger"></i>
                <h4 class="mt-2">Reporte Mensual</h4>
                <p class="text-muted">Por rango de fechas</p>
                <a href="{{ route('reporte.porfechas') }}" class="btn btn-sm btn-danger">Ver reporte</a>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm p-3 text-center">
                {{-- <i class="fas fa-tint fa-2x text-info"></i> 
                <h4 class="mt-2">Total: {{ number_format($totalAnimales) }} </h4> 
                <p class="text-muted">🐄</p> --}}
                <i class="fas fa-cow fa-2x text-info"></i>
                <h4 class="mt-2">🐄 Reporte por Animal</h4>
                <p class="text-muted">Total: {{ number_format($totalAnimales) }}</p>
                <a href="{{ route('reporte.general.animal.select') }}" class="btn btn-sm btn-info">Ver reporte</a>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card shadow-sm p-3 text-center">
                <i class="fas fa-chart-line fa-2x text-warning"></i>
                <h4 class="mt-2">Análisis Financiero</h4>
                <p class="text-muted">Gastos vs Ingresos</p>
                <a href="{{ route('reportes.financiero') }}" class="btn btn-sm btn-warning">Ver análisis</a>
            </div>
        </div>
    </div>

    
    {{-- REPORTE COMBINADO --}}
    <div class="card shadow-sm p-4">
        <h4 class="mb-3">Producción Total por Animal (Carne + Leche)</h4>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Animal</th>
                    <th>Total Carne (kg)</th>
                    <th>Total Leche (L)</th>
                    <th>Total General</th>
                </tr>
            </thead>
            <tbody>
                @foreach($produccionTotal as $p)
                <tr>
                    <td>{{ $p['codigo'] }}</td>
                    <td>{{ number_format($p['total_carne'], 2) }}</td>
                    <td>{{ number_format($p['total_leche'], 2) }}</td>
                    <td><strong>{{ number_format($p['total_general'], 2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>

</div>

@endsection