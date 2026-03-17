@extends('layouts.layout')

@section('content')

<style>
@media print {
    body * { visibility: hidden !important; }
    #area-imprimir, #area-imprimir * { visibility: visible !important; }
    #area-imprimir { position: absolute; left: 0; top: 0; width: 100%; }
    @page { size: Letter; margin: 1cm; }
    .btn, nav, header, footer { display: none !important; }
}
</style>

<button onclick="window.print()" class="btn btn-primary mb-3">Imprimir</button>

<div id="area-imprimir">

    <h2 class="mb-4">Reporte de Producción de Carne por Fecha</h2>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Animal</th>
                <th>Fecha</th>
                <th>Peso (kg)</th>
                <th>Ganancia diaria (kg)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($carne as $c)
            <tr>
                <td>{{ $c->animal->codigo_interno }}</td>
                <td>{{ $c->fecha }}</td>
                <td>{{ $c->peso }}</td>
                <td>{{ $c->ganancia_diaria }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <hr>

    <h4>Totales</h4>
    <p><strong>Total de registros:</strong> {{ $totalRegistros }}</p>
    <p><strong>Total de ganancia acumulada:</strong> {{ number_format($totalGanado, 2) }} kg</p>
    <p><strong>Promedio de ganancia diaria:</strong> {{ number_format($promedioGanancia, 2) }} kg</p>

</div>

@endsection