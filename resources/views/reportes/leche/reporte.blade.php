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

    <h2 class="mb-4">Reporte de Producción de Leche por Fecha</h2>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Animal</th>
                <th>Fecha</th>
                <th>Litros</th>
                <th>Variación</th>
            </tr>
        </thead>
        <tbody>
            @foreach($leche as $l)
            <tr>
                <td>{{ $l->animal->codigo_interno }}</td>
                <td>{{ $l->fecha }}</td>
                <td>{{ $l->litros }}</td>
                <td>
                    @if($l->variacion > 0)
                        <span class="text-success">+{{ $l->variacion }}</span>
                    @elseif($l->variacion < 0)
                        <span class="text-danger">{{ $l->variacion }}</span>
                    @else
                        0
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <hr>

    <h4>Totales</h4>
    <p><strong>Total de registros:</strong> {{ $totalRegistros }}</p>
    <p><strong>Total de litros producidos:</strong> {{ number_format($totalLitros, 2) }} litros</p>
    <p><strong>Promedio por registro:</strong> {{ number_format($promedioLitros, 2) }} litros</p>

</div>

@endsection