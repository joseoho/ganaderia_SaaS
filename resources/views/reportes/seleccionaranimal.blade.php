@extends('layouts.layout')
@section('title', 'Reporte por Animal')

@section('content')

<h2 class="mb-4">Generar Reporte por Animal</h2>

<div class="card shadow-sm p-4">

    <form action="{{ route('reporte.general.animal') }}" method="GET">

        <div class="mb-3">
            <label class="form-label">Seleccione un animal</label>
            <select name="animal_id" class="form-select" required>
                <option value="">-- Seleccione --</option>

                @foreach($animales as $a)
                    <option value="{{ $a->id }}">
                        {{ $a->codigo_interno }} — {{ $a->raza }} — {{ $a->sexo }}
                    </option>
                @endforeach

            </select>
        </div>

        <button class="btn btn-primary">Generar Reporte</button>

    </form>

</div>

@endsection