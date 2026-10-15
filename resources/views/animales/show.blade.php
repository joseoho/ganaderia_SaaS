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
                    <div class="table-responsive">
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
                                <td>{{ \App\Support\Formato::fecha($animal->fecha_nacimiento) }}</td>
                            </tr>
                            <tr>
                                <th>Edad:</th>
                                <td>{{ \Carbon\Carbon::parse($animal->fecha_nacimiento)->age }} años</td>
                            </tr>
                            <tr>
                                <th>Peso Entrada:</th>
                                <td>{{ $animal->peso_entrada }} kg</td>
                            </tr>
                            {{-- El potrero actual lo resuelve el controlador: la vista
                                 leia `$animal->animalPotreros->last()->potrero`, que
                                 daba error 500 si el animal no habia estado nunca en
                                 un potrero. --}}
                            <tr>
                                <th>Potrero actual:</th>
                                <td>{{ $potreroActual?->nombre ?? 'Sin potrero asignado' }}</td>
                            </tr>
                            <tr>
                                <th>QR:</th>
                                <td>
                                    <a href="{{ route('animal.qr.show', $animal->id) }}" target="_blank">
                                        {!! QrCode::size(100)->generate(route('animal.qr.show', $animal->id)) !!}
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </div>
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
