@extends('layouts.layout')
@section('title', 'Mis Potreros')

@section('content')

<h2 class="mb-4">Potreros</h2>

<a href="{{ route('potreros.create') }}" class="btn btn-primary mb-3">Nuevo Potrero</a>

<div class="row">
@foreach($potreros as $p)
    <div class="col-md-4">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h5>{{ $p->nombre }}</h5>
                <p>{{ $p->tamaño }} ha</p>
                <p>Estado: <strong>{{ $p->estado }}</strong></p>
                <a href="{{ route('potreros.show', $p->id) }}" class="btn btn-info btn-sm">Ver</a>
            </div>
        </div>
    </div>
@endforeach
</div>


@endsection