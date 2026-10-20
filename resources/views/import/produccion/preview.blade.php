@extends('layouts.layout')
@section('title', 'Vista previa de '.$titulo)

@section('content')

<h2 class="mb-4">Vista previa de {{ $titulo }}</h2>

<div class="card shadow-sm p-4">

    <p class="text-muted">Revisa que los datos se vean correctos. Se muestran las 20 primeras filas.</p>

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
            @forelse($preview as $row)
                <tr>
                    @foreach($row as $val)
                        <td>{{ $val }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center text-muted">
                        No hay filas que mostrar. Vuelve a subir el archivo.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>

    <form action="{{ route('import.produccion.import', ['tipo' => $tipo]) }}" method="POST" class="mt-3">
        @csrf
        <button class="btn btn-success">Importar</button>
        <a href="{{ route('import.produccion.form', ['tipo' => $tipo]) }}" class="btn btn-secondary">Cancelar</a>
    </form>

</div>

@endsection
