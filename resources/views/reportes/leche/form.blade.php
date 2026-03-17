@extends('layouts.layout')

@section('content')

<h2 class="mb-4">Reporte de Producción de Leche por Fecha</h2>

<div class="card shadow-sm p-4">

    <form action="{{ route('reportes.leche.generar') }}" method="GET">

        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Desde</label>
                <input type="date" name="desde" class="form-control" required>
            </div>

            <div class="col-md-6 mb-3">
                <label>Hasta</label>
                <input type="date" name="hasta" class="form-control" required>
            </div>
        </div>

        <button class="btn btn-primary">Generar Reporte</button>

    </form>

</div>

@endsection