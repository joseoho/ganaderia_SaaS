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

    <h2 class="mb-4">Reporte de Ventas por Fecha</h2>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Descripción</th>
                <th>Fecha</th>
                <th>Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ventas as $v)
            <tr>
                <td>{{ $v->cliente }}</td>
                <td>{{ $v->descripcion }}</td>
                <td>{{ $v->fecha }}</td>
                <td>${{ number_format($v->monto_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <hr>

    <h4>Totales</h4>
    <p><strong>Total de ventas:</strong> {{ $totalVentas }}</p>
    <p><strong>Monto total vendido:</strong> ${{ number_format($montoTotal, 2) }}</p>

</div>

@endsection