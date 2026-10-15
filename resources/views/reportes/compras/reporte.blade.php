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

    <h2 class="mb-4">Reporte de Gastos por Fecha</h2>

    <div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Categoria</th>
                <th>Descripcion</th>
                <th>Fecha</th>
                <th>Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($compras as $c)
            <tr>
                <td>{{ $c->categoria }}</td>
                <td>{{ $c->descripcion }}</td>
                <td>{{ \App\Support\Formato::fecha($c->fecha) }}</td>
                <td>${{ number_format($c->monto, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>

    <hr>

    <h4>Totales</h4>
    <p><strong>Total de compras:</strong> {{ $totalCompras }}</p>
    <p><strong>Monto total:</strong> ${{ number_format($montoTotal, 2) }}</p>

</div>

@endsection