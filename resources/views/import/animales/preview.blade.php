@extends('layouts.layout')
@section('title', 'Vista previa de importación')

@section('content')

<h2 class="mb-4">Vista previa de importación</h2>

<div class="card shadow-sm p-4">

    <p class="text-muted">
        Revisa que los datos se vean correctos. Solo se muestran las primeras filas.
    </p>

    <div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                @foreach(array_keys($preview->first() ?? []) as $col)
                    <th>{{ $col }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($preview as $row)
                <tr>
                    @foreach($row as $val)
                        <td>{{ $val }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>

    <form action="{{ route('import.animales.import') }}" method="POST" class="mt-3">
        @csrf
        <button class="btn btn-success">Confirmar e importar</button>
        <a href="{{ route('import.animales.form') }}" class="btn btn-secondary">Cancelar</a>
    </form>

</div>

@endsection
