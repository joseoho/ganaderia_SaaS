@extends('layouts.layout')

@section('content')

<div class="container">

    <h2 class="mb-4">Potrero: {{ $potrero->nombre }}</h2>

    <div class="card shadow-sm p-4 mb-4">
        <h5>Información del potrero</h5>
        <p><strong>Tamaño:</strong> {{ $potrero->tamaño }} ha</p>
        <p><strong>Tipo de pasto:</strong> {{ $potrero->tipo_pasto }}</p>
        <p><strong>Estado:</strong> {{ ucfirst($potrero->estado) }}</p>
        <p><strong>Último ingreso:</strong> {{ $potrero->fecha_ultimo_ingreso }}</p>
        <p><strong>Último descanso:</strong> {{ $potrero->fecha_ultimo_descanso }}</p>

        <a href="{{ route('potreros.edit', $potrero->id) }}" class="btn btn-warning btn-sm">Editar</a>
    </div>

    {{-- Animales dentro --}}
    <div class="card shadow-sm p-4 mb-4">
        <h5>Animales dentro del potrero</h5>

        @if($animalesDentro->count() == 0)
            <p class="text-muted">No hay animales en este potrero.</p>
        @else
            <ul class="list-group">
                @foreach($animalesDentro as $a)
                    <li class="list-group-item">
                        {{ $a->animal->codigo_interno }} — {{ $a->animal->raza }}
                        <span class="text-muted float-end">
                            Entrada: {{ $a->fecha_entrada }}
                        </span>
                    </li>
                @endforeach
            </ul>

            <form action="{{ route('potreros.salida', $potrero->id) }}" method="POST" class="mt-3">
                @csrf
                <button class="btn btn-danger">Registrar salida de todos</button>
            </form>
        @endif
    </div>

    {{-- Asignar animales --}}
    <div class="card shadow-sm p-4 mb-4">
        <h5>Asignar animales a este potrero</h5>

        <form action="{{ route('potreros.asignar', $potrero->id) }}" method="POST">
            @csrf

            <div class="mb-3">
                <label class="form-label">Seleccione animales</label>
                <select name="animales[]" class="form-select" multiple required>
                    @foreach(\App\Models\Animal::where('inquilino_id', Auth::user()->inquilino_id)->get() as $animal)
                        <option value="{{ $animal->id }}">
                            {{ $animal->codigo_interno }} — {{ $animal->raza }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button class="btn btn-primary">Asignar</button>
        </form>
    </div>

    {{-- Historial de rotaciones --}}
    <div class="card shadow-sm p-4">
        <h5>Historial de rotaciones</h5>

        @php
            $rotaciones = \App\Models\Rotacion::where('potrero_id', $potrero->id)->get();
        @endphp

        @if($rotaciones->count() == 0)
            <p class="text-muted">No hay rotaciones registradas.</p>
        @else
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Días</th>
                        <th>Carga animal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rotaciones as $r)
                        <tr>
                            <td>{{ $r->fecha_entrada }}</td>
                            <td>{{ $r->fecha_salida }}</td>
                            <td>{{ $r->dias_ocupacion }}</td>
                            <td>{{ $r->carga_animal }} animales/ha</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</div>

@endsection