@extends('layouts.layout')

@section('content')
<h1>Animales</h1>

<!-- 🔍 Barra de búsqueda y filtros -->
<form method="GET" action="{{ route('animales.index') }}" class="row g-3 mb-4">
    <div class="col-md-3">
        <input type="text" name="search" class="form-control" placeholder="Buscar por código o raza..."
               value="{{ request('search') }}">
    </div>
    <div class="col-md-2">
        <select name="categoria" class="form-select">
            <option value="">-- Categoría --</option>
            <option value="bovino" {{ request('categoria')=='bovino' ? 'selected' : '' }}>Bovino</option>
            <option value="ovino" {{ request('categoria')=='ovino' ? 'selected' : '' }}>Ovino</option>
            <option value="caprino" {{ request('categoria')=='caprino' ? 'selected' : '' }}>Caprino</option>
        </select>
    </div>
    <div class="col-md-2">
        <select name="sexo" class="form-select">
            <option value="">-- Sexo --</option>
            <option value="M" {{ request('sexo')=='M' ? 'selected' : '' }}>Macho</option>
            <option value="F" {{ request('sexo')=='F' ? 'selected' : '' }}>Hembra</option>
        </select>
    </div>
    <div class="col-md-2">
        <input type="date" name="fecha_desde" class="form-control" value="{{ request('fecha_desde') }}">
    </div>
    <div class="col-md-2">
        <input type="date" name="fecha_hasta" class="form-control" value="{{ request('fecha_hasta') }}">
    </div>
    <div class="col-md-2">
        <select name="estado" class="form-select">
            <option value="">-- Estado --</option>
            <option value="activo" {{ request('estado')=='activo' ? 'selected' : '' }}>Activo</option>
            <option value="vendido" {{ request('estado')=='vendido' ? 'selected' : '' }}>Vendido</option>
            <option value="muerto" {{ request('estado')=='muerto' ? 'selected' : '' }}>Muerto</option>
        </select>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
        
    </div>
    <div class="col-md-2">
        <a href="{{ route('animales.index') }}" class="btn btn-secondary">
        <i class="fas fa-broom"></i> Limpiar</a> 
    </div>    
</form>

<a href="{{ route('animales.create') }}" class="btn btn-success mb-3">Registrar Animal</a>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>Código</th>
            <th>Categoría</th>
            <th>Raza</th>
            <th>Tipo</th>
            <th>Sexo</th>
            <th>Fecha Nacimiento</th>
            <th>Peso Entrada</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse($animales as $animal)
        <tr>
            <td>{{ $animal->codigo_interno }}</td>
            <td>{{ $animal->categoria }}</td>
            <td>{{ $animal->raza }}</td>
            <td>{{ $animal->tipo }}</td>
            <td>{{ $animal->sexo }}</td>
            <td>{{ $animal->fecha_nacimiento }}</td>
            <td>{{ $animal->peso_entrada }}</td>
            <td>{{ $animal->estado }}</td>
            <td>
                <a href="{{ route('animales.show', $animal->id) }}" class="btn btn-info btn-sm">Ver</a>
                <a href="{{ route('animales.edit', $animal->id) }}" class="btn btn-warning btn-sm">Editar</a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" class="text-center">No se encontraron animales</td>
        </tr>
        @endforelse
    </tbody>
</table>

<!-- 📄 Paginación -->
{{ $animales->links() }}

@endsection
