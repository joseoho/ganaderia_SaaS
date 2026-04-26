@extends('layouts.layout')
@section('title', 'Análisis Financiero')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-chart-pie text-warning"></i> Análisis Financiero</h2>
        <button onclick="window.print()" class="btn btn-outline-secondary">
            <i class="fas fa-print"></i> Imprimir Informe
        </button>
    </div>

    <!-- Resumen en Tarjetas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-success border-4">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small">Ventas Totales</h6>
                    <h3 class="text-success">${{ number_format($totalVentas, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-danger border-4">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small">Compras Animales</h6>
                    <h3 class="text-danger">${{ number_format($totalCompras, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-warning border-4">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small">Gastos Operativos</h6>
                    <h3 class="text-warning">${{ number_format($totalGastos, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 {{ $balance >= 0 ? 'bg-success text-white' : 'bg-danger text-white' }}">
                <div class="card-body text-center">
                    <h6 class="text-uppercase small">Balance Neto</h6>
                    <h3>${{ number_format($balance, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfica -->
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm p-4">
                <h5 class="mb-4">Comparativa Mensual: Ingresos vs Egresos (Año Actual)</h5>
                <div style="height: 400px;">
                    <canvas id="financialChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cargar Chart.js desde CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const ctx = document.getElementById('financialChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($labels),
            datasets: [
                {
                    label: 'Ingresos (Ventas)',
                    data: @json($dataVentas),
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: 'rgb(40, 167, 69)',
                    borderWidth: 1
                },
                {
                    label: 'Egresos (Compras + Gastos)',
                    data: @json($dataGastos),
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderColor: 'rgb(220, 53, 69)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': $' + context.raw.toLocaleString();
                        }
                    }
                }
            }
        }
    });
</script>
@endsection