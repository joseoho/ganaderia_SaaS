@extends('layouts.layout')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Ficha Técnica: {{ $animal->codigo_interno }}</h4>
                    <span class="badge {{ $animal->estado == 'activo' ? 'bg-success' : ($animal->estado == 'vendido' ? 'bg-primary' : 'bg-danger') }}">
                        {{ strtoupper($animal->estado) }}
                    </span>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <tr>
                            <th>Categoría:</th>
                            <td>{{ ucfirst($animal->categoria) }}</td>
                        </tr>
                        <tr>
                            <th>Raza:</th>
                            <td>{{ $animal->raza }}</td>
                        </tr>
                        <tr>
                            <th>Sexo:</th>
                            <td>{{ $animal->sexo == 'M' ? 'Macho' : 'Hembra' }}</td>
                        </tr>
                        <tr>
                            <th>Fecha Nacimiento:</th>
                            <td>{{ \Carbon\Carbon::parse($animal->fecha_nacimiento)->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <th>Edad:</th>
                            <td>{{ \Carbon\Carbon::parse($animal->fecha_nacimiento)->age }} años</td>
                        </tr>
                        <tr>
                            <th>Peso Entrada:</th>
                            <td>{{ $animal->peso_entrada }} kg</td>
                        </tr>
                    </table>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('animales.index') }}" class="btn btn-secondary">Volver al listado</a>
                    <div>
                        <a href="{{ route('animales.edit', $animal->id) }}" class="btn btn-warning">Editar</a>
                        <form action="{{ route('animales.destroy', $animal->id) }}" method="POST" class="d-inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de eliminar este registro?')">Eliminar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection