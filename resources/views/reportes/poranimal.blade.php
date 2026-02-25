@extends('layouts.layout')
@section('title', 'Reporte por Animal')

@section('content')
<style>
@media print {

    /* Oculta absolutamente todo */
    body * {
        visibility: hidden !important;
    }

    /* Muestra solo el área del reporte */
    #area-imprimir, #area-imprimir * {
        visibility: visible !important;
    }

    /* Posiciona el reporte al inicio */
    #area-imprimir {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
    }

    /* Página tipo carta */
    @page {
        size: Letter;
        margin: 1.5cm;
    }

    /* Quitar bordes y sombras */
    .card, .table {
        border: none !important;
        box-shadow: none !important;
    }

    /* Ocultar botones, filtros, paginación */
    .btn, button, form, nav, header, footer, .pagination {
        display: none !important;
    }
}
</style>
<div id="area-imprimir" class="card shadow-sm p-4">

    <h2 class="text-center mb-4">Reporte por Animal</h2>

    <h4>Animal: {{ $animal->codigo_interno }}</h4>
    <p><strong>Raza:</strong> {{ $animal->raza }}</p>
    <p><strong>Sexo:</strong> {{ $animal->sexo }}</p>
    <p><strong>Peso Entrada:</strong> {{ $animal->peso_entrada }} kg</p>

    <hr>

    <h4>Producción de Leche</h4>
    <ul>
        @foreach($produccion_leche as $p)
            <li>{{ $p->fecha }} — {{ $p->litros }} litros</li>
        @endforeach
    </ul>

    <hr>

    <h4>Producción de Carne</h4>
    <ul>
        @foreach($produccion_carne as $p)
            <li>{{ $p->fecha }} — {{ $p->peso }} kg</li>
        @endforeach
    </ul>

    <hr>

    <h4>Tratamientos</h4>
    <ul>
        @foreach($tratamientos as $t)
            <li>{{ $t->motivo }} — {{ $t->medicamento }} ({{ $t->fecha_inicio }})</li>
        @endforeach
    </ul>

    <hr>

    <h4>Vacunas</h4>
    <ul>
        @foreach($vacunas as $v)
            <li>{{ $v->nombre }} — {{ $v->fecha }}</li>
        @endforeach
    </ul>

    <hr>

    <h4>Reproducción</h4>
    <ul>
        @foreach($reproduccion as $r)
            <li>{{ $r->tipo }} — {{ $r->fecha }}</li>
        @endforeach
    </ul>

    <hr>

    <h4>Genealogía</h4>
    @if($genealogia)
        <p><strong>Padre:</strong> {{ $genealogia->padre }}</p>
        <p><strong>Madre:</strong> {{ $genealogia->madre }}</p>
    @else
        <p>No hay datos de genealogía.</p>
    @endif

    <div class="text-center mt-4">
        <button onclick="window.print()" class="btn btn-primary">Imprimir</button>
    </div>

</div>

@endsection