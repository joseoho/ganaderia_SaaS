@extends('layouts.app')

@section('content')

<h2 class="mb-4">Vista previa de Producción</h2>

<div class="card shadow-sm p-4">

    <p class="text-muted">Revisa que los datos se vean correctos.</p>

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

    <form action="{{ route('import.produccion.import') }}" method="POST" class="mt-3">
        @csrf
        <button class="btn btn-success">Importar</button>
        <a href="{{ route('import.produccion.form') }}" class="btn btn-secondary">Cancelar</a>
    </form>

</div>

@endsection
