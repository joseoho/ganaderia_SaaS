@extends('layouts.layout')

@section('content')

<h2 class="mb-4">QR del Animal: {{ $animal->codigo_interno }}</h2>

<div class="card shadow-sm p-4 text-center">

    <h4>Escanea para ver la ficha completa</h4>

    <div class="my-4">
        {!! QrCode::size(250)->generate($url) !!}
    </div>

    <p class="text-muted">{{ $url }}</p>

    <a href="{{ route('animales.show', $animal->id) }}" class="btn btn-secondary mt-3">
        Volver
    </a>

</div>

@endsection