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

    <h2 class="mb-4">Reporte de Animales en Potreros</h2>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Animal</th>
                <th>Potrero</th>
                <th>Fecha Entrada</th>
                <th>Fecha Salida</th>
            </tr>
        </thead>
        <tbody>
            @foreach($registros as $r)
            <tr>
                <td>{{ $r->animal->codigo_interno }}</td>
                <td>{{ $r->potrero->nombre }}</td>
                <td>{{ $r->fecha_entrada }}</td>
                <td>{{ $r->fecha_salida ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <hr>

    <h4>Totales</h4>
    <p><strong>Total de animales movilizados:</strong> {{ $totalAnimales }}</p>
    <p><strong>Total de potreros involucrados:</strong> {{ $totalPotreros }}</p>

</div>

@endsection