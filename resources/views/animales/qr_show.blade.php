@extends('layouts.layout')
@section('title', 'Ficha del animal')

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
        <p>{{ \App\Support\Formato::fecha($p->fecha) }} — {{ $p->litros }} litros</p>
    @endforeach
</div>

<div class="card">
    <h3 class="section-title">Producción de Carne</h3>
    @foreach($produccion_carne as $p)
        <p>{{ \App\Support\Formato::fecha($p->fecha) }} — {{ $p->peso }} kg</p>
    @endforeach
</div>

<div class="card">
    <h3 class="section-title">Tratamientos</h3>
    @foreach($tratamientos as $t)
        <p>{{ $t->motivo }} — {{ $t->medicamento }} ({{ \App\Support\Formato::fecha($t->fecha) }})</p>
    @endforeach
</div>

<div class="card">
    <h3 class="section-title">Vacunas</h3>
    @foreach($vacunas as $v)
        <p>{{ $v->nombre }} — {{ \App\Support\Formato::fecha($v->fecha_aplicacion) }}</p>
    @endforeach
</div>

<div class="card">
    <h3 class="section-title">Reproducción</h3>
    @foreach($reproduccion as $r)
        <p>{{ $r->tipo }} — {{ \App\Support\Formato::fecha($r->fecha) }}</p>
    @endforeach
        @if($r = $reproduccion->last())
            <p><strong>Último evento:</strong> {{ $r->tipo }} el {{ \App\Support\Formato::fecha($r->fecha) }}</p>
    @else
        <p>No hay datos de genealogía.</p>
    @endif
    
</div>

<div class="card">
    <h3 class="section-title">Genealogía</h3>
    @if($genealogia)
        <p><strong>Padre:</strong> {{ $genealogia->padre?->codigo_interno }}</p>
        <p><strong>Madre:</strong> {{ $genealogia->madre?->codigo_interno }}</p>
    @else
        <p>No hay datos de genealogía.</p>
    @endif
</div>
                <div class="mt-4 d-flex justify-content-between">
                    <a href="{{ route('animales.index') }}" class="btn btn-secondary">Regresar</a>
                </div>

                <a href="{{ route('animal.qr.editar.todo', $animal->id) }}"
                style="display:block; margin-top:20px; text-align:center; font-weight:bold;">
                ✏️ Editar todos los datos del animal
                </a>
@endsection