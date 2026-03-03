@extends('layouts.layout')
@section('title', 'Registrar Animal')

@section('content')

<h2>Ficha del Animal: {{ $animal->codigo_interno }}</h2>

<div class="card">
    <p><strong>Raza:</strong> {{ $animal->raza }}</p>
    <p><strong>Sexo:</strong> {{ $animal->sexo }}</p>
    <p><strong>Peso actual:</strong> {{ $animal->peso_entrada }} kg</p>
</div>

<div class="card">
    <h3 class="section-title">Producción de Leche</h3>
    @foreach($produccion_leche as $p)
        <p>{{ $p->fecha }} — {{ $p->litros }} litros</p>
    @endforeach
</div>

<div class="card">
    <h3 class="section-title">Producción de Carne</h3>
    @foreach($produccion_carne as $p)
        <p>{{ $p->fecha }} — {{ $p->peso }} kg</p>
    @endforeach
</div>

<div class="card">
    <h3 class="section-title">Tratamientos</h3>
    @foreach($tratamientos as $t)
        <p>{{ $t->motivo }} — {{ $t->medicamento }} ({{ $t->fecha_inicio }})</p>
    @endforeach
</div>

<div class="card">
    <h3 class="section-title">Vacunas</h3>
    @foreach($vacunas as $v)
        <p>{{ $v->nombre }} — {{ $v->fecha }}</p>
    @endforeach
</div>

<div class="card">
    <h3 class="section-title">Reproducción</h3>
    @foreach($reproduccion as $r)
        <p>{{ $r->tipo }} — {{ $r->fecha }}</p>
    @endforeach
</div>

<div class="card">
    <h3 class="section-title">Genealogía</h3>
    @if($genealogia)
        <p><strong>Padre:</strong> {{ $genealogia->padre }}</p>
        <p><strong>Madre:</strong> {{ $genealogia->madre }}</p>
    @else
        <p>No hay datos de genealogía.</p>
    @endif
</div>
                <div class="mt-4 d-flex justify-content-between">
                    <a href="{{ route('animales.index') }}" class="btn btn-secondary">Regresar</a>
                </div>

@endsection