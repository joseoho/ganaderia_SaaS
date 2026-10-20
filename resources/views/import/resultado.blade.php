@extends('layouts.layout')

@section('content')

<h2 class="mb-4">Resultado de la Importación</h2>

<div class="card shadow-sm p-4">

    <h4>Registros importados: {{ $importados }}</h4>

    @if(($omitidos ?? 0) > 0)
        <div class="alert alert-warning mt-3">
            <strong>Filas omitidas: {{ $omitidos }}</strong>
            <p class="mb-2 mt-2">No se han inventado datos: una fila se omite cuando le falta
                algún campo obligatorio o trae un valor que no se puede guardar.</p>

            @php($motivos = session('import_motivos', []))

            @if(! empty($motivos))
                <p class="mb-1"><strong>Motivos:</strong></p>
                <ul class="mb-2 small">
                    @foreach($motivos as $motivo)
                        <li>{{ $motivo }}</li>
                    @endforeach
                </ul>
            @endif

            <p class="mb-0 small">
                Se aceptan fechas <code>AAAA-MM-DD</code> y <code>DD/MM/AAAA</code>, e importes
                con punto o con coma decimal (<code>1500.50</code> o <code>1.500,50</code>).
                Si una fila que parece correcta se omite, es que su fecha o su importe no
                llegaron a leerse como un valor válido.
            </p>
        </div>
    @endif

    <a href="{{ route('import.index') }}" class="btn btn-primary mt-3">Volver al módulo de importación</a>

</div>

@endsection
