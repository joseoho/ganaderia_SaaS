@extends('layouts.layout')
@section('title', 'Etiquetas QR')

@section('content')

<style>
@media print {
    body * { visibility: hidden !important; }
    #area-imprimir, #area-imprimir * { visibility: visible !important; }
    #area-imprimir { position: absolute; left: 0; top: 0; width: 100%; }
    @page { size: Letter; margin: 1cm; }
    .btn, button, nav, header, footer { display: none !important; }
}

.etiqueta {
    width: 220px;
    height: 260px;
    border: 1px solid #000;
    padding: 10px;
    margin: 10px;
    text-align: center;
    display: inline-block;
    border-radius: 8px;
}
</style>

<button onclick="window.print()" class="btn btn-primary mb-3">Imprimir Etiquetas</button>

<div id="area-imprimir">

    @foreach($animales as $animal)
        <div class="etiqueta">

            <h5>{{ $animal->codigo_interno }}</h5>
            <p class="small">{{ $animal->raza }} — {{ $animal->sexo }}</p>

            <div>
                {!! QrCode::size(150)->generate(route('animal.qr.show', $animal->id)) !!}
            </div>

            <p class="small mt-2">ID: {{ $animal->id }}</p>

        </div>
    @endforeach

</div>

@endsection