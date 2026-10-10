@extends('layouts.app')

@section('content')

<h2 class="mb-4">Vista previa de Ventas</h2>

<div class="card shadow-sm p-4">

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

    <form action="{{ route('import.ventas.import') }}" method="POST" class="mt-3">
        @csrf
        <button class="btn btn-success">Importar</button>
        <a href="{{ route('import.ventas.form') }}" class="btn btn-secondary">Cancelar</a>
    </form>

</div>

@endsection
