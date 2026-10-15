@extends('layouts.layout')

@section('content')

<h2 class="mb-4">Vista previa de Compras</h2>

<div class="card shadow-sm p-4">

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

    <form action="{{ route('import.compras.import') }}" method="POST" class="mt-3">
        @csrf
        <button class="btn btn-success">Importar</button>
        <a href="{{ route('import.compras.form') }}" class="btn btn-secondary">Cancelar</a>
    </form>

</div>

@endsection
