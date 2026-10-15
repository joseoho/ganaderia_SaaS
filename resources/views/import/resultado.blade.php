@extends('layouts.layout')

@section('content')

<h2 class="mb-4">Resultado de la Importación</h2>

<div class="card shadow-sm p-4">

    <h4>Registros importados: {{ $importados }}</h4>

    @if(($omitidos ?? 0) > 0)
        <div class="alert alert-warning mt-3 mb-0">
            <strong>Filas omitidas: {{ $omitidos }}</strong>
            <p class="mb-1 mt-2">No se han inventado datos: una fila se omite cuando le falta
                algún campo obligatorio o trae un valor que no se puede guardar.</p>
            <p class="mb-0">Revisa que las fechas estén en formato <code>AAAA-MM-DD</code>
                (por ejemplo <code>2026-02-01</code>), que los importes usen el punto como
                separador decimal (<code>1500.50</code>) y que no falte ningún campo obligatorio.
                El detalle de cada fila descartada queda en el registro de la aplicación.</p>
        </div>
    @endif

    <a href="{{ route('import.index') }}" class="btn btn-primary mt-3">Volver al módulo de importación</a>

</div>

@endsection
