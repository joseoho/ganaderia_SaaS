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

    <div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Proveedor</th>
                <th>Descripcion</th>
                <th>Fecha</th>
                <th>Monto</th>
            </tr>
        </thead>
        <tbody>
            @forelse($compras as $compra)
            <tr>
                <td>{{ $compra->proveedor }}</td>
                <td>{{ $compra->descripcion }}</td>
                <td>{{ \App\Support\Formato::fecha($compra->fecha) }}</td>
                <td>${{ number_format((float) $compra->monto_total, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center text-muted">
                    No hay compras registradas en ese rango de fechas.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>

    <hr>

    <h4>Totales</h4>
    <p><strong>Total de compras:</strong> {{ $totalCompras }}</p>
    <p><strong>Monto total:</strong> ${{ number_format((float) $montoTotal, 2) }}</p>

</div>

@endsection
