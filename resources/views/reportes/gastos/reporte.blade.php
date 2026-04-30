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

    <h2 class="mb-4">Reporte de Compras por Fecha</h2>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Proveedor</th>
                <th>Descripción</th>
                <th>Fecha</th>
                <th>Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($compras as $c)
            <tr>
                <td>{{ $c->proveedor }}</td>
                <td>{{ $c->descripcion }}</td>
                <td>{{ $c->fecha }}</td>
                <td>${{ number_format($c->monto, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <hr>

    <h4>Totales</h4>
    <p><strong>Total de compras:</strong> {{ $totalCompras }}</p>
    <p><strong>Monto total:</strong> ${{ number_format($montoTotal, 2) }}</p>

</div>

@endsection